document.addEventListener('DOMContentLoaded', function () {
    initApp();
});

const API_BASE = '/Electronics_Ordering_System/web_project/public/api';

function initApp() {
    // Check Auth
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user || user.role !== 'admin') {
        alert('Access Denied');
        window.location.href = 'login.html';
        return;
    }

    // Initial Load
    showSection('dashboard');
}

// Navigation
window.showSection = function (sectionId) {
    // Update Active Link
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    // Simple match based on text content (fragile but works for this demo) or add IDs to nav 
    // actually, let's just highlight the clicked one if passed as event, 
    // but here we just show the content section

    document.querySelectorAll('.section').forEach(el => el.classList.remove('active'));
    document.getElementById(sectionId).classList.add('active');

    if (sectionId === 'dashboard') loadDashboard();
    if (sectionId === 'orders') loadOrders();
    if (sectionId === 'users') loadUsers();
    if (sectionId === 'menu') loadMenu();
};

/* -------------------------------------------------------------------------- */
/*                               DASHBOARD                                    */
/* -------------------------------------------------------------------------- */
window.loadDashboard = function () {
    // Fetch dashboard stats from the dedicated API
    fetch(`${API_BASE}/admin/dashboard_stats.php?_t=` + new Date().getTime())
        .then(res => {
            if (!res.ok) throw new Error('Failed to fetch stats');
            return res.json();
        })
        .then(data => {
            // Update UI with data from API
            document.getElementById('stat-orders').innerText = data.total_orders || 0;
            document.getElementById('stat-users').innerText = data.total_users || 0;
            document.getElementById('stat-items').innerText = data.total_products || 0;

            // Format revenue robustly
            const revenue = parseFloat(data.total_revenue || 0);
            document.getElementById('stat-revenue').innerText = 'TSh ' + revenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        })
        .catch(err => {
            console.error('Error loading dashboard stats:', err);
            // Fallback for error state
            document.getElementById('stat-revenue').innerText = 'TSh 0.00';
        });

    // Clear existing interval if any
    if (window.dashboardInterval) clearInterval(window.dashboardInterval);

    // Set auto-refresh every 30 seconds
    window.dashboardInterval = setInterval(() => {
        // Only refresh if dashboard section is active
        if (document.getElementById('dashboard').classList.contains('active')) {
            loadDashboard();
        }
    }, 30000);
};

