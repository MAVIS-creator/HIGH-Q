/**
 * Profile Management Modal
 * Handles profile updates, password changes, and security settings
 */

(function() {
    'use strict';

    const ADMIN_BASE = (function(){
        const raw = window.HQ_ADMIN_BASE || '';
        try {
            if (raw) {
                const u = new URL(raw, window.location.origin);
                return u.origin !== window.location.origin
                    ? window.location.origin + u.pathname.replace(/\/$/, '')
                    : u.origin + u.pathname.replace(/\/$/, '');
            }
        } catch (e) {}
        // Fallback keeps subfolder path when header injected only HQ_ADMIN_PATH
        const path = window.HQ_ADMIN_PATH || '/admin';
        return window.location.origin + path.replace(/\/$/, '');
    })();
    
    // Track original values for email/phone verification
    let originalEmail = '';
    let originalPhone = '';

    // Initialize modal
    function initProfileModal() {
        // Check if modal already exists
        if (document.getElementById('profileModal')) {
            return;
        }

        // Create modal HTML
        const modalHTML = `
            <div class="profile-modal-overlay" id="profileModalOverlay">
                <div class="profile-modal">
                    <div class="profile-modal-header">
                        <h2><i class='bx bx-user-circle'></i> Profile Management</h2>
                        <button class="profile-modal-close" id="profileModalClose">
                            <i class='bx bx-x'></i>
                        </button>
                    </div>

                    <div class="profile-modal-tabs">
                        <button class="profile-tab active" data-tab="general">
                            <i class='bx bx-user'></i> General
                        </button>
                        <button class="profile-tab" data-tab="password">
                            <i class='bx bx-lock'></i> Password
                        </button>
                        <button class="profile-tab" data-tab="security">
                            <i class='bx bx-shield'></i> Security
                        </button>
                    </div>

                    <div class="profile-modal-body">
                        <!-- General Tab -->
                        <div class="profile-tab-content active" id="tab-general">
                            <div class="profile-avatar-section">
                                <div class="profile-avatar-preview">
                                    <img src="${window.HQ_USER_AVATAR || '../../public/assets/images/hq-logo.jpeg'}" alt="Avatar" id="profileAvatarPreview">
                                </div>
                                <div class="profile-avatar-controls">
                                    <h3>Profile Picture</h3>
                                    <p>Upload a new profile picture. Max size: 2MB. Formats: JPG, PNG, GIF</p>
                                    <input type="file" id="profileAvatarInput" accept="image/*" style="display:none">
                                    <button class="profile-upload-btn" id="profileUploadBtn">
                                        <i class='bx bx-upload'></i> Upload New Picture
                                    </button>
                                </div>
                            </div>

                            <form id="profileGeneralForm">
                                <div class="profile-form-row">
                                    <div class="profile-form-group">
                                        <label for="profileName">Full Name</label>
                                        <input type="text" id="profileName" name="name" required>
                                    </div>
                                    <div class="profile-form-group">
                                        <label for="profileEmail">Email Address</label>
                                        <div style="display:flex;gap:8px">
                                            <input type="email" id="profileEmail" name="email" style="flex:1" required>
                                            <button type="button" class="profile-verify-btn" id="verifyEmailBtn" style="display:none;padding:8px 12px;background:#ffd600;border:none;border-radius:6px;cursor:pointer;white-space:nowrap">
                                                <i class='bx bx-mail-send'></i> Verify
                                            </button>
                                        </div>
                                        <div id="emailVerifyPanel" style="display:none;margin-top:10px;padding:10px;background:#f0f8ff;border-radius:6px">
                                            <label for="emailVerifyCode">Verification Code</label>
                                            <input type="text" id="emailVerifyCode" maxlength="6" placeholder="000000" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:8px">
                                            <button type="button" id="confirmEmailCodeBtn" style="width:100%;padding:8px;background:#ffd600;border:none;border-radius:4px;cursor:pointer;font-weight:600">
                                                Verify Code
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="profile-form-row">
                                    <div class="profile-form-group">
                                        <label for="profilePhone">Phone Number</label>
                                        <div style="display:flex;gap:8px">
                                            <input type="tel" id="profilePhone" name="phone" style="flex:1">
                                            <button type="button" class="profile-verify-btn" id="verifyPhoneBtn" style="display:none;padding:8px 12px;background:#ffd600;border:none;border-radius:6px;cursor:pointer;white-space:nowrap">
                                                <i class='bx bx-phone'></i> Verify
                                            </button>
                                        </div>
                                        <div id="phoneVerifyPanel" style="display:none;margin-top:10px;padding:10px;background:#f0f8ff;border-radius:6px">
                                            <label for="phoneVerifyCode">Verification Code</label>
                                            <input type="text" id="phoneVerifyCode" maxlength="6" placeholder="000000" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;margin-bottom:8px">
                                            <button type="button" id="confirmPhoneCodeBtn" style="width:100%;padding:8px;background:#ffd600;border:none;border-radius:4px;cursor:pointer;font-weight:600">
                                                Verify Code
                                            </button>
                                        </div>
                                    </div>
                                    <div class="profile-form-group">
                                        <label for="profileRole">Role</label>
                                        <input type="text" id="profileRole" name="role" readonly style="background:#eee;cursor:not-allowed">
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Password Tab -->
                        <div class="profile-tab-content" id="tab-password">
                            <form id="profilePasswordForm">
                                <div class="profile-form-group">
                                    <label for="currentPassword">Current Password</label>
                                    <input type="password" id="currentPassword" name="current_password" required>
                                </div>
                                <div class="profile-form-group">
                                    <label for="newPassword">New Password</label>
                                    <input type="password" id="newPassword" name="new_password" required minlength="8">
                                    <small style="color:#666;font-size:0.85rem">Must be at least 8 characters long</small>
                                </div>
                                <div class="profile-form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <input type="password" id="confirmPassword" name="confirm_password" required>
                                </div>
                            </form>
                        </div>

                        <!-- Security Tab -->
                        <div class="profile-tab-content" id="tab-security">
                            <div class="security-option">
                                <div class="security-option-header">
                                    <h4><i class='bx bxl-google'></i> Google Authenticator</h4>
                                    <span class="otp-status disabled" id="google2faStatus">
                                        <i class='bx bx-x-circle'></i> Not Set Up
                                    </span>
                                </div>
                                <p class="security-option-description">
                                    Use Google Authenticator app for secure two-factor authentication.
                                </p>
                                <button class="security-action-btn btn-setup" id="setupGoogle2faBtn">
                                    <i class='bx bx-plus-circle'></i> Setup Google Authenticator
                                </button>
                                
                                <!-- Google 2FA Setup Panel (hidden by default) -->
                                <div class="google2fa-setup" id="google2faSetup" style="display:none">
                                    <div class="google2fa-qr">
                                        <h4>Scan QR Code</h4>
                                        <img src="" alt="QR Code" id="google2faQR" style="max-width:200px;margin:10px auto;display:block">
                                        <p style="font-size:0.85rem;color:#666">
                                            Scan this QR code with Google Authenticator app
                                        </p>
                                        <div class="manual-entry">
                                            <strong>Manual Entry:</strong>
                                            <code id="google2faSecret" style="background:#f5f5f5;padding:8px;display:block;border-radius:4px;margin:8px 0;font-family:monospace"></code>
                                        </div>
                                    </div>
                                    <div class="google2fa-verify">
                                        <label for="google2faCode">Enter 6-digit code from app:</label>
                                        <input type="text" id="google2faCode" maxlength="6" placeholder="000000" style="text-align:center;font-size:1.5rem;letter-spacing:0.5rem">
                                        <div style="display:flex;gap:10px;margin-top:10px">
                                            <button class="profile-btn profile-btn-save" id="verifyGoogle2faBtn">
                                                <i class='bx bx-check'></i> Verify & Enable
                                            </button>
                                            <button class="profile-btn profile-btn-cancel" id="cancelGoogle2faBtn">
                                                <i class='bx bx-x'></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Disable 2FA Button (shown when enabled) -->
                                <button class="security-action-btn btn-danger" id="disableGoogle2faBtn" style="display:none;background:#dc3545">
                                    <i class='bx bx-x-circle'></i> Disable Google Authenticator
                                </button>
                            </div>

                            <div class="security-option">
                                <div class="security-option-header">
                                    <h4>
                                        <i class='bx bx-time'></i>
                                        Session Timeout
                                    </h4>
                                    <label class="security-toggle">
                                        <input type="checkbox" id="sessionTimeout">
                                        <span class="security-toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="security-option-description">
                                    Automatically log out after 30 minutes of inactivity to protect your account.
                                </p>
                            </div>

                            <div class="security-option">
                                <div class="security-option-header">
                                    <h4>
                                        <i class='bx bx-bell'></i>
                                        Login Notifications
                                    </h4>
                                    <label class="security-toggle">
                                        <input type="checkbox" id="loginNotifications" checked>
                                        <span class="security-toggle-slider"></span>
                                    </label>
                                </div>
                                <p class="security-option-description">
                                    Receive email notifications whenever someone logs into your account.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="profile-modal-footer">
                        <button class="profile-btn profile-btn-cancel" id="profileCancelBtn">
                            <i class='bx bx-x'></i> Cancel
                        </button>
                        <button class="profile-btn profile-btn-save" id="profileSaveBtn">
                            <i class='bx bx-check'></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Initialize event listeners
        setupEventListeners();
        loadUserData();
    }

    function setupEventListeners() {
        const overlay = document.getElementById('profileModalOverlay');
        const closeBtn = document.getElementById('profileModalClose');
        const cancelBtn = document.getElementById('profileCancelBtn');
        const saveBtn = document.getElementById('profileSaveBtn');
        const tabs = document.querySelectorAll('.profile-tab');
        const uploadBtn = document.getElementById('profileUploadBtn');
        const avatarInput = document.getElementById('profileAvatarInput');
        const setupGoogle2faBtn = document.getElementById('setupGoogle2faBtn');
        const verifyGoogle2faBtn = document.getElementById('verifyGoogle2faBtn');
        const cancelGoogle2faBtn = document.getElementById('cancelGoogle2faBtn');
        const disableGoogle2faBtn = document.getElementById('disableGoogle2faBtn');

        // Close modal
        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
        overlay?.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });

        // Email/Phone verification
        const profileEmailInput = document.getElementById('profileEmail');
        const profilePhoneInput = document.getElementById('profilePhone');
        const verifyEmailBtn = document.getElementById('verifyEmailBtn');
        const verifyPhoneBtn = document.getElementById('verifyPhoneBtn');
        const confirmEmailCodeBtn = document.getElementById('confirmEmailCodeBtn');
        const confirmPhoneCodeBtn = document.getElementById('confirmPhoneCodeBtn');

        // Monitor email changes to show verify button
        profileEmailInput?.addEventListener('change', () => {
            if (profileEmailInput.value !== originalEmail && profileEmailInput.value) {
                verifyEmailBtn.style.display = 'inline-block';
            } else {
                verifyEmailBtn.style.display = 'none';
                document.getElementById('emailVerifyPanel').style.display = 'none';
            }
        });

        // Monitor phone changes to show verify button
        profilePhoneInput?.addEventListener('change', () => {
            if (profilePhoneInput.value !== originalPhone && profilePhoneInput.value) {
                verifyPhoneBtn.style.display = 'inline-block';
            } else {
                verifyPhoneBtn.style.display = 'none';
                document.getElementById('phoneVerifyPanel').style.display = 'none';
            }
        });

        // Send email verification code
        verifyEmailBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            const email = profileEmailInput.value;
            if (!email) return;

            try {
                const response = await fetch(`${ADMIN_BASE}/api/send_verification_code.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'email', value: email }),
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('emailVerifyPanel').style.display = 'block';
                    document.getElementById('emailVerifyCode').focus();
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent',
                        text: 'Verification code sent to your email'
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: error.message
                });
            }
        });

        // Verify email code
        confirmEmailCodeBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            const code = document.getElementById('emailVerifyCode').value;
            if (!code) {
                Swal.fire({ icon: 'error', text: 'Enter the code' });
                return;
            }

            try {
                const response = await fetch(`${ADMIN_BASE}/api/verify_code.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code }),
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('emailVerifyPanel').style.display = 'none';
                    document.getElementById('emailVerifyCode').value = '';
                    verifyEmailBtn.innerHTML = '<i class="bx bx-check-circle"></i> Verified';
                    verifyEmailBtn.disabled = true;
                    Swal.fire({ icon: 'success', text: 'Email verified successfully' });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    text: error.message
                });
            }
        });

        // Send phone verification code
        verifyPhoneBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            const phone = profilePhoneInput.value;
            if (!phone) return;

            try {
                const response = await fetch(`${ADMIN_BASE}/api/send_verification_code.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'phone', value: phone }),
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('phoneVerifyPanel').style.display = 'block';
                    document.getElementById('phoneVerifyCode').focus();
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent',
                        text: 'Verification code sent to your phone'
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: error.message
                });
            }
        });

        // Verify phone code
        confirmPhoneCodeBtn?.addEventListener('click', async (e) => {
            e.preventDefault();
            const code = document.getElementById('phoneVerifyCode').value;
            if (!code) {
                Swal.fire({ icon: 'error', text: 'Enter the code' });
                return;
            }

            try {
                const response = await fetch(`${ADMIN_BASE}/api/verify_code.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code }),
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('phoneVerifyPanel').style.display = 'none';
                    document.getElementById('phoneVerifyCode').value = '';
                    verifyPhoneBtn.innerHTML = '<i class="bx bx-check-circle"></i> Verified';
                    verifyPhoneBtn.disabled = true;
                    Swal.fire({ icon: 'success', text: 'Phone verified successfully' });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Verification Failed',
                    text: error.message
                });
            }
        });

    // Google 2FA Functions
    async function setupGoogle2FA() {
        try {
            const response = await fetch(`${ADMIN_BASE}/api/google2fa_setup.php`, {
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show QR code and secret
                document.getElementById('google2faQR').src = data.qr_code_url;
                document.getElementById('google2faSecret').textContent = data.secret;
                
                // Hide setup button, show setup panel
                document.getElementById('setupGoogle2faBtn').style.display = 'none';
                document.getElementById('google2faSetup').style.display = 'block';
            } else {
                throw new Error(data.error || 'Failed to generate 2FA secret');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Setup Failed',
                text: error.message
            });
        }
    }

    async function verifyGoogle2FA() {
        const code = document.getElementById('google2faCode').value;
        
        if (!code || code.length !== 6) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Code',
                text: 'Please enter a 6-digit code'
            });
            return;
        }
        
        try {
            const response = await fetch(`${ADMIN_BASE}/api/google2fa_verify.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ code }),
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Enabled!',
                    text: 'Google Authenticator has been enabled successfully'
                });
                
                // Update UI
                document.getElementById('google2faSetup').style.display = 'none';
                document.getElementById('google2faCode').value = '';
                
                const statusEl = document.getElementById('google2faStatus');
                statusEl.innerHTML = '<i class="bx bx-check-circle"></i> Enabled';
                statusEl.className = 'otp-status enabled';
                
                document.getElementById('setupGoogle2faBtn').style.display = 'none';
                document.getElementById('disableGoogle2faBtn').style.display = 'inline-block';
            } else {
                throw new Error(data.message || 'Verification failed');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                text: error.message
            });
        }
    }

    function cancelGoogle2FASetup() {
        document.getElementById('google2faSetup').style.display = 'none';
        document.getElementById('setupGoogle2faBtn').style.display = 'inline-block';
        document.getElementById('google2faCode').value = '';
    }

    async function disableGoogle2FA() {
        const result = await Swal.fire({
            title: 'Disable Google Authenticator?',
            text: 'Enter your password to confirm',
            input: 'password',
            inputPlaceholder: 'Enter your password',
            showCancelButton: true,
            confirmButtonText: 'Disable',
            cancelButtonText: 'Cancel',
            inputValidator: (value) => {
                if (!value) {
                    return 'Password is required';
                }
            }
        });
        
        if (result.isConfirmed) {
            try {
                const response = await fetch(`${ADMIN_BASE}/api/google2fa_disable.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password: result.value }),
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Disabled',
                        text: 'Google Authenticator has been disabled'
                    });
                    
                    // Update UI
                    const statusEl = document.getElementById('google2faStatus');
                    statusEl.innerHTML = '<i class="bx bx-x-circle"></i> Not Set Up';
                    statusEl.className = 'otp-status disabled';
                    
                    document.getElementById('setupGoogle2faBtn').style.display = 'inline-block';
                    document.getElementById('disableGoogle2faBtn').style.display = 'none';
                } else {
                    throw new Error(data.message || 'Failed to disable');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: error.message
                });
            }
        }
    }


        // Tab switching
        tabs.forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

        // Avatar upload
        uploadBtn?.addEventListener('click', () => avatarInput.click());
        avatarInput?.addEventListener('change', handleAvatarUpload);

        // Save button
        saveBtn?.addEventListener('click', handleSave);

        // Google 2FA
        setupGoogle2faBtn?.addEventListener('click', setupGoogle2FA);
        verifyGoogle2faBtn?.addEventListener('click', verifyGoogle2FA);
        cancelGoogle2faBtn?.addEventListener('click', cancelGoogle2FASetup);
        disableGoogle2faBtn?.addEventListener('click', disableGoogle2FA);
    }

    function openModal() {
        const overlay = document.getElementById('profileModalOverlay');
        if (overlay) {
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        const overlay = document.getElementById('profileModalOverlay');
        if (overlay) {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    function switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.tab === tabName);
        });

        // Update tab content
        document.querySelectorAll('.profile-tab-content').forEach(content => {
            content.classList.toggle('active', content.id === `tab-${tabName}`);
        });
    }

    async function loadUserData() {
        try {
            // Load user data from session or API
            const response = await fetch(`${ADMIN_BASE}/api/user_profile.php`, {
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                populateForm(data);
            }
        } catch (error) {
            console.error('Failed to load user data:', error);
        }
    }

    function populateForm(data) {
        document.getElementById('profileName').value = data.name || '';
        document.getElementById('profileEmail').value = data.email || '';
        document.getElementById('profilePhone').value = data.phone || '';
        document.getElementById('profileRole').value = data.role || '';
        
        // Store original values to detect changes
        originalEmail = data.email || '';
        originalPhone = data.phone || '';
        
        if (data.avatar) {
            document.getElementById('profileAvatarPreview').src = data.avatar;
        }

        // Update Google 2FA status
        if (data.google2fa_enabled) {
            const statusEl = document.getElementById('google2faStatus');
            statusEl.className = 'otp-status enabled';
            statusEl.innerHTML = '<i class=\'bx bx-check-circle\'></i> Enabled';
            
            document.getElementById('setupGoogle2faBtn').style.display = 'none';
            document.getElementById('disableGoogle2faBtn').style.display = 'inline-block';
        } else {
            const statusEl = document.getElementById('google2faStatus');
            statusEl.className = 'otp-status disabled';
            statusEl.innerHTML = '<i class=\'bx bx-x-circle\'></i> Not Set Up';
            
            document.getElementById('setupGoogle2faBtn').style.display = 'inline-block';
            document.getElementById('disableGoogle2faBtn').style.display = 'none';
        }
    }

    async function handleAvatarUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select an image under 2MB'
            });
            return;
        }

        if (!file.type.startsWith('image/')) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select an image file (JPG, PNG, or GIF)'
            });
            return;
        }

        // Preview image
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('profileAvatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    async function handleSave() {
        const activeTab = document.querySelector('.profile-tab.active').dataset.tab;
        
        if (activeTab === 'general') {
            await saveGeneralInfo();
        } else if (activeTab === 'password') {
            await savePassword();
        } else if (activeTab === 'security') {
            await saveSecuritySettings();
        }
    }

    async function saveGeneralInfo() {
        const form = document.getElementById('profileGeneralForm');
        const formData = new FormData(form);
        
        // Add avatar if changed
        const avatarInput = document.getElementById('profileAvatarInput');
        if (avatarInput.files[0]) {
            formData.append('avatar', avatarInput.files[0]);
        }

        try {
            const response = await fetch(`${ADMIN_BASE}/api/update_profile.php`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Profile Updated',
                    text: 'Your profile has been updated successfully'
                });
                closeModal();
                
                // Reload page to reflect changes
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message || 'Update failed');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: error.message || 'Failed to update profile'
            });
        }
    }

    async function savePassword() {
        const form = document.getElementById('profilePasswordForm');
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Passwords Don\'t Match',
                text: 'Please make sure both passwords match'
            });
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch(`${ADMIN_BASE}/api/update_password.php`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Changed',
                    text: 'Your password has been changed successfully'
                });
                form.reset();
            } else {
                throw new Error(data.message || 'Password change failed');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: error.message || 'Failed to change password'
            });
        }
    }

    async function saveSecuritySettings() {
        const settings = {
            session_timeout: document.getElementById('sessionTimeout').checked,
            login_notifications: document.getElementById('loginNotifications').checked
        };

        try {
            const response = await fetch(`${ADMIN_BASE}/api/update_security.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(settings),
                credentials: 'same-origin'
            });

            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Settings Updated',
                    text: 'Your security settings have been updated'
                });
            } else {
                throw new Error(data.message || 'Update failed');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: error.message || 'Failed to update security settings'
            });
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProfileModal);
    } else {
        initProfileModal();
    }

    // Expose open function globally
    window.openProfileModal = openModal;
})();
