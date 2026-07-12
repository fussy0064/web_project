const API_BASE = '/Electronics_Ordering_System/web_project/public/api';

document.addEventListener('DOMContentLoaded', function () {
    initApp();
});

function initApp() {
    const user = JSON.parse(localStorage.getItem('user') || 'null');
    if (!user || (user.role !== 'seller' && user.role !== 'admin')) {
        alert('Access Denied');
        window.location.href = 'login.html';
        return;
    }
    document.getElementById('userInfo').textContent = user.username;
    showSection('dashboard');

    document.getElementById('logoutBtn').addEventListener('click', function () {
        fetch(`${API_BASE}/auth/logout.php`).finally(() => {
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        });
    });

    document.getElementById('productForm').addEventListener('submit', submitProductForm);
}

window.showSection = function (sectionId) {
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.section').forEach(el => el.classList.remove('active'));
    document.getElementById(sectionId).classList.add('active');

    if (sectionId === 'dashboard') loadDashboard();
    if (sectionId === 'products') loadProducts();
    if (sectionId === 'orders') loadOrders();
    if (sectionId === 'notifications') loadNotifications();
};

/* Dashboard */
window.loadDashboard = function () {
    fetch(`${API_BASE}/seller/dashboard_stats.php`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('stat-products').innerText = data.my_products || 0;
            document.getElementById('stat-orders').innerText = data.active_orders || 0;
            document.getElementById('stat-stock').innerText = data.total_stock || 0;
            const revenue = parseFloat(data.my_revenue || 0);
            document.getElementById('stat-revenue').innerText = 'TSh ' + revenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        })
        .catch(err => console.error('Error loading dashboard stats:', err));
};

/* Products */
window.loadProducts = function () {
    fetch(`${API_BASE}/seller/products.php`)
        .then(res => res.json())
        .then(products => {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';
            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem;">No products yet.</td></tr>';
                return;
            }
            products.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${p.id}</td>
                    <td>${p.name}</td>
                    <td>TSh ${parseFloat(p.price).toLocaleString()}</td>
                    <td>${p.stock_quantity}</td>
                    <td>${p.status}</td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&#39;")})'>Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.id})">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error('Error loading products:', err));
};

window.openAddProductModal = function () {
    document.getElementById('productModal').style.display = 'flex';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('modalTitle').innerText = 'Add Product';
};

window.closeModal = function () {
    document.getElementById('productModal').style.display = 'none';
};

window.editProduct = function (product) {
    document.getElementById('productModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('itemName').value = product.name;
    document.getElementById('itemDesc').value = product.description || '';
    document.getElementById('itemBrand').value = product.brand || '';
    document.getElementById('itemPrice').value = product.price;
    document.getElementById('itemStock').value = product.stock_quantity;
    document.getElementById('categoryId').value = product.category_id || 1;
    document.getElementById('itemImage').value = '';
};

function submitProductForm(e) {
    e.preventDefault();

    const id = document.getElementById('productId').value;
    const formData = new FormData();
    if (id) formData.append('id', id);
    formData.append('name', document.getElementById('itemName').value);
    formData.append('description', document.getElementById('itemDesc').value);
    formData.append('brand', document.getElementById('itemBrand').value);
    formData.append('price', document.getElementById('itemPrice').value);
    formData.append('stock_quantity', document.getElementById('itemStock').value);
    formData.append('category_id', document.getElementById('categoryId').value);

    const fileInput = document.getElementById('itemImage');
    if (fileInput.files[0]) formData.append('image', fileInput.files[0]);

    // The PHP backend uses POST for both create and update, keyed off
    // whether an 'id' field is present in the submitted data.
    fetch(`${API_BASE}/products/${id ? 'update.php' : 'create.php'}`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) throw new Error(data.message || 'Request failed');
            alert(data.message || 'Saved successfully');
            closeModal();
            loadProducts();
        })
        .catch(err => alert('Error saving product: ' + err.message));
}

window.deleteProduct = function (id) {
    if (!confirm('Delete this product?')) return;
    fetch(`${API_BASE}/products/delete.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
        .then(res => res.json())
        .then(data => {
            alert(data.message || 'Deleted');
            loadProducts();
        })
        .catch(err => alert('Error deleting product: ' + err.message));
};

/* Orders */
window.loadOrders = function () {
    fetch(`${API_BASE}/seller/all_orders.php`)
        .then(res => res.json())
        .then(orders => {
            const tbody = document.getElementById('ordersTableBody');
            tbody.innerHTML = '';
            if (!orders || orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem;">No orders yet.</td></tr>';
                return;
            }
            orders.forEach(o => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>#${o.id}</td>
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
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error('Error loading orders:', err));
};

window.updateOrderStatus = function (orderId, newStatus) {
    fetch(`${API_BASE}/seller/update_order_status.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: newStatus })
    })
        .then(res => res.json())
        .then(data => alert(data.message || 'Status updated'))
        .catch(err => alert('Failed to update status'));
};

/* Notifications */
window.loadNotifications = function () {
    fetch(`${API_BASE}/seller/notifications.php`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('notificationsList');
            const notifs = data.notifications || [];
            if (notifs.length === 0) {
                list.innerHTML = '<p style="padding:1rem; color:#666;">No notifications.</p>';
                return;
            }
            list.innerHTML = notifs.map(n => `
                <div class="notif-item ${n.is_read ? '' : 'unread'}">
                    <strong>${n.title}</strong>
                    <p>${n.message}</p>
                    <small>${new Date(n.created_at).toLocaleString()}</small>
                </div>
            `).join('');
        })
        .catch(err => console.error('Error loading notifications:', err));
};

window.markAllRead = function () {
    fetch(`${API_BASE}/seller/mark_all_notifications_read.php`, { method: 'POST' })
        .then(() => loadNotifications());
};
