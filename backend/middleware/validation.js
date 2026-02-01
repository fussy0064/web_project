const { body, validationResult } = require('express-validator');
const { pool } = require('../config/database');

const validate = (validations) => {
    return async (req, res, next) => {
        await Promise.all(validations.map(validation => validation.run(req)));

        const errors = validationResult(req);
        if (errors.isEmpty()) {
            return next();
        }

        res.status(400).json({
            success: false,
            errors: errors.array()
        });
    };
};

const registerValidation = validate([
    body('email')
        .isEmail().withMessage('Please enter a valid email')
        .custom(async email => {
            const [users] = await pool.execute(
                'SELECT user_id FROM users WHERE email = ?',
                [email]
            );
            if (users.length > 0) {
                throw new Error('Email already registered');
            }
            return true;
        }),
    body('password')
        .isLength({ min: 8 }).withMessage('Password must be at least 8 characters')
        .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
        .withMessage('Password must contain uppercase, lowercase, and number'),
    body('full_name')
        .trim()
        .notEmpty().withMessage('Full name is required')
        .isLength({ min: 2, max: 100 }).withMessage('Name must be 2-100 characters')
]);

const loginValidation = validate([
    body('email').isEmail().withMessage('Please enter a valid email'),
    body('password').notEmpty().withMessage('Password is required')
]);

const profileUpdateValidation = validate([
    body('full_name').optional().trim().isLength({ min: 2, max: 100 }),
    body('phone').optional().isMobilePhone().withMessage('Invalid phone number'),
    body('date_of_birth').optional().isDate().withMessage('Invalid date format'),
    body('gender').optional().isIn(['male', 'female', 'other'])
]);

module.exports = {
    registerValidation,
    loginValidation,
    profileUpdateValidation
};