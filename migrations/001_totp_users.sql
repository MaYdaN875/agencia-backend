-- Migración: tabla users (registro de usuarios normales) y 2FA TOTP para admin_users
-- Ejecutar UNA VEZ en bases creadas con el esquema antiguo (sin mfa_* ni users):
--   mysql -u appuser -p mi_app < migrations/001_totp_users.sql
-- Si admin_users ya tiene mfa_secret, comentar o omitir los dos ALTER de abajo.

-- Tabla de usuarios normales (registro y login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    mfa_secret VARCHAR(255) NULL,
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2FA TOTP para admin_users (ejecutar solo si las columnas no existen)
ALTER TABLE admin_users ADD COLUMN mfa_secret VARCHAR(255) NULL;
ALTER TABLE admin_users ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0;

-- Secretos TOTP pendientes (entre mfa/setup y mfa/enable)
CREATE TABLE IF NOT EXISTS mfa_pending (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('user', 'admin') NOT NULL,
    user_id INT NOT NULL,
    secret VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_type, user_id)
);
