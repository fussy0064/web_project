const express = require('express');
const router = express.Router();
const UserController = require('../controllers/userController');
const { authenticate, authorize } = require('../middleware/auth');
const { profileUpdateValidation } = require('../middleware/validation');

// All user routes require authentication
router.use(authenticate);

// Profile management (all authenticated users)
router.put('/profile', profileUpdateValidation, UserController.updateProfile);
router.put('/change-password', UserController.changePassword);
router.post('/upload-avatar', UserController.uploadAvatar);

// Admin/Manager only routes
router.get('/all', authorize('admin', 'manager'), UserController.getAllUsers);
router.get('/:id', authorize('admin', 'manager'), UserController.getUserById);
router.put('/:id/role', authorize('admin'), UserController.updateUserRole);
router.delete('/:id', authorize('admin'), UserController.deleteUser);

module.exports = router;