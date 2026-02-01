const User = require('../models/User');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

// Configure multer for file upload
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        const uploadDir = 'public/uploads/avatars';
        if (!fs.existsSync(uploadDir)) {
            fs.mkdirSync(uploadDir, { recursive: true });
        }
        cb(null, uploadDir);
    },
    filename: (req, file, cb) => {
        const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
        cb(null, `avatar-${req.user.user_id}-${uniqueSuffix}${path.extname(file.originalname)}`);
    }
});

const fileFilter = (req, file, cb) => {
    const allowedTypes = /jpeg|jpg|png|gif/;
    const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
    const mimetype = allowedTypes.test(file.mimetype);
    
    if (mimetype && extname) {
        return cb(null, true);
    } else {
        cb(new Error('Only image files are allowed'));
    }
};

const upload = multer({
    storage,
    limits: { fileSize: 5 * 1024 * 1024 }, // 5MB limit
    fileFilter
}).single('avatar');

class UserController {
    static async updateProfile(req, res) {
        try {
            const userId = req.user.user_id;
            const updateData = req.body;
            
            // Handle file upload if present
            if (req.file) {
                updateData.avatar_url = `/uploads/avatars/${req.file.filename}`;
                
                // Delete old avatar if exists
                const user = await User.findById(userId);
                if (user.avatar_url && user.avatar_url.startsWith('/uploads/avatars/')) {
                    const oldPath = `public${user.avatar_url}`;
                    if (fs.existsSync(oldPath)) {
                        fs.unlinkSync(oldPath);
                    }
                }
            }
            
            const updatedUser = await User.updateProfile(userId, updateData);
            
            // Remove sensitive data
            delete updatedUser.password_hash;
            delete updatedUser.reset_token;
            delete updatedUser.reset_token_expiry;
            delete updatedUser.verification_token;
            
            // Log activity
            await User.logActivity(userId, 'profile_update', req.ip, req.get('User-Agent'));
            
            res.json({
                success: true,
                message: 'Profile updated successfully',
                data: { user: updatedUser }
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async changePassword(req, res) {
        try {
            const userId = req.user.user_id;
            const { currentPassword, newPassword } = req.body;
            
            await User.changePassword(userId, currentPassword, newPassword);
            
            // Log activity
            await User.logActivity(userId, 'password_change', req.ip, req.get('User-Agent'));
            
            res.json({
                success: true,
                message: 'Password changed successfully'
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async getAllUsers(req, res) {
        try {
            const { page = 1, limit = 10, role } = req.query;
            
            const result = await User.getAllUsers(parseInt(page), parseInt(limit), role);
            
            res.json({
                success: true,
                data: result
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: error.message
            });
        }
    }

    static async getUserById(req, res) {
        try {
            const userId = req.params.id;
            const user = await User.findById(userId);
            
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

    static async updateUserRole(req, res) {
        try {
            const userId = req.params.id;
            const { role } = req.body;
            
            const updated = await User.updateUserRole(userId, role);
            
            if (!updated) {
                throw new Error('User not found');
            }
            
            res.json({
                success: true,
                message: `User role updated to ${role}`
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async deleteUser(req, res) {
        try {
            const userId = req.params.id;
            
            // Prevent self-deletion
            if (userId == req.user.user_id) {
                throw new Error('Cannot delete your own account');
            }
            
            const [result] = await pool.execute(
                'DELETE FROM users WHERE user_id = ?',
                [userId]
            );
            
            if (result.affectedRows === 0) {
                throw new Error('User not found');
            }
            
            res.json({
                success: true,
                message: 'User deleted successfully'
            });
        } catch (error) {
            res.status(400).json({
                success: false,
                message: error.message
            });
        }
    }

    static async uploadAvatar(req, res) {
        upload(req, res, async (err) => {
            try {
                if (err) {
                    throw new Error(err.message);
                }
                
                if (!req.file) {
                    throw new Error('Please upload an image file');
                }
                
                const avatarUrl = `/uploads/avatars/${req.file.filename}`;
                
                // Update user avatar
                const [result] = await pool.execute(
                    'UPDATE users SET avatar_url = ? WHERE user_id = ?',
                    [avatarUrl, req.user.user_id]
                );
                
                if (result.affectedRows === 0) {
                    throw new Error('User not found');
                }
                
                // Delete old avatar if exists
                const user = await User.findById(req.user.user_id);
                if (user.avatar_url && user.avatar_url.startsWith('/uploads/avatars/')) {
                    const oldPath = `public${user.avatar_url}`;
                    if (fs.existsSync(oldPath)) {
                        fs.unlinkSync(oldPath);
                    }
                }
                
                res.json({
                    success: true,
                    message: 'Avatar uploaded successfully',
                    data: { avatar_url: avatarUrl }
                });
            } catch (error) {
                res.status(400).json({
                    success: false,
                    message: error.message
                });
            }
        });
    }
}

module.exports = UserController;