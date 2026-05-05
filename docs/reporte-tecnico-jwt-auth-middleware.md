# Reporte técnico — JWT, hasheo, middleware, HTTP y ofuscación

Documento generado a partir del código del repositorio `agencia-backend` (Node.js / Express).

---

## 1. JWT (JSON Web Tokens)

La API utiliza la librería **`jsonwebtoken`** (ver `api/package.json`). La configuración y el flujo están en `api/controllers/authController.js`.

### Comportamiento

- **Secreto**: `JWT_SECRET` vía variables de entorno, con valor por defecto si falta (`change-me-in-production`). En producción conviene exigir la variable y no depender del fallback.
- **Tokens**:
  - **Sesión principal** tras completar 2FA: `jwt.sign(payload, JWT_SECRET, { expiresIn: "7d" })`. Se envía en **cookie** `auth_token` (`httpOnly`, `secure` en producción, `sameSite: "lax"`, 7 días) y también en el JSON de respuesta como `token` en `verify2fa`.
  - **Tokens auxiliares**: `setupToken` (registro / configuración obligatoria de 2FA) y `tempToken` (entre login y `verify-2fa`), con expiraciones cortas (`SETUP_TOKEN_EXPIRY` = 15 m, `TEMP_TOKEN_EXPIRY` = 5 m).
- **Lectura**: cookie `auth_token` o cabecera `Authorization: Bearer <token>` (mismo criterio en `requireAuth` y en `me`).

### Fragmento relevante

```javascript
// api/controllers/authController.js — requireAuth
export function requireAuth(req, res, next) {
  const token =
    req.cookies?.[COOKIE_NAME] ||
    (req.headers.authorization || "").replace(/^Bearer\s+/i, "");
  if (!token) {
    return res.status(401).json({ message: "No autenticado" });
  }
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    req.authUser = decoded;
    next();
  } catch {
    return res.status(401).json({ message: "Token inválido o expirado" });
  }
}
```

**Resumen**: JWT firma y verifica identidad; el payload incluye `sub`, email/username, `full_name`, `role`, y en tokens temporales campos como `purpose`, `setupOnly`, etc.

---

## 2. Hasheo de contraseñas

Se usa **`bcryptjs`** con **`BCRYPT_ROUNDS = 10`**.

### Usuarios (`users`)

En **registro**, la contraseña en claro se sustituye por `bcrypt.hashSync(password, BCRYPT_ROUNDS)` y se persiste en `password_hash`.

### Login

Se valida con `bcrypt.compareSync(password, user.password_hash)` contra el hash almacenado.

### Administradores (`admin_users`)

La consulta lee la columna `password` y el código la trata como `password_hash` en la comparación (se asume hash compatible con bcrypt).

### Política en código

- Longitud mínima de contraseña: **6 caracteres** en registro (`register`).
- El esquema SQL define `users.password_hash` para el hash (ver `db-init.sql` / migraciones).

---

## 3. Middleware

### Globales (`api/app.js`)

| Middleware        | Función                                      |
|-----------------|-----------------------------------------------|
| `express.json()` | Parseo del cuerpo JSON                        |
| `cookie-parser` | Lectura de cookies (p. ej. `auth_token`)     |
| `cors`          | Origen desde `CORS_ORIGIN` o permisivo; `credentials: true` |

```javascript
app.use(express.json());
app.use(cookieParser());
app.use(cors({ origin: process.env.CORS_ORIGIN || true, credentials: true }));
```

### De autorización (`authController.js`)

| Función         | Comportamiento                                                |
|-----------------|----------------------------------------------------------------|
| `requireAuth`   | Exige JWT válido; adjunta `req.authUser`                     |
| `requireAdmin`  | Tras auth, exige `req.authUser?.role === "admin"` → 403 si no |

**Nota**: `requireAdmin` está definido pero, en el estado actual del repo, **no aparece encadenado en las rutas**; solo `requireAuth` se usa en rutas MFA (`authRoutes.js`). Para proteger rutas solo de administrador hay que añadir explícitamente `requireAuth` + `requireAdmin` en esas rutas.

---

## 4. POST, GET y métodos HTTP

Los routers se montan en `app.js` bajo prefijos (`/api/auth`, `/api/flights`, etc.). Patrón habitual: **GET** lecturas/búsquedas; **POST** creación y acciones; **PUT/DELETE** en recursos con CRUD completo.

### Autenticación (`api/routes/authRoutes.js`)

| Método | Ruta                | Middleware    |
|--------|---------------------|---------------|
| POST   | `/register`         | —             |
| POST   | `/login`            | —             |
| POST   | `/verify-2fa`       | —             |
| GET    | `/me`               | — (lee token) |
| POST   | `/logout`           | —             |
| GET    | `/mfa/setup`        | `requireAuth` |
| POST   | `/mfa/enable`       | `requireAuth` |
| POST   | `/mfa/disable`      | `requireAuth` |

### Dominio (resumen)

- **Vuelos, buses, hoteles**: GET listas, destinos, fechas, por id; POST crear; PUT/DELETE actualizar/borrar.
- **Habitaciones**: GET por hotel / por id; POST crear.
- **Ubicaciones**: GET listado.
- **Reservas**: POST crear reserva, booking, añadir hotel; GET detalles por id.

No hay un único middleware que distinga GET frente a POST: cada controlador implementa su lógica; la convención REST está repartida por archivo de rutas.

---

## 5. Ofuscación con `javascript-obfuscator`

En este proyecto **no hay integración** de `javascript-obfuscator`: no figura en `package.json`, ni scripts de build, ni referencias en el código.

Para un backend Express, la ofuscación del código servidor **no suele ser la medida principal de seguridad**: dificulta depuración y despliegue y **no sustituye** controles como secretos fuertes, HTTPS, validación de entrada o límites de tasa. Si se incorporara en el futuro, sería como paso de build opcional y con impacto en operaciones documentado.

---

## Conclusión

| Tema              | Estado en el proyecto                                              |
|-------------------|---------------------------------------------------------------------|
| JWT               | Implementado con cookies + Bearer, tokens de sesión y temporales   |
| Hasheo            | `bcryptjs` (10 rondas) para usuarios; admin vía columna `password`  |
| Middleware        | Express + CORS + cookies + `requireAuth` / `requireAdmin`           |
| GET/POST / REST   | Rutas modulares por dominio                                       |
| `requireAdmin`    | Definido; no aplicado a rutas en el código actual                   |
| Ofuscación JS     | No utilizada                                                        |

---

*Última revisión alineada con el código del repositorio `agencia-backend`.*
