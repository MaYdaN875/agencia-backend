import jwt from "jsonwebtoken";
import bcrypt from "bcryptjs";
import speakeasy from "speakeasy";
import QRCode from "qrcode";
import { connection } from "../database/config.js";

const JWT_SECRET = process.env.JWT_SECRET || "change-me-in-production";
const COOKIE_NAME = "auth_token";
const COOKIE_OPTIONS = {
  httpOnly: true,
  secure: process.env.NODE_ENV === "production",
  sameSite: "lax",
  maxAge: 7 * 24 * 60 * 60 * 1000,
};
const APP_NAME = process.env.APP_NAME || "Agencia Viajes";
const BCRYPT_ROUNDS = 10;
const TEMP_TOKEN_EXPIRY = "5m"; // para flujo 2FA (código de 6 dígitos)
const SETUP_TOKEN_EXPIRY = "15m"; // solo para configurar 2FA la primera vez

/** Middleware: exige JWT válido y adjunta req.authUser */
export function requireAuth(req, res, next) {
  const token = req.cookies?.[COOKIE_NAME] || (req.headers.authorization || "").replace(/^Bearer\s+/i, "");
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

/** Solo admin (después de requireAuth) */
export function requireAdmin(req, res, next) {
  if (req.authUser?.role !== "admin") {
    return res.status(403).json({ message: "Acceso denegado" });
  }
  next();
}

/** Registro de usuarios normales. 2FA es obligatorio: no se da sesión hasta completar configuración. */
export const register = async (req, res) => {
  const { email, password, full_name } = req.body;
  if (!email || !password || !full_name) {
    return res.status(400).json({ message: "Faltan email, password o full_name" });
  }
  const emailNorm = String(email).trim().toLowerCase();
  if (password.length < 6) {
    return res.status(400).json({ message: "La contraseña debe tener al menos 6 caracteres" });
  }
  try {
    const [existing] = await connection.execute("SELECT id FROM users WHERE email = ?", [emailNorm]);
    if (existing.length > 0) {
      return res.status(409).json({ message: "Ya existe un usuario con ese correo" });
    }
    const password_hash = bcrypt.hashSync(password, BCRYPT_ROUNDS);
    await connection.execute(
      "INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)",
      [emailNorm, password_hash, String(full_name).trim()]
    );
    const [rows] = await connection.execute("SELECT id, email, full_name FROM users WHERE email = ?", [emailNorm]);
    const user = rows[0];
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
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: "Error al registrar" });
  }
};

/** Login: email (users) o username (admin). Si tiene 2FA devuelve requiresMfa + tempToken */
export const login = async (req, res) => {
  const { email, username, password } = req.body;
  const identifier = (email || username || "").trim();
  if (!identifier || !password) {
    return res.status(400).json({ message: "Faltan email o usuario y contraseña" });
  }
  try {
    const isEmail = identifier.includes("@");
    let user = null;
    let role = "user";

    if (isEmail) {
      const [rows] = await connection.execute(
        "SELECT id, email, password_hash, full_name, mfa_secret, mfa_enabled FROM users WHERE email = ?",
        [identifier.toLowerCase()]
      );
      if (rows.length) {
        user = { ...rows[0], username: rows[0].email };
        role = "user";
      }
    }

    if (!user) {
      const [rows] = await connection.execute(
        "SELECT id, username, password, full_name, email, mfa_secret, mfa_enabled FROM admin_users WHERE username = ?",
        [identifier]
      );
      if (rows.length) {
        user = {
          id: rows[0].id,
          email: rows[0].email,
          username: rows[0].username,
          full_name: rows[0].full_name,
          password_hash: rows[0].password,
          mfa_secret: rows[0].mfa_secret,
          mfa_enabled: !!rows[0].mfa_enabled,
        };
        role = "admin";
      }
    } else {
      user.mfa_enabled = !!user.mfa_enabled;
    }

    if (!user) {
      return res.status(401).json({ message: "Credenciales incorrectas" });
    }

    const valid = bcrypt.compareSync(password, user.password_hash);
    if (!valid) {
      return res.status(401).json({ message: "Credenciales incorrectas" });
    }

    if (!user.mfa_enabled || !user.mfa_secret) {
      const setupToken = jwt.sign(
        { sub: user.id, email: user.email || user.username, full_name: user.full_name, role, setupOnly: true },
        JWT_SECRET,
        { expiresIn: SETUP_TOKEN_EXPIRY }
      );
      return res.status(403).json({
        requiresMfaSetup: true,
        setupToken,
        message: "Debes configurar la autenticación en dos pasos (2FA) para poder iniciar sesión.",
      });
    }

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
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: "Error al iniciar sesión" });
  }
};

