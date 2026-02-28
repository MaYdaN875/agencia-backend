import { Router } from 'express';
import * as LocationController from '../controllers/locationController.js';

const router = Router();
router.get('/', LocationController.getAll);
export default router;
