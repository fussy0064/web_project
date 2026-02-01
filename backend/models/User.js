const { pool } = require('../config/database');
const bcrypt = require('bcrypt');
const { generateVerificationToken, generateResetToken } = require('../utils/tokenGenerator');

class User {
    static async create(userData) {
        const { email, password, full_name, phone } = userData;
        const verificationToken = generateVerificationToken();
        
        const passwordHash = await bcrypt.hash(password, 10);
        
        const [result] = await pool.execute(
            'CALL RegisterUser(?, ?, ?, ?)',
            [email, passwordHash, full_name, verificationToken]
        );
        
        return {
            user_id: result[0][0].user_id,
            email,
            full_name,
            verification_token: verificationToken
        };
    }

    static async findByEmail(email) {
        const [users] = await pool.execute(
            `SELECT u.*, up.* FROM users u 
             LEFT JOIN user_profiles up ON u.user_id = up.user_id 
             WHERE u.email = ?`,
            [email]
        );
        return users[0];
    }

    static async findById(userId) {
        const [users] = await pool.execute(
            `SELECT u.*, up.* FROM users u 
             LEFT JOIN user_profiles up ON u.user_id = up.user_id 
             WHERE u.user_id = ?`,
            [userId]
        );
        return users[0];
    }

    static async verifyEmail(token) {
        const [result] = await pool.execute(
            'UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE verification_token = ?',
            [token]
        );
        return result.affectedRows > 0;
    }

    static async createPasswordResetToken(email) {
        const resetToken = generateResetToken();
        const expiry = new Date(Date.now() + 3600000); // 1 hour
        
        const [result] = await pool.execute(
            'UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?',
            [resetToken, expiry, email]
        );
        
        if (result.affectedRows > 0) {
            return resetToken;
        }
        return null;
    }

    static async resetPassword(token, newPassword) {
        const passwordHash = await bcrypt.hash(newPassword, 10);
        
        const [result] = await pool.execute(
            `UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL 
             WHERE reset_token = ? AND reset_token_expiry > NOW()`,
            [passwordHash, token]
        );
        
        return result.affectedRows > 0;
    }

    static async updateProfile(userId, updateData) {
        const { full_name, phone, date_of_birth, gender, address, city, state, country, postal_code, bio } = updateData;
        
        // Update users table
        if (full_name || phone) {
            const updates = [];
            const values = [];
            
            if (full_name) {
                updates.push('full_name = ?');
                values.push(full_name);
            }
            if (phone) {
                updates.push('phone = ?');
                values.push(phone);
            }
            
            if (updates.length > 0) {
                values.push(userId);
                await pool.execute(
                    `UPDATE users SET ${updates.join(', ')} WHERE user_id = ?`,
                    values
                );
            }
        }
        
        // Update user_profiles table
        const profileUpdates = [];
        const profileValues = [];
        
        if (date_of_birth !== undefined) {
            profileUpdates.push('date_of_birth = ?');
            profileValues.push(date_of_birth);
        }
        if (gender !== undefined) {
            profileUpdates.push('gender = ?');
            profileValues.push(gender);
        }
        if (address !== undefined) {
            profileUpdates.push('address = ?');
            profileValues.push(address);
        }
        if (city !== undefined) {
            profileUpdates.push('city = ?');
            profileValues.push(city);
        }
        if (state !== undefined) {
            profileUpdates.push('state = ?');
            profileValues.push(state);
        }
        if (country !== undefined) {
            profileUpdates.push('country = ?');
            profileValues.push(country);
        }
        if (postal_code !== undefined) {
            profileUpdates.push('postal_code = ?');
            profileValues.push(postal_code);
        }
        if (bio !== undefined) {
            profileUpdates.push('bio = ?');
            profileValues.push(bio);
        }
        
        if (profileUpdates.length > 0) {
            profileValues.push(userId);
            await pool.execute(
                `UPDATE user_profiles SET ${profileUpdates.join(', ')} WHERE user_id = ?`,
                profileValues
            );
        }
        
        return this.findById(userId);
    }

    static async changePassword(userId, currentPassword, newPassword) {
        const user = await this.findById(userId);
        
        if (!user) {
            throw new Error('User not found');
        }
        
        const isValid = await bcrypt.compare(currentPassword, user.password_hash);
        if (!isValid) {
            throw new Error('Current password is incorrect');
        }
        
        const newPasswordHash = await bcrypt.hash(newPassword, 10);
        
        await pool.execute(
            'UPDATE users SET password_hash = ? WHERE user_id = ?',
            [newPasswordHash, userId]
        );
        
        return true;
    }

    static async logActivity(userId, activityType, ipAddress, userAgent) {
        await pool.execute(
            'INSERT INTO user_activity_logs (user_id, activity_type, ip_address, user_agent) VALUES (?, ?, ?, ?)',
            [userId, activityType, ipAddress, userAgent]
        );
    }

    static async getAllUsers(page = 1, limit = 10, role = null) {
        const offset = (page - 1) * limit;
        let query = `SELECT u.user_id, u.email, u.full_name, u.role, u.email_verified, 
                            u.created_at, up.city, up.country
                     FROM users u
                     LEFT JOIN user_profiles up ON u.user_id = up.user_id`;
        let countQuery = 'SELECT COUNT(*) as total FROM users';
        const values = [];
        const countValues = [];
        
        if (role) {
            query += ' WHERE u.role = ?';
            countQuery += ' WHERE role = ?';
            values.push(role);
            countValues.push(role);
        }
        
        query += ' ORDER BY u.created_at DESC LIMIT ? OFFSET ?';
        values.push(limit, offset);
        
        const [users] = await pool.execute(query, values);
        const [[{ total }]] = await pool.execute(countQuery, countValues);
        
        return {
            users,
            pagination: {
                total,
                page,
                limit,
                pages: Math.ceil(total / limit)
            }
        };
    }

    static async updateUserRole(userId, role) {
        const validRoles = ['customer', 'admin', 'manager'];
        if (!validRoles.includes(role)) {
            throw new Error('Invalid role');
        }
        
        const [result] = await pool.execute(
            'UPDATE users SET role = ? WHERE user_id = ?',
            [role, userId]
        );
        
        return result.affectedRows > 0;
    }
}

module.exports = User;