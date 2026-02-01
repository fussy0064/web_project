const express = require('express');
const router = express.Router();
const AuthController = require('/controllers/authController');
const { authenticate } = require('middleware/auth');
const { registerValidation, loginValidation } = require('middleware/validation');

// Public routes
router.post('/register', registerValidation, AuthController.register);
router.post('/login', loginValidation, AuthController.login);
router.get('/verify-email', AuthController.verifyEmail);
router.post('/forgot-password', AuthController.requestPasswordReset);
router.post('/reset-password', AuthController.resetPassword);

// Protected routes
router.get('/me', authenticate, AuthController.getCurrentUser);
router.post('/logout', authenticate, AuthController.logout);

module.exports = router;