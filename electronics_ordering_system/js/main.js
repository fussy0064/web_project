document.addEventListener('DOMContentLoaded', function () {
    // Check user login status
    const userStatus = document.getElementById('userStatus');
    const userJson = localStorage.getItem('user');

    if (userJson && userStatus) {
        const user = JSON.parse(userJson);
        const userAvatar = userStatus.querySelector('.user-avatar');
        const userText = userStatus.querySelector('span');

        // User is logged in
        if (userAvatar) userAvatar.textContent = user.username.charAt(0).toUpperCase();
        if (userText) userText.textContent = user.username.split(' ')[0];

        // Update login button to dashboard or logout
        const navLinks = document.querySelector('.nav-links');

        // Find existing Login/Register buttons
        const loginBtn = document.querySelector('a[href="login.html"]');
        const registerBtn = document.querySelector('a[href="register.html"]');

        if (loginBtn) {
            loginBtn.textContent = 'Dashboard';
            // Simple dashboard routing
            if (user.role === 'seller') loginBtn.href = 'seller-dashboard.html';
            else if (user.role === 'admin') loginBtn.href = 'admin.html';
            else loginBtn.href = 'profile.html';
        }

        if (registerBtn) {
            registerBtn.textContent = 'Logout';
            registerBtn.href = '#';
            registerBtn.classList.remove('btn-outline');
            registerBtn.classList.add('btn-primary');

            registerBtn.addEventListener('click', function (e) {
                e.preventDefault();
                localStorage.removeItem('user');
                alert('Logged out successfully');
                window.location.href = 'index.html';
            });
        }
    }

    // Fetch Products (Example of integration)
    // This could replace the static features or be a new section
    const featuresGrid = document.querySelector('.features-grid');
    // If we wanted to load dynamic content, we would do it here.
    // For now, we keep the static design as requested by the user ("Rich Aesthetics").

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#' || !targetId) return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.feature-card, .step, .category-card, .testimonial-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });

    // Load Featured Items
    loadFeaturedItems();
});

function loadFeaturedItems() {
    const container = document.getElementById('featuredMenuItems');
    if (!container) return; // Only run on index.html

    fetch('/Electronics_Ordering_System/web_project/public/api/products/')
        .then(res => res.json())
        .then(items => {
            if (items.length === 0) {
                container.innerHTML = '<p style="text-align:center; width:100%;">No products available yet.</p>';
                return;
            }

            container.innerHTML = '';
            items.forEach(item => {
                // Image path fix: backend returns 'uploads/filename', frontend is in 'frontend/html', so we need '../uploads/filename' 
                // OR root relative '/electronics_ordering_system/frontend/uploads/filename'
                // But the backend saves 'uploads/filename'. 
                // Let's assume we serve from root or use absolute path.

                let imgPath = 'https://images.unsplash.com/photo-1498049794561-7780e7231661?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; // Fallback
                if (item.image_url) {
                    if (item.image_url.startsWith('http') || item.image_url.startsWith('/')) {
                        imgPath = item.image_url;
                    } else {
                        // Fallback for old data or relative paths not handled by API
                        imgPath = '/electronics_ordering_system/frontend/' + item.image_url;
                    }
                }

                const itemEl = document.createElement('div');
                itemEl.className = 'menu-item';
                itemEl.innerHTML = `
                    <div class="item-image">
                        <img src="${imgPath}" alt="${item.name}">
                    </div>
                    <div class="item-content">
                        <div class="item-header">
                            <h3 class="item-title">${item.name}</h3>
                            <span class="item-price">TSh ${item.price}</span>
                        </div>
                        <span class="item-restaurant">by ${item.seller_name || 'ElectroHub'}</span>
                        <p class="item-desc">${item.description || 'Quality electronics product.'}</p>
                        <button class="btn-add-cart" onclick="addToCart(${item.id})">Add to Cart</button>
                    </div>
                `;
                container.appendChild(itemEl);
            });
        })
        .catch(err => {
            console.error('Error loading products:', err);
            container.innerHTML = '<p style="text-align:center; color:red;">Failed to load products.</p>';
        });
}

function addToCart(itemId) {
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        alert('Please login to add items to cart');
        window.location.href = 'login.html';
        return;
    }

    // Get the item details from the page or fetch from API
    fetch('/Electronics_Ordering_System/web_project/public/api/products/')
        .then(res => res.json())
        .then(items => {
            const item = items.find(i => i.id == itemId);
            if (!item) {
                alert('Product not found');
                return;
            }

            // Get existing cart or create new
            let cart = JSON.parse(localStorage.getItem('cart')) || [];

            // Check if item already in cart
            const existingIndex = cart.findIndex(c => c.id == itemId);
            if (existingIndex >= 0) {
                cart[existingIndex].quantity += 1;
            } else {
                cart.push({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    seller_id: item.seller_id,
                    seller_name: item.seller_name,
                    image_url: item.image_url,
                    quantity: 1
                });
            }

            // Save cart
            localStorage.setItem('cart', JSON.stringify(cart));

            // Redirect to order page
            window.location.href = 'order.html';
        })
        .catch(err => {
            console.error('Error adding to cart:', err);
            alert('Failed to add product to cart');
        });
}


