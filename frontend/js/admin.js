// Load users table
async function loadUsersTable(page = 1, search = '', role = '') {
    if (!currentUser || (currentUser.role !== 'admin' && currentUser.role !== 'manager')) {
        return;
    }
    
    try {
        let url = `${API_BASE}/users/all?page=${page}&limit=10`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (role) url += `&role=${role}`;
        
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            renderUsersTable(data.data);
            renderPagination(data.data.pagination);
        }
    } catch (error) {
        console.error('Failed to load users:', error);
        showToast('Failed to load users', 'error');
    }
}

// Render users table
function renderUsersTable(data) {
    const container = document.getElementById('usersTableContainer');
    const template = document.getElementById('userRowTemplate');
    
    if (!container || !template) return;
    
    container.innerHTML = `
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                </tbody>
            </table>
        </div>
    `;
    
    const tbody = document.getElementById('usersTableBody');
    
    data.users.forEach(user => {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        
        // Fill in data
        Object.keys(user).forEach(key => {
            const cell = row.querySelector(`[data-field="${key}"]`);
            if (cell) {
                if (key === 'email_verified') {
                    const verifiedBadge = cell.querySelector('.verified');
                    const unverifiedBadge = cell.querySelector('.unverified');
                    
                    if (user[key]) {
                        verifiedBadge.style.display = 'inline-block';
                        unverifiedBadge.style.display = 'none';
                    } else {
                        verifiedBadge.style.display = 'none';
                        unverifiedBadge.style.display = 'inline-block';
                    }
                } else if (key === 'role') {
                    const select = cell.querySelector('.role-select');
                    const saveBtn = cell.querySelector('.save-role');
                    
                    select.value = user[key];
                    select.setAttribute('data-user-id', user.user_id);
                    
                    // Only admins can change roles
                    if (currentUser.role === 'admin') {
                        select.addEventListener('change', () => {
                            saveBtn.style.display = 'inline-block';
                        });
                        
                        saveBtn.addEventListener('click', async () => {
                            await updateUserRole(user.user_id, select.value);
                            saveBtn.style.display = 'none';
                        });
                    } else {
                        select.disabled = true;
                    }
                } else if (key === 'actions') {
                    const deleteBtn = cell.querySelector('.delete-user');
                    deleteBtn.setAttribute('data-user-id', user.user_id);
                    
                    // Only admins can delete users
                    if (currentUser.role === 'admin') {
                        deleteBtn.addEventListener('click', async () => {
                            if (confirm(`Are you sure you want to delete user ${user.email}?`)) {
                                await deleteUser(user.user_id);
                            }
                        });
                    } else {
                        deleteBtn.style.display = 'none';
                    }
                } else if (key === 'created_at') {
                    const date = new Date(user[key]);
                    cell.textContent = date.toLocaleDateString();
                } else {
                    cell.textContent = user[key];
                }
            }
        });
        
        tbody.appendChild(clone);
    });
}

// Render pagination
function renderPagination(pagination) {
    const container = document.getElementById('usersPagination');
    if (!container) return;
    
    container.innerHTML = '';
    
    const { page, pages } = pagination;
    
    // Previous button
    const prevBtn = document.createElement('a');
    prevBtn.href = '#';
    prevBtn.className = 'page-link' + (page === 1 ? ' disabled' : '');
    prevBtn.innerHTML = '&laquo; Previous';
    prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (page > 1) {
            const search = document.getElementById('searchUsers').value;
            const role = document.getElementById('roleFilter').value;
            loadUsersTable(page - 1, search, role);
        }
    });
    container.appendChild(prevBtn);
    
    // Page numbers
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(pages, page + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageLink = document.createElement('a');
        pageLink.href = '#';
        pageLink.className = 'page-link' + (i === page ? ' active' : '');
        pageLink.textContent = i;
        pageLink.addEventListener('click', (e) => {
            e.preventDefault();
            const search = document.getElementById('searchUsers').value;
            const role = document.getElementById('roleFilter').value;
            loadUsersTable(i, search, role);
        });
        container.appendChild(pageLink);
    }
    
    // Next button
    const nextBtn = document.createElement('a');
    nextBtn.href = '#';
    nextBtn.className = 'page-link' + (page === pages ? ' disabled' : '');
    nextBtn.innerHTML = 'Next &raquo;';
    nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (page < pages) {
            const search = document.getElementById('searchUsers').value;
            const role = document.getElementById('roleFilter').value;
            loadUsersTable(page + 1, search, role);
        }
    });
    container.appendChild(nextBtn);
}

// Update user role
async function updateUserRole(userId, role) {
    try {
        const response = await fetch(`${API_BASE}/users/${userId}/role`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ role })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            
            // If updating current user's role, update local state
            if (userId == currentUser.user_id) {
                currentUser.role = role;
                updateAuthUI();
            }
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to update user role:', error);
        showToast('Failed to update user role', 'error');
    }
}

// Delete user
async function deleteUser(userId) {
    try {
        const response = await fetch(`${API_BASE}/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            
            // Reload users table
            const search = document.getElementById('searchUsers').value;
            const role = document.getElementById('roleFilter').value;
            loadUsersTable(1, search, role);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Failed to delete user:', error);
        showToast('Failed to delete user', 'error');
    }
}

// Setup admin event listeners
document.addEventListener('DOMContentLoaded', () => {
    // User search
    const searchInput = document.getElementById('searchUsers');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const search = searchInput.value;
                const role = document.getElementById('roleFilter').value;
                loadUsersTable(1, search, role);
            }, 500);
        });
    }
    
    // Role filter
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
        roleFilter.addEventListener('change', () => {
            const search = document.getElementById('searchUsers').value;
            const role = roleFilter.value;
            loadUsersTable(1, search, role);
        });
    }
});