/** Verificar código 2FA y devolver JWT */
export const verify2fa = async (req, res) => {
  const { tempToken, code } = req.body;
  if (!tempToken || !code) {
    return res.status(400).json({ message: "Faltan tempToken y code" });
  }
  try {
    const decoded = jwt.verify(tempToken, JWT_SECRET);
    if (decoded.purpose !== "2fa" || !decoded.userId || !decoded.type) {
      return res.status(400).json({ message: "Token inválido" });
    }
    const { type, userId } = decoded;
    const table = type === "admin" ? "admin_users" : "users";
    const idCol = "id";
    const fields = table === "admin_users" ? "id, email, full_name, username, mfa_secret" : "id, email, full_name, mfa_secret";
    const [rows] = await connection.execute(
      `SELECT ${fields} FROM ${table} WHERE ${idCol} = ?`,
      [userId]
    );
    if (!rows.length || !rows[0].mfa_secret) {
      return res.status(401).json({ message: "Sesión expirada. Vuelve a iniciar sesión." });
    }
    const valid = speakeasy.totp.verify({
      secret: rows[0].mfa_secret,
      encoding: "base32",
      token: String(code).replace(/\s/g, ""),
      window: 1,
    });
    if (!valid) {
      return res.status(401).json({ message: "Código incorrecto o expirado" });
    }
    const u = rows[0];
    const payload = {
      sub: u.id,
      email: u.email || u.username,
      username: u.username || u.email,
      full_name: u.full_name,
      role: type,
    };
    const token = jwt.sign(payload, JWT_SECRET, { expiresIn: "7d" });
    res.cookie(COOKIE_NAME, token, COOKIE_OPTIONS);
    return res.json({
      user: { id: u.id, email: u.email || u.username, full_name: u.full_name, role: type },
      token,
    });
  } catch (err) {
    if (err.name === "TokenExpiredError") {
      return res.status(401).json({ message: "Sesión expirada. Vuelve a iniciar sesión." });
    }
    console.error(err);
    return res.status(500).json({ message: "Error al verificar" });
  }
};

/** Iniciar configuración 2FA: genera secreto y QR (requiere auth o setupToken) */
export const mfaSetup = async (req, res) => {
  const userId = req.authUser.sub;
  const role = req.authUser.role || "user";
  const table = role === "admin" ? "admin_users" : "users";
  try {
    const secret = speakeasy.generateSecret({
      name: `${APP_NAME} (${req.authUser.email || req.authUser.username})`,
      length: 20,
    });
    const otpauth = secret.otpauth_url;
    const qrCode = await QRCode.toDataURL(otpauth || "");

    await connection.execute("DELETE FROM mfa_pending WHERE user_type = ? AND user_id = ?", [
      role,
      userId,
    ]);
    await connection.execute(
      "INSERT INTO mfa_pending (user_type, user_id, secret) VALUES (?, ?, ?)",
      [role, userId, secret.base32]
    );

    return res.json({
      secret: secret.base32,
      qrCode,
      message: "Escanea el QR con tu app de autenticación (Google Authenticator, etc.) e introduce el código para activar 2FA",
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: "Error al generar código 2FA" });
  }
};

