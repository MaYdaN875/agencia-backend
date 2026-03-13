# Guía de implementación: Autenticación en dos pasos (2FA) TOTP

Documento para reporte técnico sobre la autenticación en dos factores implementada en el backend de la agencia.

---

## 1. Resumen ejecutivo

El backend implementa **autenticación en dos pasos (2FA)** mediante **TOTP** (Time-based One-Time Password, RFC 6238). Los usuarios (clientes y administradores) deben configurar 2FA para poder usar la aplicación: tras el registro o el primer login sin 2FA se obtiene un token temporal que permite únicamente completar la configuración (escanear QR e introducir código). Una vez activado el 2FA, cada login exige además un código de 6 dígitos generado por una app (p. ej. Google Authenticator).

**Alcance:**

- **Usuarios normales** (`users`): registro → obligatorio configurar 2FA → login con email + contraseña + código TOTP.
- **Administradores** (`admin_users`): login con usuario + contraseña → si no tienen 2FA, obligatorio configurarlo → luego login con código TOTP.
- Endpoints para **activar** y **desactivar** 2FA (desactivar requiere código actual).

---

## 2. Tecnología utilizada

| Componente | Uso |
|------------|-----|
| **speakeasy** (v2.0.0) | Generar secreto TOTP, validar códigos de 6 dígitos con ventana de 1 paso (30 s). |
| **qrcode** (v1.5.x) | Generar imagen QR en Data URL a partir de `otpauth_url` para escanear con la app de autenticación. |
| **JWT** (jsonwebtoken) | Tokens de sesión (7 días), token temporal para flujo 2FA (5 min), token de setup (15 min). |

**TOTP:** El servidor genera un secreto compartido (base32); la app del usuario (Google Authenticator, Authy, etc.) genera códigos de 6 dígitos que cambian cada ~30 segundos. El servidor verifica con `speakeasy.totp.verify()` usando ese secreto y una ventana de 1 paso para tolerar pequeño desfase de tiempo.

---

## 3. Modelo de datos

### 3.1 Tablas y columnas para 2FA

**`users`** (usuarios normales):

```sql
mfa_secret VARCHAR(255) NULL,   -- Secreto TOTP en base32 (solo si 2FA activo)
mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
```

**`admin_users`** (administradores):

```sql
mfa_secret VARCHAR(255) NULL,
mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
```

**`mfa_pending`** (secretos temporales durante la configuración):

Al solicitar “configurar 2FA” (`GET /mfa/setup`) se guarda aquí el secreto hasta que el usuario confirma con un código (`POST /mfa/enable`). Así el secreto no se persiste en la tabla de usuarios hasta que se verifica que la app genera el mismo código.

```sql
CREATE TABLE mfa_pending (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('user', 'admin') NOT NULL,
    user_id INT NOT NULL,
    secret VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_type, user_id)
);
```

### 3.2 Migración para bases existentes

Si la base ya existía sin 2FA, se aplica una sola vez:

```bash
mysql -u appuser -p mi_app < migrations/001_totp_users.sql
```

El script incluye (entre otras cosas):

- Creación de `users` si no existe, con `mfa_secret` y `mfa_enabled`.
- `ALTER TABLE admin_users` para añadir `mfa_secret` y `mfa_enabled` si no existen.
- Creación de `mfa_pending`.

---

## 4. Flujos de autenticación

### 4.1 Registro de usuario normal

1. Cliente llama `POST /api/auth/register` con `email`, `password`, `full_name`.
2. Se crea el usuario en `users`; **no** se devuelve sesión.
3. Respuesta: `requiresMfaSetup: true`, `setupToken` (JWT, 15 min) y mensaje para configurar 2FA.
4. El cliente debe:
   - Llamar `GET /api/auth/mfa/setup` con `Authorization: Bearer <setupToken>` (o cookie si se guardara).
   - Mostrar el QR y pedir el código.
   - Llamar `POST /api/auth/mfa/enable` con `{ "code": "123456" }` y el mismo token.
5. La respuesta de `mfa/enable` (con `setupOnly`) incluye el **token** y **user** definitivos; a partir de ahí la sesión es normal.

### 4.2 Login (usuario o admin) cuando 2FA está activo

1. Cliente llama `POST /api/auth/login` con `email` (o `username` para admin) y `password`.
2. Si las credenciales son correctas y el usuario **tiene** `mfa_enabled` y `mfa_secret`:
   - Respuesta: `requiresMfa: true`, `tempToken` (JWT, 5 min, purpose `"2fa"`) y mensaje para introducir el código.
