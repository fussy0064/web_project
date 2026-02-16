// profile.js
const API_BASE = '/Electronics_Ordering_System/web_project/public/api';

document.addEventListener('DOMContentLoaded', function () {
    // Check authentication
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        alert('Please login to view your profile');
        window.location.href = 'login.html';
        return;
    }

    // Load profile data
    loadProfile();
    loadOrders();

    // Logout handler
    document.getElementById('logoutBtn').addEventListener('click', function () {
        fetch(`${API_BASE}/auth/logout.php`)
            .then(() => {
                localStorage.removeItem('user');
                localStorage.removeItem('cart');
                window.location.href = 'index.html';
            })
            .catch(() => {
                // Force logout even if API fails
                localStorage.removeItem('user');
                localStorage.removeItem('cart');
                window.location.href = 'index.html';
            });
    });
});

function loadProfile() {
    fetch(`${API_BASE}/user/profile.php`)
        .then(res => {
            if (res.status === 401) {
                window.location.href = 'login.html';
                throw new Error('Unauthorized');
            }
            return res.json();
        })
        .then(user => {
            // Update localStorage with fresh data
            localStorage.setItem('user', JSON.stringify(user));

            // Set profile avatar
            document.getElementById('profileAvatar').textContent = (user.username || 'U').charAt(0).toUpperCase();

            // Set profile name and email
            document.getElementById('profileName').textContent = user.username;
            document.getElementById('profileEmail').textContent = user.email;

            // Set role
            const roleMap = {
                'customer': 'Customer',
                'seller': 'Seller',
                'admin': 'Administrator'
            };
            document.getElementById('profileRole').textContent = roleMap[user.role] || user.role;

            // Set member since
            if (user.created_at) {
                const date = new Date(user.created_at);
                document.getElementById('memberSince').textContent = date.toLocaleDateString('en-US', {
                    month: 'short',
                    year: 'numeric'
                });
            }

            // Populate Edit Form
            if (document.getElementById('editName')) document.getElementById('editName').value = user.username;
            if (document.getElementById('editEmail')) document.getElementById('editEmail').value = user.email;

            // Allow address to be set from localStorage if not in DB, for convenience
            const localAddress = localStorage.getItem('userAddress');
            if (localAddress) {
                document.getElementById('userAddress').textContent = localAddress;
                if (document.getElementById('editAddress')) document.getElementById('editAddress').value = localAddress;
            }

        })
        .catch(err => console.error('Error loading profile:', err));
}

function loadOrders() {
    fetch(`${API_BASE}/orders/history.php`)
        .then(res => res.json())
        .then(orders => {
            const container = document.getElementById('ordersContainer');

            // Update total orders count
            if (document.getElementById('totalOrders')) {
                document.getElementById('totalOrders').textContent = orders.length;
            }

            if (!Array.isArray(orders) || orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>No orders yet</h3>
                        <p>Start shopping for the best electronics!</p>
                    </div>
                `;
                return;
            }

            // Display orders
            container.innerHTML = '';
            orders.forEach(order => {
                const orderCard = document.createElement('div');
                orderCard.className = 'order-card';

                const statusClass = `status-${order.status}`;
                const statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                const orderDate = new Date(order.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Calculate item count
                const itemCount = order.items ? order.items.length : 0;

                orderCard.innerHTML = `
                    <div class="order-header">
                        <div class="order-id">Order #${order.order_number || order.id}</div>
                        <div class="order-status ${statusClass}">${statusText}</div>
                    </div>
                    <div class="order-details">
                        <p><strong>Date:</strong> ${orderDate}</p>
                        <p><strong>Items:</strong> ${itemCount} product(s)</p>
                    </div>
                    <div class="order-total">Total: TSh ${parseFloat(order.total).toLocaleString()}</div>
                `;

                container.appendChild(orderCard);
            });
        })
        .catch(err => {
            console.error('Error loading orders:', err);
            document.getElementById('ordersContainer').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">⚠️</div>
                    <h3>Error loading orders</h3>
                    <p>Please try again later.</p>
                </div>
            `;
        });
}

// Tab switching function
window.switchTab = function (tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    if (tabName === 'view') {
        document.querySelector('.tab-btn:first-child').classList.add('active');
        document.getElementById('viewProfileTab').classList.add('active');
    } else if (tabName === 'edit') {
        document.querySelector('.tab-btn:last-child').classList.add('active');
        document.getElementById('editProfileTab').classList.add('active');

        // Clear password fields
        document.getElementById('editPassword').value = '';
        document.getElementById('editPasswordConfirm').value = '';

        // Hide messages
        const updateMessage = document.getElementById('updateMessage');
        updateMessage.style.display = 'none';
    }
}

// Handle profile form submission
document.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const updateMessage = document.getElementById('updateMessage');

            // For now, we only update Email and Password because the backend doesn't support address in Users table
            // and Username is usually immutable.

            const email = document.getElementById('editEmail').value.trim();
            const password = document.getElementById('editPassword').value;
            const passwordConfirm = document.getElementById('editPasswordConfirm').value;
            const address = document.getElementById('editAddress').value.trim();

            // Validate
            if (password || passwordConfirm) {
                if (password !== passwordConfirm) {
                    showUpdateMessage('Passwords do not match!', 'error');
                    return;
                }
                if (password.length < 6) {
                    showUpdateMessage('Password must be at least 6 characters!', 'error');
                    return;
                }
            }

            // Save address to localStorage since DB doesn't have it
            if (address) {
                localStorage.setItem('userAddress', address);
                document.getElementById('userAddress').textContent = address;
            }

            const updateData = { email: email };
            if (password) updateData.password = password;

            // Send update request
            fetch(`${API_BASE}/user/profile.php`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updateData)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.message && (data.message.includes('success') || data.message.includes('updated'))) {
                        showUpdateMessage('Profile updated successfully!', 'success');
                        loadProfile(); // Reload data
                        setTimeout(() => switchTab('view'), 1500);
                    } else {
                        showUpdateMessage(data.message || 'Failed to update profile', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error updating profile:', err);
                    showUpdateMessage('Error updating profile. Please try again.', 'error');
                });
        });
    }
});

function showUpdateMessage(msg, type) {
    const el = document.getElementById('updateMessage');
    el.textContent = msg;
    el.className = 'update-message ' + type;
    el.style.display = 'block';
}
