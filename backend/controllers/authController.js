const User = require('models/User');
const { generateJWT } = require('utils/tokenGenerator');
const { sendVerificationEmail, sendPasswordResetEmail } = require('utils/emailService');

class AuthController {
    static async register(req, res) {
        try {
            const { email, password, full_name, phone } = req.body;
            
            // Create user
            const user = await User.create({
                email,
                password,
                full_name,
                phone
            });
            
            // Send verification email
            await sendVerificationEmail(email, user.verification_token, full_name);
            
            // Log activity
            await User.logActivity(user.user_id, 'registration', req.ip, req.get('User-Agent'));
            
            res.status(201).json({
                success: true,
                message: 'Registration successful. Please check your email for verification.',
                data: {
                    user_id: user.user_id,
                    email: user.email,
                    full_name: user.full_name
                }
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async login(req, res) {
        try {
            const { email, password } = req.body;
            
            // Find user
            const user = await User.findByEmail(email);
            
            if (!user) {
                throw new Error('Invalid credentials');
            }
            
            // Check if email is verified
            if (!user.email_verified) {
                throw new Error('Please verify your email before logging in');
            }
            
            // Verify password
            const bcrypt = require('bcrypt');
            const isValid = await bcrypt.compare(password, user.password_hash);
            
            if (!isValid) {
                throw new Error('Invalid credentials');
            }
            
            // Generate JWT token
            const token = generateJWT(user.user_id, user.role);
            
            // Log activity
            await User.logActivity(user.user_id, 'login', req.ip, req.get('User-Agent'));
            
            res.json({
                success: true,
                message: 'Login successful',
                data: {
                    token,
                    user: {
                        user_id: user.user_id,
                        email: user.email,
                        full_name: user.full_name,
                        role: user.role,
                        avatar_url: user.avatar_url
                    }
                }
            });
        } catch (error) {
            res.status(401).json({
                success: false,
                message: error.message
            });
        }
    }

    static async logout(req, res) {
        try {
            if (req.user) {
                await User.logActivity(req.user.user_id, 'logout', req.ip, req.get('User-Agent'));
            }
            
            res.json({
                success: true,
                message: 'Logout successful'
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: 'Logout failed'
            });
        }
    }

    static async verifyEmail(req, res) {
        try {
            const { token } = req.query;
            
            if (!token) {
                throw new Error('Verification token is required');
            }
            
            const verified = await User.verifyEmail(token);
            
            if (!verified) {
                throw new Error('Invalid or expired verification token');
            }
            
            res.json({
                success: true,
                message: 'Email verified successfully. You can now login.'
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async requestPasswordReset(req, res) {
        try {
            const { email } = req.body;
            
            // Check if user exists
            const user = await User.findByEmail(email);
            
            if (!user) {
                // For security, don't reveal if user exists
                return res.json({
                    success: true,
                    message: 'If the email exists, a password reset link has been sent'
                });
            }
            
            // Create reset token
            const resetToken = await User.createPasswordResetToken(email);
            
            if (resetToken) {
                // Send reset email
                await sendPasswordResetEmail(email, resetToken, user.full_name);
            }
            
            res.json({
                success: true,
                message: 'If the email exists, a password reset link has been sent'
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: 'Password reset request failed'
            });
        }
    }

    static async resetPassword(req, res) {
        try {
            const { token, newPassword } = req.body;
            
            if (!token || !newPassword) {
                throw new Error('Token and new password are required');
            }
            
            // Validate password strength
            if (newPassword.length < 8) {
                throw new Error('Password must be at least 8 characters');
            }
            
            const reset = await User.resetPassword(token, newPassword);
            
            if (!reset) {
                throw new Error('Invalid or expired reset token');
            }
            
            res.json({
                success: true,
                message: 'Password reset successful. You can now login with your new password.'
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async getCurrentUser(req, res) {
        try {
            const user = await User.findById(req.user.user_id);
            
            if (!user) {
                throw new Error('User not found');
            }
            
            // Remove sensitive data
            delete user.password_hash;
            delete user.reset_token;
            delete user.reset_token_expiry;
            delete user.verification_token;
            
            res.json({
                success: true,
                data: { user }
            });
        } catch (error) {
            res.status(404).json({
                success: false,
                message: error.message
            });
        }
    }
}

module.exports = AuthController;