3. Cliente muestra campo de código y llama `POST /api/auth/verify-2fa` con `{ "tempToken": "...", "code": "123456" }`.
4. El servidor verifica el código TOTP con el secreto del usuario; si es válido, devuelve el JWT de sesión (y opcionalmente cookie).

### 4.3 Login cuando 2FA no está configurado

1. Mismo `POST /api/auth/login`; si la contraseña es correcta pero **no** tiene 2FA activo:
   - Respuesta: `requiresMfaSetup: true`, `setupToken` (15 min) y mensaje para configurar 2FA.
2. Flujo igual que tras el registro: `GET /mfa/setup` → mostrar QR → `POST /mfa/enable` con código. Si el token era `setupOnly`, la respuesta de `mfa/enable` devuelve el JWT de sesión.

### 4.4 Activar 2FA (usuario ya autenticado)

1. Usuario autenticado (JWT normal) llama `GET /api/auth/mfa/setup`.
2. Se genera nuevo secreto, se guarda en `mfa_pending` y se devuelve `qrCode` (Data URL) y el secreto en base32.
3. Usuario escanea el QR e introduce un código.
4. Llama `POST /api/auth/mfa/enable` con `{ "code": "123456" }`.
5. Se verifica el código contra el secreto en `mfa_pending`; si es correcto, se actualiza `users` o `admin_users` con `mfa_secret` y `mfa_enabled = 1` y se limpia `mfa_pending`. No se devuelve nuevo JWT (ya estaba autenticado).

### 4.5 Desactivar 2FA

1. Usuario autenticado llama `POST /api/auth/mfa/disable` con `{ "code": "123456" }` (código actual de la app).
2. Se verifica el código con el `mfa_secret` guardado; si es correcto, se hace `mfa_secret = NULL` y `mfa_enabled = 0`.

---

## 5. Endpoints API relacionados con 2FA

| Método | Ruta | Auth | Descripción |
|--------|------|------|-------------|
| POST | `/api/auth/register` | No | Registro; devuelve `setupToken` para configurar 2FA. |
| POST | `/api/auth/login` | No | Login; si tiene 2FA → `requiresMfa` + `tempToken`; si no → `requiresMfaSetup` + `setupToken`. |
| POST | `/api/auth/verify-2fa` | No (usa tempToken en body) | Body: `{ tempToken, code }`. Devuelve JWT de sesión. |
| GET | `/api/auth/mfa/setup` | Sí (JWT o setupToken) | Genera secreto y QR; guarda secreto en `mfa_pending`. |
| POST | `/api/auth/mfa/enable` | Sí | Body: `{ code }`. Activa 2FA; si token es setupOnly, devuelve JWT. |
| POST | `/api/auth/mfa/disable` | Sí | Body: `{ code }`. Desactiva 2FA verificando código actual. |

Las rutas MFA están definidas en `api/routes/authRoutes.js`:

```javascript
router.get("/mfa/setup", Auth.requireAuth, Auth.mfaSetup);
router.post("/mfa/enable", Auth.requireAuth, Auth.mfaEnable);
router.post("/mfa/disable", Auth.requireAuth, Auth.mfaDisable);
```

`requireAuth` acepta JWT en cookie `auth_token` o en cabecera `Authorization: Bearer <token>`. El `setupToken` emitido en registro/login sin 2FA también es un JWT válido para `requireAuth`, por eso el cliente puede llamar `mfa/setup` y `mfa/enable` con ese token.

---

## 6. Código relevante (resumen por función)

### 6.1 Constantes y dependencias

```javascript
const TEMP_TOKEN_EXPIRY = "5m";   // Token para introducir código 2FA tras login
const SETUP_TOKEN_EXPIRY = "15m"; // Token para configurar 2FA la primera vez
```

- **speakeasy**: `generateSecret()`, `totp.verify()`.
- **qrcode**: `QRCode.toDataURL(otpauth_url)` para el QR que escanea la app.

### 6.2 Registro (obligación de 2FA)

Tras insertar el usuario en `users`, se emite un JWT con `setupOnly: true` y no se da sesión; la respuesta indica que debe configurarse 2FA:

```javascript
const setupToken = jwt.sign(
  { sub: user.id, email: user.email, full_name: user.full_name, role: "user", setupOnly: true },
  JWT_SECRET,
  { expiresIn: SETUP_TOKEN_EXPIRY }
);
return res.status(201).json({
  requiresMfaSetup: true,
  setupToken,
  message: "Debes configurar la autenticación en dos pasos (2FA) para activar tu cuenta. Escanea el QR con tu app de autenticación.",
});
```

### 6.3 Login: decisión requiresMfa vs requiresMfaSetup

Después de comprobar contraseña:

