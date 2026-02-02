// API Base URL
const API_BASE = 'http://localhost:5000/api';

// State management
let currentUser = null;
let authToken = localStorage.getItem('token');

// DOM Elements
const authLinks = document.getElementById('authLinks');
const app = document.getElementById('app');
const toast = document.getElementById('toast');
const body = document.body;
const authPageContent = document.getElementById('authPageContent');

// Authentication form elements (for modal)
const loginText = document.querySelector("#loginRegisterModal .title-text .login");
const loginFormModal = document.querySelector("#loginRegisterModal form.login");
const loginBtnModal = document.querySelector("#loginRegisterModal label.login");
const signupBtnModal = document.querySelector("#loginRegisterModal label.signup");
const signupLinkModal = document.querySelector("#loginRegisterModal form .signup-link a");

// Authentication form elements (for auth page)
const loginTextPage = document.querySelector("#authPageContent .title-text .login");
const loginFormPage = document.querySelector("#authPageContent form.login");
const loginBtnPage = document.querySelector("#authPageContent label.login");
const signupBtnPage = document.querySelector("#authPageContent label.signup");
const signupLinkPage = document.querySelector("#authPageContent form .signup-link a");

// Initialize application
document.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
    setupEventListeners();
    setupAuthFormSliders();
    updatePageLayout();
});

// Check authentication status
async function checkAuthStatus() {
    if (authToken) {
        try {
            const response = await fetch(`${API_BASE}/auth/me`, {
                headers: {
                    'Authorization': `Bearer ${authToken}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                currentUser = data.data.user;
                updateAuthUI();
                updatePageLayout();
            } else {
                localStorage.removeItem('token');
                authToken = null;
                currentUser = null;
                updateAuthUI();
                updatePageLayout();
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            localStorage.removeItem('token');
            authToken = null;
            currentUser = null;
            updateAuthUI();
            updatePageLayout();
        }
    } else {
        updateAuthUI();
        updatePageLayout();
    }
}

// Update page layout based on auth status
function updatePageLayout() {
    if (currentUser) {
        // User is logged in - show main app
        body.classList.remove('auth-page');
        if (authPageContent) authPageContent.style.display = 'none';
        if (app) {
            app.style.display = 'block';
            loadHomePage();
        }
        // Show navbar
        document.querySelector('.navbar').style.display = 'flex';
    } else {
        // User is not logged in
        if (window.location.pathname === '/auth.html' || window.location.pathname === '/login.html') {
            // On dedicated auth page
            body.classList.add('auth-page');
            if (authPageContent) authPageContent.style.display = 'block';
            if (app) app.style.display = 'none';
            // Hide navbar on auth page
            document.querySelector('.navbar').style.display = 'none';
        } else {
            // On main app page
            body.classList.remove('auth-page');
            if (authPageContent) authPageContent.style.display = 'none';
            if (app) {
                app.style.display = 'block';
                loadHomePage();
            }
            // Show navbar
            document.querySelector('.navbar').style.display = 'flex';
        }
    }
}

// Update authentication UI in navbar
function updateAuthUI() {
    const authLinksDiv = document.getElementById('authLinks');
    
    if (!authLinksDiv) return;
    
    if (currentUser) {
        authLinksDiv.innerHTML = `
            <div class="dropdown">
                <button class="btn btn-secondary" id="userDropdown">
                    <i class="fas fa-user"></i> ${currentUser.full_name}
                    <i class="fas fa-caret-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="#" class="dropdown-item" id="profileLink">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    ${currentUser.role === 'admin' || currentUser.role === 'manager' ? 
                        `<a href="#" class="dropdown-item" id="adminLink">
                            <i class="fas fa-cog"></i> Admin Panel
                        </a>` : ''}
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item" id="logoutLink">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        `;
        
        document.getElementById('profileLink').addEventListener('click', (e) => {
            e.preventDefault();
            showProfileModal();
        });
        
        if (currentUser.role === 'admin' || currentUser.role === 'manager') {
            document.getElementById('adminLink').addEventListener('click', (e) => {
                e.preventDefault();
                showProfileModal('users');
            });
        }
        
        document.getElementById('logoutLink').addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    } else {
        authLinksDiv.innerHTML = `
            <button class="btn btn-secondary" id="loginBtn">Login</button>
            <button class="btn btn-primary" id="registerBtn">Register</button>
        `;
        
        document.getElementById('loginBtn').addEventListener('click', showLoginModal);
        document.getElementById('registerBtn').addEventListener('click', showRegisterModal);
    }
}

// Setup auth form sliders
function setupAuthFormSliders() {
    // Setup modal sliders
    if (signupBtnModal && loginBtnModal && signupLinkModal) {
        signupBtnModal.onclick = (() => {
            if (loginFormModal) loginFormModal.style.marginLeft = "-50%";
            if (loginText) loginText.style.marginLeft = "-50%";
        });
        
        loginBtnModal.onclick = (() => {
            if (loginFormModal) loginFormModal.style.marginLeft = "0%";
            if (loginText) loginText.style.marginLeft = "0%";
        });
        
        signupLinkModal.onclick = (() => {
            if (signupBtnModal) signupBtnModal.click();
            return false;
        });
    }
    
    // Setup auth page sliders
    if (signupBtnPage && loginBtnPage && signupLinkPage) {
        signupBtnPage.onclick = (() => {
            if (loginFormPage) loginFormPage.style.marginLeft = "-50%";
            if (loginTextPage) loginTextPage.style.marginLeft = "-50%";
        });
        
        loginBtnPage.onclick = (() => {
            if (loginFormPage) loginFormPage.style.marginLeft = "0%";
            if (loginTextPage) loginTextPage.style.marginLeft = "0%";
        });
        
        signupLinkPage.onclick = (() => {
            if (signupBtnPage) signupBtnPage.click();
            return false;
        });
    }
}

// Setup event listeners
function setupEventListeners() {
    // Menu toggle for mobile
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        });
    }
    
    // Modal close buttons
    document.querySelectorAll('.modal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            closeBtn.closest('.modal').classList.remove('active');
        });
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Forgot password links
    document.getElementById('forgotPasswordLink')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('loginRegisterModal').classList.remove('active');
        document.getElementById('forgotPasswordModal').classList.add('active');
    });
    
    document.getElementById('forgotPasswordLinkPage')?.addEventListener('click', (e) => {
        e.preventDefault();
        showForgotPasswordModal();
    });
    
    document.getElementById('showLoginFromReset')?.addEventListener('click', (e) => {
        e.preventDefault();
        document.getElementById('forgotPasswordModal').classList.remove('active');
        document.getElementById('loginRegisterModal').classList.add('active');
    });
    
    // Check for reset token in URL
    const urlParams = new URLSearchParams(window.location.search);
    const resetToken = urlParams.get('resetToken');
    const verifyToken = urlParams.get('verifyToken');
    
    if (resetToken) {
        document.getElementById('resetToken').value = resetToken;
        document.getElementById('resetPasswordModal').classList.add('active');
    }
    
    if (verifyToken) {
        verifyEmail(verifyToken);
    }
    
    // Form submissions
    setupFormHandlers();
}

