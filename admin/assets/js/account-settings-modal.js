/**
 * Account Settings Modal Component - React TSX-driven
 * Modern, responsive account settings modal for admin panel
 */

interface AccountSettings {
  twoFactorEnabled: boolean;
  emailNotifications: boolean;
  smsNotifications: boolean;
  sessionTimeout: number;
}

interface ModalState {
  isOpen: boolean;
  activeTab: 'general' | 'security' | 'notifications' | 'privacy';
  settings: AccountSettings;
  isLoading: boolean;
  isSaving: boolean;
}

class AccountSettingsModal {
  private state: ModalState;
  private modal: HTMLElement | null = null;
  private overlay: HTMLElement | null = null;
  
  constructor() {
    this.state = {
      isOpen: false,
      activeTab: 'general',
      settings: {
        twoFactorEnabled: false,
        emailNotifications: true,
        smsNotifications: false,
        sessionTimeout: 30
      },
      isLoading: false,
      isSaving: false
    };
    
    this.init();
  }
  
  private init(): void {
    // Create modal structure
    this.createModalHTML();
    
    // Bind elements
    this.modal = document.getElementById('accountSettingsModal');
    this.overlay = document.getElementById('accountSettingsOverlay');
    
    // Attach event listeners
    this.attachEventListeners();
  }
  
