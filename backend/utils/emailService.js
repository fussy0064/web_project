const { sendEmail } = require('../config/mail');

const sendVerificationEmail = async (email, token, fullName) => {
    const verificationLink = `${process.env.FRONTEND_URL}/verify-email?token=${token}`;
    
    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background-color: #f8f9fa; }
                .button { display: inline-block; padding: 12px 24px; background-color: #007bff; 
                         color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #6c757d; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to TechSphere Electronics!</h1>
                </div>
                <div class="content">
                    <h2>Hi ${fullName},</h2>
                    <p>Thank you for registering with TechSphere Electronics. Please verify your email address by clicking the button below:</p>
                    <div style="text-align: center;">
                        <a href="${verificationLink}" class="button">Verify Email Address</a>
                    </div>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p>${verificationLink}</p>
                    <p>This verification link will expire in 24 hours.</p>
                    <p>If you didn't create an account, you can safely ignore this email.</p>
                </div>
                <div class="footer">
                    <p>&copy; ${new Date().getFullYear()} TechSphere Electronics. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    `;

    return await sendEmail(email, 'Verify Your Email Address - TechSphere Electronics', html);
};

const sendPasswordResetEmail = async (email, token, fullName) => {
    const resetLink = `${process.env.FRONTEND_URL}/reset-password?token=${token}`;
    
    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background-color: #f8f9fa; }
                .button { display: inline-block; padding: 12px 24px; background-color: #dc3545; 
                         color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #6c757d; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Password Reset Request</h1>
                </div>
                <div class="content">
                    <h2>Hi ${fullName},</h2>
                    <p>We received a request to reset your password for your TechSphere Electronics account.</p>
                    <p>Click the button below to reset your password:</p>
                    <div style="text-align: center;">
                        <a href="${resetLink}" class="button">Reset Password</a>
                    </div>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p>${resetLink}</p>
                    <p>This password reset link will expire in 1 hour.</p>
                    <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
                </div>
                <div class="footer">
                    <p>&copy; ${new Date().getFullYear()} TechSphere Electronics. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    `;

    return await sendEmail(email, 'Password Reset Request - TechSphere Electronics', html);
};

module.exports = {
    sendVerificationEmail,
    sendPasswordResetEmail
};