- Si **no** tiene 2FA activo: se devuelve `requiresMfaSetup: true` y `setupToken` (mismo uso que en registro).
- Si **sí** tiene 2FA: se devuelve `requiresMfa: true` y un `tempToken` con `purpose: "2fa"` para usar en `verify-2fa`:

```javascript
const tempToken = jwt.sign(
  { type: role, userId: user.id, purpose: "2fa" },
  JWT_SECRET,
  { expiresIn: TEMP_TOKEN_EXPIRY }
);
return res.json({
  requiresMfa: true,
  tempToken,
  message: "Introduce el código de tu app de autenticación",
});
```

### 6.4 Verificación del código 2FA (verify2fa)

- Se recibe `tempToken` y `code`.
- Se verifica el JWT y que `purpose === "2fa"` y existan `userId` y `type` (user/admin).
- Se obtiene el `mfa_secret` del usuario en la tabla correspondiente.
- Verificación TOTP con speakeasy:

```javascript
const valid = speakeasy.totp.verify({
  secret: rows[0].mfa_secret,
  encoding: "base32",
  token: String(code).replace(/\s/g, ""),
  window: 1,
});
```

- Si es válido, se genera el JWT de sesión (7 días) y se devuelve (y se puede setear cookie).

### 6.5 Generación del QR (mfaSetup)

- Se genera el secreto con `speakeasy.generateSecret()` (nombre de app + usuario).
- Se guarda en `mfa_pending` por `user_type` y `user_id` (se borra cualquier pendiente anterior).
- Se genera el QR a partir de `secret.otpauth_url`:

```javascript
const secret = speakeasy.generateSecret({
  name: `${APP_NAME} (${req.authUser.email || req.authUser.username})`,
  length: 20,
});
const qrCode = await QRCode.toDataURL(secret.otpauth_url || "");
// INSERT en mfa_pending ...
return res.json({ secret: secret.base32, qrCode, message: "..." });
```

### 6.6 Activación 2FA (mfaEnable)

- Se lee el secreto pendiente de `mfa_pending` para ese usuario/rol.
- Se verifica el `code` con `speakeasy.totp.verify()` contra ese secreto.
- Se actualiza `users` o `admin_users` con `mfa_secret` y `mfa_enabled = 1` y se borra de `mfa_pending`.
- Si el JWT era `setupOnly`, se genera el JWT definitivo y se devuelve en la respuesta (y cookie) para completar el login/registro.

### 6.7 Desactivar 2FA (mfaDisable)

- Se obtiene `mfa_secret` del usuario autenticado.
- Se verifica el `code` con `speakeasy.totp.verify()`.
- Se hace `UPDATE ... SET mfa_secret = NULL, mfa_enabled = 0`.

---

## 7. Seguridad

- **Secretos TOTP:** Se almacenan en base de datos (solo en el servidor); el cliente nunca recibe el secreto persistido, solo el QR (que contiene el mismo secreto) durante el setup.
- **Temp token (5 min):** Limita el tiempo para introducir el código tras el login; no da acceso a recursos, solo a `verify-2fa`.
- **Setup token (15 min):** Solo para completar la configuración 2FA; si tiene `setupOnly`, al activar 2FA se canjea por un JWT de sesión completo.
- **Ventana TOTP:** `window: 1` permite un paso de 30 s antes/después para tolerar desfase de reloj entre servidor y app.
- **Desactivar 2FA:** Exige el código actual para evitar desactivación por alguien que solo tenga la sesión robada.

---

## 8. Dependencias npm

En `api/package.json`:

```json
"speakeasy": "^2.0.0",
"qrcode": "^1.5.3"
```

Además se usan `jsonwebtoken`, `bcryptjs`, `cookie-parser` y `mysql2` en el flujo de auth.

---

## 9. Resumen para el reporte

- **Qué se implementó:** 2FA TOTP obligatorio para usuarios y administradores: registro/login no dan sesión completa hasta configurar 2FA; luego cada login requiere código de 6 dígitos.
- **Cómo:** Secreto TOTP generado con speakeasy, QR con qrcode; códigos verificados con ventana de 1 paso; secretos temporales en `mfa_pending` hasta confirmar con código.
- **Dónde:** Controlador `api/controllers/authController.js`, rutas en `api/routes/authRoutes.js`, tablas `users`, `admin_users` y `mfa_pending` (esquema en `db-init.sql`, migración en `migrations/001_totp_users.sql`).
- **Endpoints:** `POST /register`, `POST /login`, `POST /verify-2fa`, `GET /mfa/setup`, `POST /mfa/enable`, `POST /mfa/disable`.

Esta guía y los fragmentos de código permiten entender y describir en un reporte la implementación completa del 2FA en el proyecto.
