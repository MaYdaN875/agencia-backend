import { Router } from "express";
import * as Auth from "../controllers/authController.js";

const router = Router();

router.post("/register", Auth.register);
router.post("/login", Auth.login);
router.post("/verify-2fa", Auth.verify2fa);
router.get("/me", Auth.me);
router.post("/logout", Auth.logout);

router.get("/mfa/setup", Auth.requireAuth, Auth.mfaSetup);
router.post("/mfa/enable", Auth.requireAuth, Auth.mfaEnable);
router.post("/mfa/disable", Auth.requireAuth, Auth.mfaDisable);

export default router;
