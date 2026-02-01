// Show login modal
function showLoginModal() {
    document.getElementById('loginModal').classList.add('active');
    clearFormErrors('loginForm');
}

// Show register modal
function showRegisterModal() {
    document.getElementById('registerModal').classList.add('active');
    clearFormErrors('registerForm');
}

// Show forgot password modal
function showForgotPasswordModal() {
    document.getElementById('forgotPasswordModal').classList.add('active');
    clearFormErrors('forgotPasswordForm');
}

// Clear form errors
function clearFormErrors(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.querySelectorAll('.error').forEach(error => {
            error.textContent = '';
        });
    }
}

// Validate password strength
function validatePassword(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    
    if (password.length < minLength) {
        return 'Password must be at least 8 characters long';
    }
    if (!hasUpperCase) {
        return 'Password must contain at least one uppercase letter';
    }
    if (!hasLowerCase) {
        return 'Password must contain at least one lowercase letter';
    }
    if (!hasNumbers) {
        return 'Password must contain at least one number';
    }
    return null;
}

// Handle login
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    clearFormErrors('loginForm');
    
    try {
        const response = await fetch(`${API_BASE}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            authToken = data.data.token;
            localStorage.setItem('token', authToken);
            currentUser = data.data.user;
            
            showToast('Login successful!', 'success');
            document.getElementById('loginModal').classList.remove('active');
            updateAuthUI();
            loadHomePage();
        } else {
            showToast(data.message, 'error');
            if (data.errors) {
                data.errors.forEach(error => {
                    const errorElement = document.getElementById(`${error.param}Error`);
                    if (errorElement) {
                        errorElement.textContent = error.msg;
                    }
                });
            }
        }
    } catch (error) {
        console.error('Login error:', error);
        showToast('Login failed. Please try again.', 'error');
    }
});

// Handle registration
document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const full_name = document.getElementById('registerName').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('registerConfirmPassword').value;
    const phone = document.getElementById('registerPhone').value.trim();
    
    clearFormErrors('registerForm');
    
    // Validate password
    const passwordError = validatePassword(password);
    if (passwordError) {
        document.getElementById('registerPasswordError').textContent = passwordError;
        return;
    }
    
    // Check password confirmation
    if (password !== confirmPassword) {
        document.getElementById('registerConfirmPasswordError').textContent = 'Passwords do not match';
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                full_name, 
                email, 
                password,
                phone: phone || undefined 
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            document.getElementById('registerModal').classList.remove('active');
            
            // Clear form
            document.getElementById('registerForm').reset();
        } else {
            showToast(data.message, 'error');
            if (data.errors) {
                data.errors.forEach(error => {
                    const errorElement = document.getElementById(`${error.param}Error`);
                    if (errorElement) {
                        errorElement.textContent = error.msg;
                    }
                });
            }
        }
    } catch (error) {
        console.error('Registration error:', error);
        showToast('Registration failed. Please try again.', 'error');
    }
});

// Handle forgot password
document.getElementById('forgotPasswordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('resetEmail').value.trim();
    
    clearFormErrors('forgotPasswordForm');
    
    try {
        const response = await fetch(`${API_BASE}/auth/forgot-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            document.getElementById('forgotPasswordModal').classList.remove('active');
            document.getElementById('forgotPasswordForm').reset();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Forgot password error:', error);
        showToast('Request failed. Please try again.', 'error');
    }
});

// Handle password reset
document.getElementById('resetPasswordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const token = document.getElementById('resetToken').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;
    
    clearFormErrors('resetPasswordForm');
    
    // Validate password
    const passwordError = validatePassword(newPassword);
    if (passwordError) {
        document.getElementById('newPasswordError').textContent = passwordError;
        return;
    }
    
    // Check password confirmation
    if (newPassword !== confirmPassword) {
        document.getElementById('confirmNewPasswordError').textContent = 'Passwords do not match';
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/auth/reset-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                token, 
                newPassword 
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            document.getElementById('resetPasswordModal').classList.remove('active');
            document.getElementById('resetPasswordForm').reset();
            
            // Show login modal
            showLoginModal();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Password reset error:', error);
        showToast('Password reset failed. Please try again.', 'error');
    }
});

// Email verification
async function verifyEmail(token) {
    try {
        const response = await fetch(`${API_BASE}/auth/verify-email?token=${token}`);
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Email verification error:', error);
        showToast('Email verification failed.', 'error');
    }
}

// Logout
async function logout() {
    try {
        const response = await fetch(`${API_BASE}/auth/logout`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        localStorage.removeItem('token');
        authToken = null;
        currentUser = null;
        
        showToast('Logged out successfully', 'success');
        updateAuthUI();
        loadHomePage();
    } catch (error) {
        console.error('Logout error:', error);
        showToast('Logout failed', 'error');
    }
}