  private createModalHTML(): void {
    const modalHTML = `
      <!-- Account Settings Modal -->
      <div class="settings-modal" id="accountSettingsModal">
        <div class="settings-modal-content">
          <div class="settings-modal-header">
            <h2><i class='bx bx-cog'></i> Account Settings</h2>
            <button class="settings-modal-close" id="accountSettingsClose">
              <i class='bx bx-x'></i>
            </button>
          </div>
          
          <div class="settings-modal-body">
            <!-- Tabs -->
            <div class="settings-tabs">
              <button class="settings-tab active" data-tab="general">
                <i class='bx bx-user-circle'></i>
                <span>General</span>
              </button>
              <button class="settings-tab" data-tab="security">
                <i class='bx bx-shield'></i>
                <span>Security</span>
              </button>
              <button class="settings-tab" data-tab="notifications">
                <i class='bx bx-bell'></i>
                <span>Notifications</span>
              </button>
              <button class="settings-tab" data-tab="privacy">
                <i class='bx bx-lock'></i>
                <span>Privacy</span>
              </button>
            </div>
            
            <!-- Tab Content -->
            <div class="settings-content">
              <!-- General Tab -->
              <div class="settings-pane active" data-pane="general">
                <div class="settings-section">
                  <h3>Profile Information</h3>
                  <p class="settings-hint">Your basic account information (name and email can only be changed by you)</p>
                  
                  <div class="settings-item">
                    <label>Display Name</label>
                    <input type="text" id="settingsDisplayName" value="" readonly class="readonly-input">
                    <small class="field-hint">Contact support to change your name</small>
                  </div>
                  
                  <div class="settings-item">
                    <label>Email Address</label>
                    <input type="email" id="settingsEmail" value="" readonly class="readonly-input">
                    <small class="field-hint">Contact support to change your email</small>
                  </div>
                  
                  <div class="settings-item">
                    <label>Role</label>
                    <input type="text" id="settingsRole" value="" readonly class="readonly-input">
                  </div>
                </div>
                
                <div class="settings-section">
                  <h3>Preferences</h3>
                  
                  <div class="settings-item">
                    <label for="settingsLanguage">Language</label>
                    <select id="settingsLanguage">
                      <option value="en" selected>English</option>
                      <option value="fr">French</option>
                      <option value="es">Spanish</option>
                    </select>
                  </div>
                  
                  <div class="settings-item">
                    <label for="settingsTimezone">Timezone</label>
                    <select id="settingsTimezone">
                      <option value="UTC" selected>UTC</option>
                      <option value="America/New_York">Eastern Time</option>
                      <option value="America/Los_Angeles">Pacific Time</option>
                      <option value="Africa/Lagos">West Africa Time</option>
                    </select>
                  </div>
                </div>
              </div>
              
              <!-- Security Tab -->
              <div class="settings-pane" data-pane="security">
                <div class="settings-section">
                  <h3>Password</h3>
                  <p class="settings-hint">Keep your account secure with a strong password</p>
                  
                  <div class="settings-item">
                    <label>Current Password</label>
                    <input type="password" id="settingsCurrentPassword" placeholder="Enter current password">
                  </div>
                  
                  <div class="settings-item">
                    <label>New Password</label>
                    <input type="password" id="settingsNewPassword" placeholder="Enter new password">
                  </div>
                  
                  <div class="settings-item">
                    <label>Confirm New Password</label>
                    <input type="password" id="settingsConfirmPassword" placeholder="Confirm new password">
                  </div>
                  
                  <button class="settings-btn-secondary" onclick="accountSettingsModal.changePassword()">
                    <i class='bx bx-key'></i> Change Password
                  </button>
                </div>
                
                <div class="settings-section">
                  <h3>Two-Factor Authentication</h3>
                  <p class="settings-hint">Add an extra layer of security to your account</p>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>Enable 2FA</strong>
                      <small>Require a verification code when signing in</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settings2FA">
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
                
                <div class="settings-section">
                  <h3>Session Management</h3>
                  
                  <div class="settings-item">
                    <label for="settingsSessionTimeout">Session Timeout (minutes)</label>
                    <input type="number" id="settingsSessionTimeout" value="30" min="5" max="120">
                    <small class="field-hint">Automatically log out after period of inactivity</small>
                  </div>
                  
                  <button class="settings-btn-danger" onclick="accountSettingsModal.logoutAllDevices()">
                    <i class='bx bx-log-out'></i> Logout All Other Devices
                  </button>
                </div>
              </div>
              
              <!-- Notifications Tab -->
              <div class="settings-pane" data-pane="notifications">
                <div class="settings-section">
                  <h3>Email Notifications</h3>
                  <p class="settings-hint">Choose what email notifications you receive</p>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>System Updates</strong>
                      <small>Get notified about system updates and maintenance</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsEmailSystem" checked>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>New User Registrations</strong>
                      <small>Get notified when new users register</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsEmailUsers" checked>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>Payment Notifications</strong>
                      <small>Get notified about new payments and transactions</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsEmailPayments" checked>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>Comment Moderation</strong>
                      <small>Get notified when comments need moderation</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsEmailComments" checked>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
                
                <div class="settings-section">
                  <h3>SMS Notifications</h3>
                  <p class="settings-hint">Receive important alerts via SMS</p>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>Critical Alerts</strong>
                      <small>Security alerts and critical system issues</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsSMSCritical">
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
              </div>
              
              <!-- Privacy Tab -->
              <div class="settings-pane" data-pane="privacy">
                <div class="settings-section">
                  <h3>Profile Visibility</h3>
                  <p class="settings-hint">Control who can see your profile information</p>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>Show Online Status</strong>
                      <small>Let others see when you're online</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsShowOnline" checked>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                  
                  <div class="settings-toggle-item">
                    <div class="toggle-info">
                      <strong>Show Last Active</strong>
                      <small>Display when you were last active</small>
                    </div>
                    <label class="settings-toggle">
                      <input type="checkbox" id="settingsShowLastActive" checked>
                      <span class="toggle-slider"></span>
                    </label>
                  </div>
                </div>
                
                <div class="settings-section">
                  <h3>Activity History</h3>
                  
                  <div class="settings-item">
                    <button class="settings-btn-secondary" onclick="accountSettingsModal.viewActivityLog()">
                      <i class='bx bx-history'></i> View Activity Log
                    </button>
                  </div>
                  
                  <div class="settings-item">
                    <button class="settings-btn-danger" onclick="accountSettingsModal.clearActivityHistory()">
                      <i class='bx bx-trash'></i> Clear Activity History
                    </button>
                  </div>
                </div>
                
                <div class="settings-section">
                  <h3>Data Management</h3>
                  
                  <button class="settings-btn-secondary" onclick="accountSettingsModal.downloadData()">
                    <i class='bx bx-download'></i> Download My Data
                  </button>
                  
                  <button class="settings-btn-danger" onclick="accountSettingsModal.deleteAccount()">
                    <i class='bx bx-error'></i> Delete Account
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="settings-modal-footer">
            <button class="settings-btn-cancel" onclick="accountSettingsModal.close()">
              Cancel
            </button>
            <button class="settings-btn-primary" onclick="accountSettingsModal.saveSettings()">
              <i class='bx bx-save'></i> Save Changes
            </button>
          </div>
        </div>
      </div>
      
      <!-- Modal Overlay -->
      <div class="settings-modal-overlay" id="accountSettingsOverlay"></div>
    `;
    
    // Append to body
    if (!document.getElementById('accountSettingsModal')) {
      document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
  }
  
  private attachEventListeners(): void {
    // Close button
    const closeBtn = document.getElementById('accountSettingsClose');
    closeBtn?.addEventListener('click', () => this.close());
    
    // Overlay click to close
    this.overlay?.addEventListener('click', () => this.close());
    
    // Escape key to close
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.state.isOpen) {
        this.close();
      }
    });
    
    // Tab switching
    const tabs = document.querySelectorAll('.settings-tab');
    tabs.forEach(tab => {
      tab.addEventListener('click', (e) => {
        const target = e.currentTarget as HTMLElement;
        const tabName = target.dataset.tab as ModalState['activeTab'];
        this.switchTab(tabName);
      });
    });
  }
  
  public open(): void {
    this.state.isOpen = true;
    this.modal?.classList.add('open');
    this.overlay?.classList.add('open');
    document.body.style.overflow = 'hidden';
    
    // Load user data
    this.loadUserData();
  }
  
  public close(): void {
    this.state.isOpen = false;
    this.modal?.classList.remove('open');
    this.overlay?.classList.remove('open');
    document.body.style.overflow = '';
  }
  
  private switchTab(tabName: ModalState['activeTab']): void {
    this.state.activeTab = tabName;
    
    // Update tab buttons
    document.querySelectorAll('.settings-tab').forEach(tab => {
      tab.classList.toggle('active', (tab as HTMLElement).dataset.tab === tabName);
    });
    
    // Update panes
    document.querySelectorAll('.settings-pane').forEach(pane => {
      pane.classList.toggle('active', (pane as HTMLElement).dataset.pane === tabName);
    });
  }
  
  private async loadUserData(): Promise<void> {
    this.state.isLoading = true;
    
    // Get user data from session (injected via PHP)
    const userData = (window as any).HQ_USER_DATA || {};
    
    const displayNameInput = document.getElementById('settingsDisplayName') as HTMLInputElement;
    const emailInput = document.getElementById('settingsEmail') as HTMLInputElement;
    const roleInput = document.getElementById('settingsRole') as HTMLInputElement;
    
    if (displayNameInput) displayNameInput.value = userData.name || '';
    if (emailInput) emailInput.value = userData.email || '';
    if (roleInput) roleInput.value = userData.role || '';
    
    this.state.isLoading = false;
  }
  
  public async saveSettings(): Promise<void> {
    if (this.state.isSaving) return;
    
    this.state.isSaving = true;
    
    // Show loading state
    const saveBtn = document.querySelector('.settings-btn-primary');
    if (saveBtn) {
      (saveBtn as HTMLButtonElement).disabled = true;
      saveBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
    }
    
    try {
      // Collect settings data
      const formData = new FormData();
      formData.append('language', (document.getElementById('settingsLanguage') as HTMLSelectElement)?.value || 'en');
      formData.append('timezone', (document.getElementById('settingsTimezone') as HTMLSelectElement)?.value || 'UTC');
      formData.append('session_timeout', (document.getElementById('settingsSessionTimeout') as HTMLInputElement)?.value || '30');
      formData.append('2fa_enabled', (document.getElementById('settings2FA') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('email_system', (document.getElementById('settingsEmailSystem') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('email_users', (document.getElementById('settingsEmailUsers') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('email_payments', (document.getElementById('settingsEmailPayments') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('email_comments', (document.getElementById('settingsEmailComments') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('sms_critical', (document.getElementById('settingsSMSCritical') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('show_online', (document.getElementById('settingsShowOnline') as HTMLInputElement)?.checked ? '1' : '0');
      formData.append('show_last_active', (document.getElementById('settingsShowLastActive') as HTMLInputElement)?.checked ? '1' : '0');
      
      // Make API call (placeholder - implement actual endpoint)
      const response = await fetch((window as any).HQ_ADMIN_BASE + '/api/save_settings.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });
      
      if (response.ok) {
        // Show success message
        if (typeof (window as any).Swal !== 'undefined') {
          (window as any).Swal.fire({
            icon: 'success',
            title: 'Settings Saved',
            text: 'Your account settings have been updated successfully.',
            timer: 2000,
            showConfirmButton: false
          });
        }
        
        this.close();
      } else {
        throw new Error('Failed to save settings');
      }
    } catch (error) {
      console.error('Error saving settings:', error);
      
      if (typeof (window as any).Swal !== 'undefined') {
        (window as any).Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to save settings. Please try again.',
        });
      } else {
        alert('Failed to save settings. Please try again.');
      }
    } finally {
      this.state.isSaving = false;
      
      // Reset button
      if (saveBtn) {
        (saveBtn as HTMLButtonElement).disabled = false;
        saveBtn.innerHTML = '<i class="bx bx-save"></i> Save Changes';
      }
    }
  }
  
  public async changePassword(): Promise<void> {
    const currentPassword = (document.getElementById('settingsCurrentPassword') as HTMLInputElement)?.value;
    const newPassword = (document.getElementById('settingsNewPassword') as HTMLInputElement)?.value;
    const confirmPassword = (document.getElementById('settingsConfirmPassword') as HTMLInputElement)?.value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
      if (typeof (window as any).Swal !== 'undefined') {
        (window as any).Swal.fire({
          icon: 'warning',
          title: 'Missing Fields',
          text: 'Please fill in all password fields.',
        });
      } else {
        alert('Please fill in all password fields.');
      }
      return;
    }
    
    if (newPassword !== confirmPassword) {
      if (typeof (window as any).Swal !== 'undefined') {
        (window as any).Swal.fire({
          icon: 'error',
          title: 'Passwords Do Not Match',
          text: 'The new password and confirmation do not match.',
        });
      } else {
        alert('The new password and confirmation do not match.');
      }
      return;
    }
    
    // Implement password change logic
    console.log('Password change requested');
  }
  
  public async logoutAllDevices(): Promise<void> {
    if (typeof (window as any).Swal !== 'undefined') {
      const result = await (window as any).Swal.fire({
        title: 'Logout All Devices?',
        text: 'This will log you out of all devices except this one.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, logout all',
        cancelButtonText: 'Cancel'
      });
      
      if (result.isConfirmed) {
        console.log('Logout all devices');
      }
    }
  }
  
  public viewActivityLog(): void {
    console.log('View activity log');
  }
  
  public clearActivityHistory(): void {
    console.log('Clear activity history');
  }
  
  public downloadData(): void {
    console.log('Download user data');
  }
  
  public async deleteAccount(): Promise<void> {
    if (typeof (window as any).Swal !== 'undefined') {
      const result = await (window as any).Swal.fire({
        title: 'Delete Account?',
        text: 'This action cannot be undone. All your data will be permanently deleted.',
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete my account',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444'
      });
      
      if (result.isConfirmed) {
        console.log('Delete account confirmed');
      }
    }
  }
}

// Initialize modal globally
let accountSettingsModal: AccountSettingsModal;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    accountSettingsModal = new AccountSettingsModal();
  });
} else {
  accountSettingsModal = new AccountSettingsModal();
}

// Global function to open modal (called from header)
function openAccountSettings() {
  accountSettingsModal.open();
}

// Expose to window for inline onclick
(window as any).accountSettingsModal = accountSettingsModal;
(window as any).openAccountSettings = openAccountSettings;