// Setup form handlers
function setupFormHandlers() {
    // Modal login form
    const loginFormModal = document.querySelector('#loginRegisterModal form.login');
    if (loginFormModal) {
        loginFormModal.addEventListener('submit', (e) => handleLogin(e, 'modal'));
    }
    
    // Auth page login form
    const loginFormPage = document.querySelector('#authPageContent form.login');
    if (loginFormPage) {
        loginFormPage.addEventListener('submit', (e) => handleLogin(e, 'page'));
    }
    
    // Modal register form
    const registerFormModal = document.querySelector('#loginRegisterModal form.signup');
    if (registerFormModal) {
        registerFormModal.addEventListener('submit', (e) => handleRegister(e, 'modal'));
    }
    
    // Auth page register form
    const registerFormPage = document.querySelector('#authPageContent form.signup');
    if (registerFormPage) {
        registerFormPage.addEventListener('submit', (e) => handleRegister(e, 'page'));
    }
    
    // Forgot password form
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', handleForgotPassword);
    }
    
    // Reset password form
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', handlePasswordReset);
    }
}

// Show login modal (for main app)
function showLoginModal() {
    document.getElementById('loginRegisterModal').classList.add('active');
    // Reset to login tab
    if (loginBtnModal) loginBtnModal.click();
}

// Show register modal (for main app)
function showRegisterModal() {
    document.getElementById('loginRegisterModal').classList.add('active');
    // Reset to register tab
    if (signupBtnModal) signupBtnModal.click();
}

// Show forgot password modal
function showForgotPasswordModal() {
    if (document.querySelector('.auth-page')) {
        // On auth page, open modal
        document.getElementById('forgotPasswordModal').classList.add('active');
    } else {
        // On main app, close login modal and open forgot password
        document.getElementById('loginRegisterModal').classList.remove('active');
        document.getElementById('forgotPasswordModal').classList.add('active');
    }
}

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
    if (!toast) {
        console.log(`${type.toUpperCase()}: ${message}`);
        return;
    }
    
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, duration);
}

// Load home page
function loadHomePage() {
    if (!app) return;
    
    app.innerHTML = `
        <section class="hero">
            <h1>TechSphere Electronics</h1>
            <h3>Not Biggest But the Best</h3>
            <p>Your one-stop shop for all electronic needs</p>
            ${!currentUser ? `
                <div class="hero-buttons">
                    <button class="btn btn-primary btn-large" id="heroLogin">Login</button>
                    <button class="btn btn-secondary btn-large" id="heroRegister">Register</button>
                </div>
            ` : `
                <div class="hero-buttons">
                    <button class="btn btn-primary btn-large" onclick="window.location.href='/products.html'">
                        <i class="fas fa-shopping-cart"></i> Shop Now
                    </button>
                    <button class="btn btn-secondary btn-large" onclick="showProfileModal()">
                        <i class="fas fa-user"></i> My Account
                    </button>
                </div>
            `}
        </section>
        
        <section class="features">
            <div class="feature-card">
                <i class="fas fa-laptop fa-3x"></i>
                <h3>Wide Product Range</h3>
                <p>From laptops to smartphones, we have it all</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt fa-3x"></i>
                <h3>Secure Shopping</h3>
                <p>Your data is protected with enterprise-grade security</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shipping-fast fa-3x"></i>
                <h3>Fast Delivery</h3>
                <p>Get your products delivered As soon as possible</p>
            </div>
        </section>
    `;
    
    if (!currentUser) {
        document.getElementById('heroLogin')?.addEventListener('click', showLoginModal);
        document.getElementById('heroRegister')?.addEventListener('click', showRegisterModal);
    }
}

