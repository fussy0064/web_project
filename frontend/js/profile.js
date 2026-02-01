// Show profile modal
async function showProfileModal(activeTab = 'profile') {
    if (!currentUser) return;
    
    // Load user data
    try {
        const response = await fetch(`${API_BASE}/auth/me`, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            currentUser = data.data.user;
            populateProfileForm(currentUser);
            setupProfileTabs(activeTab);
            document.getElementById('profileModal').classList.add('active');
            
            // Load activity if active tab is activity
            if (activeTab === 'activity') {
                loadUserActivity();
            }
        }
    } catch (error) {
        console.error('Failed to load profile:', error);
        showToast('Failed to load profile', 'error');
    }
}

// Populate profile form
function populateProfileForm(user) {
    // Basic info
    document.getElementById('profileAvatar').src = user.avatar_url || '/images/default-avatar.png';
    document.getElementById('profileName').textContent = user.full_name;
    document.getElementById('profileEmail').textContent = user.email;
    document.getElementById('profileRole').textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
    document.getElementById('profileRole').className = `badge ${user.role}`;
    
    // Form fields
    document.getElementById('profileFullName').value = user.full_name || '';
    document.getElementById('profilePhone').value = user.phone || '';
    document.getElementById('profileBio').value = user.bio || '';
    document.getElementById('profileDob').value = user.date_of_birth || '';
    document.getElementById('profileGender').value = user.gender || '';
    document.getElementById('profileAddress').value = user.address || '';
    document.getElementById('profileCity').value = user.city || '';
    document.getElementById('profileState').value = user.state || '';
    document.getElementById('profileCountry').value = user.country || '';
    document.getElementById('profilePostalCode').value = user.postal_code || '';
    
    // Show users tab for admin/manager
    if (user.role === 'admin' || user.role === 'manager') {
        document.getElementById('usersTab').style.display = 'block';
    }
}

// Setup profile tabs
function setupProfileTabs(activeTab = 'profile') {
    // Remove active class from all tabs and panes
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Set active tab
    const activeTabElement = document.querySelector(`[data-tab="${activeTab}"]`);
    if (activeTabElement) {
        activeTabElement.classList.add('active');
        document.getElementById(`${activeTab}Tab`).classList.add('active');
    }
    
    // Add tab click listeners
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            setupProfileTabs(tabName);
            
            // Load specific data for each tab
            switch(tabName) {
                case 'activity':
                    loadUserActivity();
                    break;
                case 'users':
                    if (currentUser.role === 'admin' || currentUser.role === 'manager') {
                        loadUsersTable();
                    }
                    break;
            }
        });
    });
}

// Handle profile update
document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        full_name: document.getElementById('profileFullName').value.trim(),
        phone: document.getElementById('profilePhone').value.trim(),
        bio: document.getElementById('profileBio').value.trim(),
        date_of_birth: document.getElementById('profileDob').value || null,
        gender: document.getElementById('profileGender').value || null,
        address: document.getElementById('profileAddress').value.trim() || null,
        city: document.getElementById('profileCity').value.trim() || null,
        state: document.getElementById('profileState').value.trim() || null,
        country: document.getElementById('profileCountry').value.trim() || null,
        postal_code: document.getElementById('profilePostalCode').value.trim() || null
    };
    
    try {
        const response = await fetch(`${API_BASE}/users/profile`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast('Profile updated successfully', 'success');
            currentUser = data.data.user;
            updateAuthUI();
            populateProfileForm(currentUser);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Profile update error:', error);
        showToast('Failed to update profile', 'error');
    }
});

// Handle password change
document.getElementById('changePasswordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPasswordProfile').value;
    const confirmPassword = document.getElementById('confirmNewPasswordProfile').value;
    
    // Clear errors
    document.getElementById('currentPasswordError').textContent = '';
    document.getElementById('newPasswordProfileError').textContent = '';
    document.getElementById('confirmNewPasswordProfileError').textContent = '';
    
    // Validate new password
    const passwordError = validatePassword(newPassword);
    if (passwordError) {
        document.getElementById('newPasswordProfileError').textContent = passwordError;
        return;
    }
    
    // Check password confirmation
    if (newPassword !== confirmPassword) {
        document.getElementById('confirmNewPasswordProfileError').textContent = 'Passwords do not match';
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/users/change-password`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({ currentPassword, newPassword })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast('Password changed successfully', 'success');
            document.getElementById('changePasswordForm').reset();
        } else {
            showToast(data.message, 'error');
            if (data.message.includes('Current password')) {
                document.getElementById('currentPasswordError').textContent = data.message;
            }
        }
    } catch (error) {
        console.error('Password change error:', error);
        showToast('Failed to change password', 'error');
    }
});

// Handle avatar change
document.getElementById('changeAvatarBtn')?.addEventListener('click', () => {
    document.getElementById('avatarInput').click();
});

document.getElementById('avatarInput')?.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showToast('File size must be less than 5MB', 'error');
        return;
    }
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        showToast('Only JPEG, PNG, and GIF images are allowed', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    
    try {
        const response = await fetch(`${API_BASE}/users/upload-avatar`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${authToken}`
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast('Avatar updated successfully', 'success');
            document.getElementById('profileAvatar').src = data.data.avatar_url;
            currentUser.avatar_url = data.data.avatar_url;
            updateAuthUI();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Avatar upload error:', error);
        showToast('Failed to upload avatar', 'error');
    }
    
    // Reset file input
    e.target.value = '';
});

// Load user activity
async function loadUserActivity() {
    try {
        // In a real application, you would fetch activity from an API endpoint
        // For now, we'll simulate with sample data
        const sampleActivities = [
            {
                type: 'login',
                text: 'You logged in to your account',
                time: 'Just now',
                icon: 'fa-sign-in-alt'
            },
            {
                type: 'profile_update',
                text: 'You updated your profile information',
                time: '2 hours ago',
                icon: 'fa-user-edit'
            },
            {
                type: 'order',
                text: 'You placed an order #ORD-12345',
                time: '1 day ago',
                icon: 'fa-shopping-cart'
            },
            {
                type: 'registration',
                text: 'You created your account',
                time: '1 week ago',
                icon: 'fa-user-plus'
            }
        ];
        
        const activityList = document.getElementById('activityList');
        const template = document.getElementById('activityItemTemplate');
        
        activityList.innerHTML = '';
        
        sampleActivities.forEach(activity => {
            const clone = template.content.cloneNode(true);
            const item = clone.querySelector('.activity-item');
            
            item.querySelector('.activity-icon i').className = `fas ${activity.icon}`;
            item.querySelector('.activity-text').textContent = activity.text;
            item.querySelector('.activity-time').textContent = activity.time;
            
            activityList.appendChild(clone);
        });
    } catch (error) {
        console.error('Failed to load activity:', error);
        showToast('Failed to load activity', 'error');
    }
}