import { Router } from 'express';
import * as BusController from '../controllers/busController.js';

const router = Router();

router.get('/', BusController.getAll);
router.get('/:id', BusController.getById);
router.post('/', BusController.create);
router.put('/:id', BusController.update);
router.delete('/:id', BusController.remove);

export default router;
