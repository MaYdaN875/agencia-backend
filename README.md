# Programas necesarios

- GIT
- Docker
- Node.js
- Visual Studio Code

# Como ejecutar el proyecto

1. Clona el repositorio
```bash
    git clone https://github.com/MaYdaN875/agencia-backend.git
```

2. cd a la carpeta del proyecto

3. Ejecuta docker compose
```bash
    docker compose -f docker-compose.dev.yml up --build
 ```

 4. cd a la carpeta api
 ```bash
    cd api
  ```

5. Instalar las dependencias de Node
```bash
    npm install
```

6. Ejecutar nodemon 
```bash
    npm run dev
```

# Frontend PHP (carpeta dist)

El frontend en PHP consume los endpoints del backend Node. Asegúrate de que la API esté en marcha (paso 6).

- **URL del API**: por defecto el frontend llama a `http://localhost:3000/api`. Para cambiar la base URL (por ejemplo en Docker o otro puerto), define la variable de entorno `API_BASE_URL` o edita `dist/config/api.php` y la constante `API_BASE_URL`.
- **Orígenes**: `GET /api/locations`
- **Búsqueda**: `GET /api/flights`, `GET /api/buses`, `GET /api/hotels/search` con query params
- **Reservas**: `POST /api/reservations/booking`, `GET /api/reservations/:id`, `POST /api/reservations/:id/hotel`

# Autenticación: registro, login y 2FA TOTP (speakeasy + qrcode)

No se usa Firebase. El backend usa **speakeasy** (generar/validar códigos TOTP) y **qrcode** (QR para la app de autenticación).

## Usuarios normales (clientes)

- **Registro**: `dist/client/registro.php` — email, contraseña, nombre. Crea cuenta en la tabla `users`.
- **Login**: `dist/client/login.php` — email y contraseña. Si el usuario tiene 2FA activado, se pide el código de 6 dígitos.
- Tras login o registro se crea sesión y cookie en el mismo dominio.

## Admin

- **Login**: `dist/admin/login.php` — usuario y contraseña (tabla `admin_users`). Si el admin tiene 2FA activado, se pide el código.
- **Configurar 2FA**: una vez dentro del panel, **Configurar 2FA** en el menú → escanear QR con Google Authenticator (u otra app) → introducir código para activar.

## API de auth

- `POST /api/auth/register` — body: `{ email, password, full_name }`
- `POST /api/auth/login` — body: `{ email, password }` (usuarios) o `{ username, password }` (admin)
- `POST /api/auth/verify-2fa` — body: `{ tempToken, code }` (cuando el login devuelve `requiresMfa`)
- `GET /api/auth/me` — usuario actual (cookie o `Authorization: Bearer`)
- `GET /api/auth/mfa/setup` — genera secreto y QR (requiere auth)
- `POST /api/auth/mfa/enable` — activa 2FA con el código (requiere auth)
- `POST /api/auth/mfa/disable` — desactiva 2FA con el código (requiere auth)

## Base de datos

Si ya tenías la base creada sin 2FA, ejecuta la migración una vez:

```bash
mysql -u appuser -p mi_app < migrations/001_totp_users.sql
```

(En instalaciones nuevas, `db-init.sql` ya incluye la tabla `users`, columnas `mfa_secret`/`mfa_enabled` en `admin_users` y tabla `mfa_pending`.)

Variables en `api/.env`: `JWT_SECRET`, opcionalmente `APP_NAME`.