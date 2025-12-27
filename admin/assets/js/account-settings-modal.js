// Plain JS Account Settings Modal (React-like UI without TS)
(function(){
  const tpl = `
  <div class="settings-modal" id="accountSettingsModal">
    <div class="settings-modal-content">
      <div class="settings-modal-header">
        <h2><i class='bx bx-cog'></i> Account Settings</h2>
        <button class="settings-modal-close" id="accountSettingsClose"><i class='bx bx-x'></i></button>
      </div>
      <div class="settings-modal-body">
        <div class="settings-tabs">
          <button class="settings-tab active" data-tab="general"><i class='bx bx-user-circle'></i><span>General</span></button>
          <button class="settings-tab" data-tab="security"><i class='bx bx-shield'></i><span>Security</span></button>
          <button class="settings-tab" data-tab="notifications"><i class='bx bx-bell'></i><span>Notifications</span></button>
          <button class="settings-tab" data-tab="privacy"><i class='bx bx-lock'></i><span>Privacy</span></button>
        </div>
        <div class="settings-content">
          <div class="settings-pane active" data-pane="general">
            <div class="settings-section">
              <h3>Profile Information</h3>
              <p class="settings-hint">Name and email are view-only; only the user can change them.</p>
              <div class="settings-item">
                <label>Display Name</label>
                <input type="text" id="settingsDisplayName" class="readonly-input" readonly>
                <small class="field-hint">Contact support to change your name</small>
              </div>
              <div class="settings-item">
                <label>Email Address</label>
                <input type="email" id="settingsEmail" class="readonly-input" readonly>
                <small class="field-hint">Contact support to change your email</small>
              </div>
              <div class="settings-item">
                <label>Role</label>
                <input type="text" id="settingsRole" class="readonly-input" readonly>
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
          <div class="settings-pane" data-pane="security">
            <div class="settings-section">
              <h3>Password</h3>
              <p class="settings-hint">Keep your account secure with a strong password</p>
              <div class="settings-item"><label>Current Password</label><input type="password" id="settingsCurrentPassword" placeholder="Current password"></div>
              <div class="settings-item"><label>New Password</label><input type="password" id="settingsNewPassword" placeholder="New password"></div>
              <div class="settings-item"><label>Confirm New Password</label><input type="password" id="settingsConfirmPassword" placeholder="Confirm password"></div>
              <button class="settings-btn-secondary" id="settingsChangePassword"><i class='bx bx-key'></i> Change Password</button>
            </div>
            <div class="settings-section">
              <h3>Two-Factor Authentication</h3>
              <p class="settings-hint">Add an extra layer of security</p>
              <div class="settings-toggle-item">
                <div class="toggle-info"><strong>Enable 2FA</strong><small>Require code when signing in</small></div>
                <label class="settings-toggle"><input type="checkbox" id="settings2FA"><span class="toggle-slider"></span></label>
              </div>
            </div>
            <div class="settings-section">
              <h3>Session Management</h3>
              <div class="settings-item">
                <label for="settingsSessionTimeout">Session Timeout (minutes)</label>
                <input type="number" id="settingsSessionTimeout" value="30" min="5" max="120">
                <small class="field-hint">Auto logout after inactivity</small>
              </div>
              <button class="settings-btn-danger" id="settingsLogoutAll"><i class='bx bx-log-out'></i> Logout All Other Devices</button>
            </div>
          </div>
          <div class="settings-pane" data-pane="notifications">
            <div class="settings-section">
              <h3>Email Notifications</h3>
              <p class="settings-hint">Choose what emails you receive</p>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>System Updates</strong><small>Maintenance and updates</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailSystem" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>New User Registrations</strong><small>Notify when users register</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailUsers" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Payment Notifications</strong><small>Payments and transactions</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailPayments" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Comment Moderation</strong><small>Moderate incoming comments</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailComments" checked><span class="toggle-slider"></span></label></div>
            </div>
            <div class="settings-section">
              <h3>SMS Notifications</h3>
              <p class="settings-hint">Receive critical alerts via SMS</p>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Critical Alerts</strong><small>Security and outages</small></div><label class="settings-toggle"><input type="checkbox" id="settingsSMSCritical"><span class="toggle-slider"></span></label></div>
            </div>
          </div>
          <div class="settings-pane" data-pane="privacy">
            <div class="settings-section">
              <h3>Profile Visibility</h3>
              <p class="settings-hint">Control who can see your status</p>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Show Online Status</strong><small>Let others see when you're online</small></div><label class="settings-toggle"><input type="checkbox" id="settingsShowOnline" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Show Last Active</strong><small>Display last active time</small></div><label class="settings-toggle"><input type="checkbox" id="settingsShowLastActive" checked><span class="toggle-slider"></span></label></div>
            </div>
            <div class="settings-section">
              <h3>Activity History</h3>
              <div class="settings-item"><button class="settings-btn-secondary" id="settingsViewHistory"><i class='bx bx-history'></i> View Activity Log</button></div>
              <div class="settings-item"><button class="settings-btn-danger" id="settingsClearHistory"><i class='bx bx-trash'></i> Clear Activity History</button></div>
            </div>
            <div class="settings-section">
              <h3>Data Management</h3>
              <button class="settings-btn-secondary" id="settingsDownloadData"><i class='bx bx-download'></i> Download My Data</button>
              <button class="settings-btn-danger" id="settingsDeleteAccount"><i class='bx bx-error'></i> Delete Account</button>
            </div>
          </div>
        </div>
      </div>
      <div class="settings-modal-footer">
        <button class="settings-btn-cancel" id="settingsCancel">Cancel</button>
        <button class="settings-btn-primary" id="settingsSave"><i class='bx bx-save'></i> Save Changes</button>
      </div>
    </div>
  </div>
  <div class="settings-modal-overlay" id="accountSettingsOverlay"></div>`;

  if (!document.getElementById('accountSettingsModal')) {
    document.body.insertAdjacentHTML('beforeend', tpl);
  }

  const modal = document.getElementById('accountSettingsModal');
  const overlay = document.getElementById('accountSettingsOverlay');
  const closeBtn = document.getElementById('accountSettingsClose');
  const cancelBtn = document.getElementById('settingsCancel');
  const saveBtn = document.getElementById('settingsSave');
  const tabs = Array.from(document.querySelectorAll('.settings-tab'));
  const panes = Array.from(document.querySelectorAll('.settings-pane'));

  function switchTab(name){
    tabs.forEach(t=>t.classList.toggle('active', t.dataset.tab===name));
    panes.forEach(p=>p.classList.toggle('active', p.dataset.pane===name));
  }
  tabs.forEach(t=>t.addEventListener('click', ()=>switchTab(t.dataset.tab)));

  function fillUserData(){
    const data = (window.HQ_USER_DATA) || {};
    const setVal = (id, val)=>{ const el=document.getElementById(id); if(el) el.value=val||''; };
    setVal('settingsDisplayName', data.name);
    setVal('settingsEmail', data.email);
    setVal('settingsRole', data.role);
  }

  function open(){
    fillUserData();
    modal?.classList.add('open');
    overlay?.classList.add('open');
    document.body.style.overflow='hidden';
  }

  function close(){
    modal?.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow='';
  }

  function save(){
    if (window.Swal) {
      Swal.fire({icon:'success',title:'Saved',text:'Preferences saved',timer:1500,showConfirmButton:false});
    }
    close();
  }

  function initListeners(){
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    saveBtn?.addEventListener('click', save);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });

    const log = msg=>console.log('[account-settings]', msg);
    const bindClick = (id, msg)=>{ const el=document.getElementById(id); if(el) el.addEventListener('click', ()=>log(msg)); };
    bindClick('settingsChangePassword','change password');
    bindClick('settingsLogoutAll','logout all devices');
    bindClick('settingsViewHistory','view history');
    bindClick('settingsClearHistory','clear history');
    bindClick('settingsDownloadData','download data');
    bindClick('settingsDeleteAccount','delete account');
  }

  window.openAccountSettings = open;
  window.accountSettingsModal = { open, close };
  initListeners();
})();