// Handle login
async function handleLogin(event, source = 'modal') {
    event.preventDefault();
    
    let email, password;
    
    if (source === 'modal') {
        email = document.querySelector('#loginRegisterModal #loginEmail')?.value.trim();
        password = document.querySelector('#loginRegisterModal #loginPassword')?.value;
    } else {
        email = document.querySelector('#authPageContent #loginEmailPage')?.value.trim();
        password = document.querySelector('#authPageContent #loginPasswordPage')?.value;
    }
    
    if (!email || !password) {
        showToast('Please fill in all fields', 'error');
        return;
    }
    
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
            
            // Close modals
            document.getElementById('loginRegisterModal')?.classList.remove('active');
            document.querySelector('.modal.active')?.classList.remove('active');
            
            // Update UI
            updateAuthUI();
            updatePageLayout();
            
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showToast('Login failed. Please try again.', 'error');
    }
}

// Handle registration
async function handleRegister(event, source = 'modal') {
    event.preventDefault();
    
    let full_name, email, password, confirmPassword;
    
    if (source === 'modal') {
        full_name = document.querySelector('#loginRegisterModal #registerName')?.value.trim();
        email = document.querySelector('#loginRegisterModal #registerEmail')?.value.trim();
        password = document.querySelector('#loginRegisterModal #registerPassword')?.value;
        confirmPassword = document.querySelector('#loginRegisterModal #registerConfirmPassword')?.value;
    } else {
        full_name = document.querySelector('#authPageContent #registerNamePage')?.value.trim();
        email = document.querySelector('#authPageContent #registerEmailPage')?.value.trim();
        password = document.querySelector('#authPageContent #registerPasswordPage')?.value;
        confirmPassword = document.querySelector('#authPageContent #registerConfirmPasswordPage')?.value;
    }
    
    if (!full_name || !email || !password || !confirmPassword) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        return;
    }
    
    // Validate password strength
    if (password.length < 8) {
        showToast('Password must be at least 8 characters', 'error');
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
                password
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            
            if (source === 'modal') {
                // Close modal and reset form
                document.getElementById('loginRegisterModal')?.classList.remove('active');
                event.target.reset();
                // Switch to login
                if (loginBtnModal) loginBtnModal.click();
            } else {
                // On auth page, switch to login tab
                if (loginBtnPage) loginBtnPage.click();
                event.target.reset();
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showToast('Registration failed. Please try again.', 'error');
    }
}

// Handle forgot password
async function handleForgotPassword(event) {
    event.preventDefault();
    
    const email = document.getElementById('resetEmail')?.value.trim();
    
    if (!email) {
        showToast('Please enter your email', 'error');
        return;
    }
    
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
            event.target.reset();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Forgot password error:', error);
        showToast('Request failed. Please try again.', 'error');
    }
}

// Handle password reset
async function handlePasswordReset(event) {
    event.preventDefault();
    
    const token = document.getElementById('resetToken')?.value;
    const newPassword = document.getElementById('newPassword')?.value;
    const confirmPassword = document.getElementById('confirmNewPassword')?.value;
    
    if (!newPassword || !confirmPassword) {
        showToast('Please fill in all fields', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showToast('Passwords do not match', 'error');
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
            event.target.reset();
            
            // Show login
            if (document.querySelector('.auth-page')) {
                if (loginBtnPage) loginBtnPage.click();
            } else {
                showLoginModal();
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Password reset error:', error);
        showToast('Password reset failed. Please try again.', 'error');
    }
}

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
        if (authToken) {
            await fetch(`${API_BASE}/auth/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authToken}`
                }
            });
        }
        
        localStorage.removeItem('token');
        authToken = null;
        currentUser = null;
        
        showToast('Logged out successfully', 'success');
        updateAuthUI();
        updatePageLayout();
    } catch (error) {
        console.error('Logout error:', error);
        showToast('Logout failed', 'error');
    }
}

// Profile modal functions (you'll need to implement these)
function showProfileModal(activeTab = 'profile') {
    if (!currentUser) return;
    // Implement profile modal logic here
    console.log('Show profile modal');
}

function populateProfileForm(user) {
    // Implement profile form population
}

function setupProfileTabs(activeTab) {
    // Implement profile tabs
}

function loadUserActivity() {
    // Implement activity loading
}