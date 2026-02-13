document.addEventListener('DOMContentLoaded', function () {

    // Base URL for API
    const API_URL = '/Electronics_Ordering_System/web_project/public/api/auth';

    /* -------------------------------------------------------------------------- */
    /*                                LOGIN LOGIC                                 */
    /* -------------------------------------------------------------------------- */
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError = document.getElementById('email-error');
        const passwordError = document.getElementById('password-error');

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            // Clear previous errors
            if (emailError) emailError.textContent = '';
            if (passwordError) passwordError.textContent = '';
            emailInput.style.borderColor = '#e0e0e0';
            passwordInput.style.borderColor = '#e0e0e0';

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="loading"></span> Logging in...';
            submitBtn.disabled = true;

            fetch(`${API_URL}/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    if (res.status === 200) {
                        // Success
                        localStorage.setItem('user', JSON.stringify(res.body.user));
                        alert('Login successful!');

                        // Redirect based on role
                        const userRole = res.body.user.role;
                        if (userRole === 'admin') {
                            window.location.href = 'admin.html';
                        } else if (userRole === 'seller') {
                            window.location.href = '/Electronics_Ordering_System/web_project/public/seller_dashboard.html';
                        } else {
                            window.location.href = 'index.html';
                        }
                    } else {
                        // Error
                        if (passwordError) passwordError.textContent = res.body.message || 'Login failed';
                        passwordInput.style.borderColor = '#e74c3c';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    if (passwordError) passwordError.textContent = 'An error occurred. Please try again.';
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        });
    }

    /* -------------------------------------------------------------------------- */
    /*                             REGISTRATION LOGIC                             */
    /* -------------------------------------------------------------------------- */
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const userTypeInput = document.getElementById('user_type');
        const userTypeOptions = document.querySelectorAll('.type-option');

        // Handle User Type Selection
        if (userTypeOptions.length > 0) {
            userTypeOptions.forEach(option => {
                option.addEventListener('click', () => {
                    userTypeOptions.forEach(opt => opt.classList.remove('active'));
                    option.classList.add('active');
                    const type = option.getAttribute('data-type');
                    if (userTypeInput) userTypeInput.value = type;
                });
            });
        }

        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const address = document.getElementById('address').value.trim();
            const role = userTypeInput ? userTypeInput.value : 'customer';

            // Basic validation
            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.innerHTML = '<span class="loading"></span> Creating Account...';
            submitBtn.disabled = true;

            const userData = {
                username: `${firstName} ${lastName}`,
                email: email,
                password: password
            };

            fetch(`${API_URL}/register.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userData)
            })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    if (res.status === 201) {
                        alert('Registration successful! Please login.');
                        window.location.href = 'login.html';
                    } else {
                        alert(res.body.message || 'Registration failed.');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        });
    }
});