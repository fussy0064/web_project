const crypto = require('crypto');
const jwt = require('jsonwebtoken');

const generateVerificationToken = () => {
    return crypto.randomBytes(32).toString('hex');
};

const generateResetToken = () => {
    return crypto.randomBytes(32).toString('hex');
};

const generateJWT = (userId, role) => {
    return jwt.sign(
        { userId, role },
        process.env.JWT_SECRET,
        { expiresIn: '7d' }
    );
};

module.exports = {
    generateVerificationToken,
    generateResetToken,
    generateJWT
};