/** Activar 2FA tras verificar código (requiere auth o setupToken). Si era setupToken, devuelve JWT completo. */
export const mfaEnable = async (req, res) => {
  const { code } = req.body;
  const userId = req.authUser.sub;
  const role = req.authUser.role || "user";
  const isSetupOnly = !!req.authUser.setupOnly;
  if (!code) {
    return res.status(400).json({ message: "Falta el código" });
  }
  try {
    const [pending] = await connection.execute(
      "SELECT secret FROM mfa_pending WHERE user_type = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1",
      [role, userId]
    );
    if (!pending.length) {
      return res.status(400).json({ message: "No hay configuración 2FA pendiente. Solicita uno nuevo (login o registro)." });
    }
    const secret = pending[0].secret;
    const valid = speakeasy.totp.verify({
      secret,
      encoding: "base32",
      token: String(code).replace(/\s/g, ""),
      window: 1,
    });
    if (!valid) {
      return res.status(401).json({ message: "Código incorrecto o expirado" });
    }
    const table = role === "admin" ? "admin_users" : "users";
    await connection.execute(`UPDATE ${table} SET mfa_secret = ?, mfa_enabled = 1 WHERE id = ?`, [
      secret,
      userId,
    ]);
    await connection.execute("DELETE FROM mfa_pending WHERE user_type = ? AND user_id = ?", [
      role,
      userId,
    ]);

    if (isSetupOnly) {
      const fields = table === "admin_users" ? "id, email, full_name, username" : "id, email, full_name";
      const [rows] = await connection.execute(
        `SELECT ${fields} FROM ${table} WHERE id = ?`,
        [userId]
      );
      const u = rows[0];
      const payload = {
        sub: u.id,
        email: u.email || u.username,
        username: u.username || u.email,
        full_name: u.full_name,
        role,
      };
      const token = jwt.sign(payload, JWT_SECRET, { expiresIn: "7d" });
      res.cookie(COOKIE_NAME, token, COOKIE_OPTIONS);
      return res.json({
        message: "2FA activado. Cuenta lista.",
        token,
        user: { id: u.id, email: u.email || u.username, full_name: u.full_name, role },
      });
    }
    return res.json({ message: "2FA activado correctamente" });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: "Error al activar 2FA" });
  }
};

/** Desactivar 2FA (requiere auth + código actual) */
export const mfaDisable = async (req, res) => {
  const { code } = req.body;
  const { sub: userId, role } = req.authUser;
  if (!code) {
    return res.status(400).json({ message: "Falta el código" });
  }
  try {
    const table = role === "admin" ? "admin_users" : "users";
    const [rows] = await connection.execute(`SELECT mfa_secret FROM ${table} WHERE id = ?`, [userId]);
    if (!rows.length || !rows[0].mfa_secret) {
      return res.json({ message: "2FA no estaba activado" });
    }
    const valid = speakeasy.totp.verify({
      secret: rows[0].mfa_secret,
      encoding: "base32",
      token: String(code).replace(/\s/g, ""),
      window: 1,
    });
    if (!valid) {
      return res.status(401).json({ message: "Código incorrecto o expirado" });
    }
    await connection.execute(`UPDATE ${table} SET mfa_secret = NULL, mfa_enabled = 0 WHERE id = ?`, [
      userId,
    ]);
    return res.json({ message: "2FA desactivado" });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: "Error al desactivar 2FA" });
  }
};

/** Usuario actual (cookie o Bearer) */
export const me = (req, res) => {
  const token = req.cookies?.[COOKIE_NAME] || (req.headers.authorization || "").replace(/^Bearer\s+/i, "");
  if (!token) {
    return res.status(401).json({ message: "No autenticado" });
  }
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    return res.json({
      id: decoded.sub,
      email: decoded.email,
      username: decoded.username,
      full_name: decoded.full_name,
      role: decoded.role || "user",
    });
  } catch {
    return res.status(401).json({ message: "Token inválido o expirado" });
  }
};

/** Cerrar sesión */
export const logout = (req, res) => {
  res.clearCookie(COOKIE_NAME, { httpOnly: true, sameSite: "lax" });
  return res.json({ ok: true });
};
