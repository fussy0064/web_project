document.addEventListener('DOMContentLoaded', () => {
    fetchProducts();
    checkAuth();
});

async function checkAuth() {
    try {
        const response = await fetch('api/auth/check_session.php');
        const data = await response.json();

        const authLinks = document.getElementById('auth-links');
        const userLinks = document.getElementById('user-links');
        const logoutBtn = document.getElementById('logout-btn');

        if (data.authenticated) {
            if (authLinks) authLinks.style.display = 'none';
            if (userLinks) userLinks.style.display = 'block';

            // Role based links
            /* Admin button removed as per request
            if (data.user.role === 'admin') {
                if (userLinks && !userLinks.querySelector('a[href="admin_dashboard.html"]')) {
                    const adminLink = document.createElement('a');
                    adminLink.href = 'admin_dashboard.html';
                    adminLink.innerHTML = '<i class="fas fa-user-shield"></i> Admin Panel';
                    adminLink.className = 'btn';
                    adminLink.style.marginRight = '10px';
                    adminLink.style.padding = '8px 15px';
                    adminLink.style.backgroundColor = '#dc3545';
                    userLinks.prepend(adminLink);
                }
            }
            */ if (data.user.role === 'seller') {
                if (userLinks && !userLinks.querySelector('a[href="seller_dashboard.html"]')) {
                    const sellerLink = document.createElement('a');
                    sellerLink.href = 'seller_dashboard.html';
                    sellerLink.textContent = 'Seller Dashboard';
                    sellerLink.style.marginRight = '10px';
                    userLinks.prepend(sellerLink);
                }
            } else if (data.user.role === 'customer') {
                if (userLinks && !userLinks.querySelector('a[href="customer_dashboard.html"]')) {
                    const dashboardLink = document.createElement('a');
                    dashboardLink.href = 'customer_dashboard.html';
                    dashboardLink.textContent = 'My Dashboard';
                    dashboardLink.style.marginRight = '10px';
                    userLinks.prepend(dashboardLink);
                }
            }

            if (logoutBtn) {
                // Remove old listener to avoid duplicates
                const newLogoutBtn = logoutBtn.cloneNode(true);
                logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);

                newLogoutBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await fetch('api/auth/logout.php');
                    localStorage.removeItem('user');
                    window.location.href = 'index.html'; // Redirect to home
                });
            }
        } else {
            if (authLinks) authLinks.style.display = 'block';
            if (userLinks) userLinks.style.display = 'none';
        }
    } catch (error) {
        console.error('Auth check error:', error);
    }
}

async function fetchProducts() {
    try {
        const response = await fetch('api/products/read.php');
        if (!response.ok) throw new Error('Failed to fetch');
        const products = await response.json();

        const productList = document.getElementById('product-list');
        productList.innerHTML = '';

        if (products.length === 0) {
            productList.innerHTML = '<p>No products found.</p>';
            return;
        }

        products.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.innerHTML = `
                <img src="${product.image_url}" alt="${product.name}" onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                <h3>${product.name}</h3>
                <p class="category">${product.category_name || ''}</p>
                <div class="specs" style="font-size: 0.9em; color: #666; margin-bottom: 5px;">${product.description ? product.description.substring(0, 50) + '...' : ''}</div>
                <div class="price">TShs ${parseFloat(product.price).toLocaleString()}</div>
                <button class="btn" onclick="addToCart(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price})">Add to Cart</button>
            `;
            productList.appendChild(productCard);
        });
    } catch (error) {
        console.error('Error fetching products:', error);
        if (document.getElementById('product-list')) {
            document.getElementById('product-list').innerHTML = '<p>Error loading products.</p>';
        }
    }
}

function addToCart(id, name, price) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existing = cart.find(item => item.id === id);

    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    alert(`${name} added to cart!`);
}