/* -------------------------------------------------------------------------- */
/*                                 ORDERS                                     */
/* -------------------------------------------------------------------------- */
window.loadOrders = function () {
    fetch(`${API_BASE}/admin/get_orders.php`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(orders => {
            const tbody = document.getElementById('ordersTableBody');
            tbody.innerHTML = '';

            if (!orders || orders.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                            <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">📦 No orders yet</p>
                            <p style="font-size: 0.9rem;">Orders will appear here when customers place them.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            orders.forEach(o => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${o.order_number || '#' + o.id}</td>
                    <td>${o.customer_name || 'Unknown'}</td>
                    <td>${o.item_count || 0}</td>
                    <td>${new Date(o.created_at).toLocaleDateString()}</td>
                    <td>
                        <select onchange="updateOrderStatus(${o.id}, this.value)" class="form-control" style="width:auto; padding:4px;">
                            <option value="pending" ${o.status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="processing" ${o.status === 'processing' ? 'selected' : ''}>Processing</option>
                            <option value="ready" ${o.status === 'ready' ? 'selected' : ''}>Ready</option>
                            <option value="shipped" ${o.status === 'shipped' ? 'selected' : ''}>Shipped</option>
                            <option value="delivered" ${o.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                            <option value="cancelled" ${o.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </td>
                    <td>TSh ${(parseFloat(o.total) || 0).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewOrderDetails(${o.id})">View</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteOrder(${o.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error loading orders:', err);
            const tbody = document.getElementById('ordersTableBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: #e74c3c;">
                        <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">⚠️ Error loading orders</p>
                        <p style="font-size: 0.9rem;">${err.message}</p>
                        <button onclick="loadOrders()" class="btn btn-primary" style="margin-top: 1rem;">Retry</button>
                    </td>
                </tr>
            `;
        });
};

window.updateOrderStatus = function (orderId, newStatus) {
    fetch(`${API_BASE}/seller/update_order_status.php`, { // Using seller API as it handles status updates
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
        .then(res => res.json())
        .then(data => {
            alert(data.message || 'Status updated');
        })
        .catch(err => {
            console.error('Error updating status:', err);
            alert('Failed to update status');
        });
};

window.viewOrderDetails = function (orderId) {
    // Ideally this would open a modal with order items
    alert('Viewing order details not fully implemented yet. Check console/network for items data.');
};

window.deleteOrder = function (id) {
    if (confirm("Are you sure you want to delete this order? This action cannot be undone.")) {
        fetch(`${API_BASE}/orders/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Order deleted successfully');
                loadOrders();
            })
            .catch(err => {
                console.error('Error deleting order:', err);
                alert('Failed to delete order');
            });
    }
};



/* -------------------------------------------------------------------------- */
/*                                  USERS                                     */
/* -------------------------------------------------------------------------- */
window.loadUsers = function () {
    fetch(`${API_BASE}/admin/get_users.php`)
        .then(res => res.json())
        .then(users => {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            users.forEach(u => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${u.id}</td>
                    <td>${u.username}</td>
                    <td>${u.email}</td>
                    <td>${u.role}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="promoteUser(${u.id}, '${u.role}')">Role</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${u.id})">Del</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
};

window.promoteUser = function (id, role) {
    let newRole = 'customer';
    if (role === 'customer') newRole = 'seller';
    else if (role === 'restaurant_owner') newRole = 'admin';

    fetch(`${API_BASE}/admin/promote_user.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: id, new_role: newRole })
    }).then(() => loadUsers());
};

window.deleteUser = function (id) {
    if (confirm("Delete user?")) {
        fetch(`${API_BASE}/admin/delete_user.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: id })
        }).then(() => loadUsers());
    }
};

/* -------------------------------------------------------------------------- */
/*                                 MENU                                       */
/* -------------------------------------------------------------------------- */
window.loadMenu = function () {
    fetch(`${API_BASE}/products/`)
        .then(res => res.json())
        .then(items => {
            const tbody = document.getElementById('menuTableBody');
            tbody.innerHTML = '';
            items.forEach(i => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${i.id}</td>
                    <td>${i.name}</td>
                    <td>${i.seller_name || 'ID: ' + i.seller_id}</td>
                    <td>TSh ${i.price}</td>
                    <td>${i.is_available == 1 ? 'Yes' : 'No'}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick='window.editMenuItem(${JSON.stringify(i).replace(/'/g, "&#39;")})'>Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="window.deleteMenuItem(${i.id})">Del</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
};


window.deleteMenuItem = function (id) {
    if (confirm("Delete item?")) {
        fetch(`${API_BASE}/products/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                loadMenu();
            });
    }
};


window.openAddMenuModal = function () {
    document.getElementById('menuModal').style.display = 'flex';
    document.getElementById('menuForm').reset();
    document.getElementById('menuId').value = '';
    document.getElementById('modalTitle').innerText = 'Add Product';
};

window.closeModal = function () {
    document.getElementById('menuModal').style.display = 'none';
};

window.editMenuItem = function (item) {
    document.getElementById('menuModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Edit Product';

    document.getElementById('menuId').value = item.id;
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemDesc').value = item.description || '';
    document.getElementById('itemPrice').value = item.price;
    document.getElementById('restaurantId').value = item.seller_id;
    document.getElementById('categoryId').value = item.category_id || 1;
    document.getElementById('itemAvail').value = item.is_available;
    document.getElementById('itemImage').value = ''; // Clear file input for security reasons
};


// Form Submit
const menuForm = document.getElementById('menuForm');
if (menuForm) {
    menuForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData();

        const id = document.getElementById('menuId').value;
        if (id) formData.append('id', id);

        formData.append('name', document.getElementById('itemName').value);
        formData.append('description', document.getElementById('itemDesc').value);
        formData.append('price', document.getElementById('itemPrice').value);
        formData.append('seller_id', document.getElementById('restaurantId').value);
        formData.append('category_id', document.getElementById('categoryId').value);
        formData.append('is_available', document.getElementById('itemAvail').value);

        const fileInput = document.getElementById('itemImage');
        if (fileInput && fileInput.files[0]) {
            formData.append('image', fileInput.files[0]);
        }

        // Use POST for both Create and Update (since HTML forms + files don't do PUT well)
        // Our backend handles the logic based on presence of 'id' in POST data
        fetch(`${API_BASE}/products/${id ? 'update.php' : 'create.php'}`, {
            method: 'POST',
            body: formData
        })
            .then(res => {
                // Check if response is ok
                if (!res.ok) {
                    return res.json().then(err => {
                        throw new Error(err.message || `Server error: ${res.status}`);
                    });
                }
                return res.json();
            })
            .then(d => {
                alert(d.message || "Operation successful");
                closeModal();
                loadMenu();
            })
            .catch(err => {
                console.error('Error details:', err);
                alert("Error saving item: " + err.message);
            });
    });
}

// Logout
document.getElementById('logoutBtn').addEventListener('click', function () {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
});
