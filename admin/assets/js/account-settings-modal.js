// Account Settings Modal - Unique, focused, no scrolling
(function(){
  const ADMIN_BASE = window.HQ_ADMIN_BASE || window.location.origin + '/admin';
  let accountVerified = false;
  let originalPhone = '';

  async function parseJsonSafe(response) {
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error('Unexpected server response. Please refresh and try again.');
    }
  }

  function requireVerificationMessage() {
    return 'Verify with your registered Gmail/email before updating password, email, or phone.';
  }

  const tpl = `
  <div class="settings-modal" id="accountSettingsModal">
    <div class="settings-modal-content">
      <div class="settings-modal-header">
        <h2><i class='bx bx-cog'></i> Profile & Account Settings</h2>
        <button class="settings-modal-close" id="accountSettingsClose"><i class='bx bx-x'></i></button>
      </div>
      <div class="settings-modal-body">
        <div class="settings-tabs">
          <button class="settings-tab active" data-tab="account"><i class='bx bx-user'></i><span>Account</span></button>
          <button class="settings-tab" data-tab="security"><i class='bx bx-shield'></i><span>Security</span></button>
          <button class="settings-tab" data-tab="appearance"><i class='bx bx-paint'></i><span>Appearance</span></button>
          <button class="settings-tab" data-tab="notifications"><i class='bx bx-bell'></i><span>Notifications</span></button>
          <button class="settings-tab" data-tab="privacy"><i class='bx bx-lock'></i><span>Privacy</span></button>
        </div>
        <div class="settings-content">
          <div class="settings-pane active" data-pane="account">
            <div class="settings-section">
              <h3>Profile</h3>
              <div class="settings-item">
                <label for="settingsName">Full Name</label>
                <input type="text" id="settingsName" placeholder="Your full name">
              </div>
              <div class="settings-item">
                <label for="settingsEmail">Current Email</label>
                <input type="email" id="settingsEmail" class="readonly-input" readonly>
              </div>
              <div class="settings-item">
                <label for="settingsNewEmail">New Email (optional)</label>
                <input type="email" id="settingsNewEmail" placeholder="new@email.com">
              </div>
              <div class="settings-item">
                <label for="settingsPhone">Phone Number</label>
                <input type="text" id="settingsPhone" placeholder="Phone number">
              </div>
              <div class="settings-item">
                <label>Email Verification</label>
                <div class="settings-inline">
                  <button class="settings-btn-secondary" id="settingsSendCode" type="button"><i class='bx bx-mail-send'></i> Send Code</button>
                  <input type="text" id="settingsVerifyCode" placeholder="Enter code">
                  <button class="settings-btn-primary" id="settingsVerifyBtn" type="button"><i class='bx bx-check'></i> Verify</button>
                </div>
                <small class="field-hint">Important changes require verification from your registered Gmail/email first.</small>
              </div>
              <div class="settings-item">
                <button class="settings-btn-primary" id="settingsSaveProfile" type="button"><i class='bx bx-save'></i> Save Profile</button>
              </div>
            </div>
            <div class="settings-section">
              <h3>Password</h3>
              <div class="settings-item">
                <label for="settingsCurrentPassword">Current Password</label>
                <input type="password" id="settingsCurrentPassword" autocomplete="current-password">
              </div>
              <div class="settings-item">
                <label for="settingsNewPassword">New Password</label>
                <input type="password" id="settingsNewPassword" autocomplete="new-password">
              </div>
              <div class="settings-item">
                <label for="settingsConfirmPassword">Confirm New Password</label>
                <input type="password" id="settingsConfirmPassword" autocomplete="new-password">
              </div>
              <div class="settings-item">
                <button class="settings-btn-primary" id="settingsChangePassword" type="button"><i class='bx bx-lock'></i> Change Password</button>
              </div>
            </div>
          </div>
          <div class="settings-pane" data-pane="security">
            <div class="settings-section">
              <h3>Account Security</h3>
              <div class="settings-toggle-item">
                <div class="toggle-info"><strong>Session Timeout</strong><small>Auto log out after inactivity</small></div>
                <label class="settings-toggle"><input type="checkbox" id="settingsSessionTimeout"><span class="toggle-slider"></span></label>
              </div>
              <div class="settings-toggle-item">
                <div class="toggle-info"><strong>Login Notifications</strong><small>Email alerts for logins</small></div>
                <label class="settings-toggle"><input type="checkbox" id="settingsLoginNotifications"><span class="toggle-slider"></span></label>
              </div>
            </div>
          </div>
          <div class="settings-pane" data-pane="appearance">
            <div class="settings-section">
              <h3>Theme</h3>
              <div class="settings-item">
                <label for="settingsTheme">Color Theme</label>
                <select id="settingsTheme">
                  <option value="system">System</option>
                  <option value="light" selected>Light</option>
                  <option value="dark">Dark</option>
                </select>
              </div>
              <div class="settings-item">
                <label for="settingsDensity">Layout Density</label>
                <select id="settingsDensity">
                  <option value="comfortable" selected>Comfortable</option>
                  <option value="compact">Compact</option>
                </select>
              </div>
              <div class="settings-toggle-item">
                <div class="toggle-info"><strong>Reduce Motion</strong><small>Minimize transitions and animations</small></div>
                <label class="settings-toggle"><input type="checkbox" id="settingsReduceMotion"><span class="toggle-slider"></span></label>
              </div>
              <div class="settings-item">
                <label for="settingsAnimationMode">Animation Mode</label>
                <select id="settingsAnimationMode">
                  <option value="auto">Auto (detect device)</option>
                  <option value="minimal">Minimal</option>
                  <option value="full">Full</option>
                </select>
                <button class="settings-btn-secondary" id="settingsAnimationApply" style="margin-top:8px;"><i class='bx bx-magic-wand'></i> Apply Now</button>
              </div>
            </div>
          </div>
          <div class="settings-pane" data-pane="notifications">
            <div class="settings-section">
              <h3>Email</h3>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>System Updates</strong><small>Maintenance and releases</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailSystem" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Registrations</strong><small>New user signups</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailUsers" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Payments</strong><small>Transactions and receipts</small></div><label class="settings-toggle"><input type="checkbox" id="settingsEmailPayments" checked><span class="toggle-slider"></span></label></div>
            </div>
            <div class="settings-section">
              <h3>Real‑Time</h3>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>In‑app alerts</strong><small>Show toast notifications</small></div><label class="settings-toggle"><input type="checkbox" id="settingsInAppAlerts" checked><span class="toggle-slider"></span></label></div>
            </div>
          </div>
          <div class="settings-pane" data-pane="privacy">
            <div class="settings-section">
              <h3>Visibility</h3>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Show Online Status</strong><small>Let others see when you're online</small></div><label class="settings-toggle"><input type="checkbox" id="settingsShowOnline" checked><span class="toggle-slider"></span></label></div>
              <div class="settings-toggle-item"><div class="toggle-info"><strong>Show Last Active</strong><small>Display last active time</small></div><label class="settings-toggle"><input type="checkbox" id="settingsShowLastActive" checked><span class="toggle-slider"></span></label></div>
            </div>
            <div class="settings-section">
              <h3>Data</h3>
              <div class="settings-item"><button class="settings-btn-secondary" id="settingsDownloadData"><i class='bx bx-download'></i> Download My Data</button></div>
              <div class="settings-item"><button class="settings-btn-danger" id="settingsDeleteAccount"><i class='bx bx-error'></i> Delete Account</button></div>
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

  function open(){
    modal?.classList.add('open');
    overlay?.classList.add('open');
    document.body.style.overflow='hidden';
  }

  function close(){
    modal?.classList.remove('open');
    overlay?.classList.remove('open');
    document.body.style.overflow='';
  }

  function applyAnimationMode(mode){
    if (window.HQDeviceCapability && typeof window.HQDeviceCapability.setPreference === 'function') {
      window.HQDeviceCapability.setPreference(mode);
    } else {
      try { localStorage.setItem('hq_admin_animation_preference', mode); } catch(e) {}
    }
  }

  async function save(){
    // Gather preferences
    const prefs = {
      theme: document.getElementById('settingsTheme')?.value || 'light',
      density: document.getElementById('settingsDensity')?.value || 'comfortable',
      reduceMotion: document.getElementById('settingsReduceMotion')?.checked || false,
      animationMode: document.getElementById('settingsAnimationMode')?.value || 'auto',
      notifSystem: document.getElementById('settingsEmailSystem')?.checked || false,
      notifUsers: document.getElementById('settingsEmailUsers')?.checked || false,
      notifPayments: document.getElementById('settingsEmailPayments')?.checked || false,
      notifInApp: document.getElementById('settingsInAppAlerts')?.checked || false,
      showOnline: document.getElementById('settingsShowOnline')?.checked || false,
      showLastActive: document.getElementById('settingsShowLastActive')?.checked || false,
      session_timeout: document.getElementById('settingsSessionTimeout')?.checked || false,
      login_notifications: document.getElementById('settingsLoginNotifications')?.checked || false
    };
    // Store preferences locally
    try { localStorage.setItem('hq_admin_prefs', JSON.stringify(prefs)); } catch(e) {}
    // Apply animation preference immediately
    applyAnimationMode(prefs.animationMode);
    
    try {
      await fetch(ADMIN_BASE + '/api/update_account_preferences.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(prefs),
        credentials: 'same-origin'
      });
    } catch (e) {}

    if (window.Swal) {
      Swal.fire({icon:'success',title:'Saved',text:'Preferences updated',timer:1200,showConfirmButton:false});
    }
    close();
  }

  function downloadData(){
    if (window.Swal) {
      Swal.fire({title:'Download Data',text:'Request sent to admin. You will receive an email with your data export.',icon:'info'});
    }
  }

  function deleteAccount(){
    if (window.Swal) {
      Swal.fire({title:'Delete Account',text:'This action cannot be undone. Contact support to proceed.',icon:'warning',showCancelButton:true,confirmButtonText:'Contact Support'}).then((result)=>{
        if(result.isConfirmed) window.location.href = ADMIN_BASE + '/pages/index.php?pages=support';
      });
    }
  }

  async function loadProfile(){
    try {
      const resp = await fetch(ADMIN_BASE + '/api/user_profile.php', { credentials: 'same-origin' });
      const data = await parseJsonSafe(resp);
      const nameEl = document.getElementById('settingsName');
      const emailEl = document.getElementById('settingsEmail');
      const phoneEl = document.getElementById('settingsPhone');
      if (nameEl) nameEl.value = data.name || '';
      if (emailEl) emailEl.value = data.email || '';
      if (phoneEl) phoneEl.value = data.phone || '';
      originalPhone = data.phone || '';

      const prefs = data.preferences || {};
      const setVal = (id,val)=>{ const el=document.getElementById(id); if(el) el.value=val; };
      const setCheck = (id,val)=>{ const el=document.getElementById(id); if(el) el.checked=!!val; };

      if (Object.keys(prefs).length > 0) {
        setVal('settingsTheme', prefs.theme || 'light');
        setVal('settingsDensity', prefs.density || 'comfortable');
        setCheck('settingsReduceMotion', prefs.reduceMotion);
        setVal('settingsAnimationMode', prefs.animationMode || 'auto');
        setCheck('settingsEmailSystem', prefs.notifSystem);
        setCheck('settingsEmailUsers', prefs.notifUsers);
        setCheck('settingsEmailPayments', prefs.notifPayments);
        setCheck('settingsInAppAlerts', prefs.notifInApp);
        setCheck('settingsShowOnline', prefs.showOnline);
        setCheck('settingsShowLastActive', prefs.showLastActive);
        setCheck('settingsSessionTimeout', prefs.session_timeout);
        setCheck('settingsLoginNotifications', prefs.login_notifications);
        try { localStorage.setItem('hq_admin_prefs', JSON.stringify(prefs)); } catch(e) {}
      }
    } catch (e) {}
  }

  function initListeners(){
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    saveBtn?.addEventListener('click', save);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });

    const downloadBtn = document.getElementById('settingsDownloadData');
    if(downloadBtn) downloadBtn.addEventListener('click', downloadData);
    const deleteBtn = document.getElementById('settingsDeleteAccount');
    if(deleteBtn) deleteBtn.addEventListener('click', deleteAccount);

    const animSelect = document.getElementById('settingsAnimationMode');
    const animApply = document.getElementById('settingsAnimationApply');
    if (animApply) {
      animApply.addEventListener('click', function(e){
        e.preventDefault();
        const mode = animSelect?.value || 'auto';
        applyAnimationMode(mode);
        if (window.Swal) Swal.fire({icon:'success',title:'Applied',text:'Animation preference updated',timer:1000,showConfirmButton:false});
      });
    }
    // Also toggle reduce motion checkbox to minimal mode when checked
    const reduceMotion = document.getElementById('settingsReduceMotion');
    if (reduceMotion) {
      reduceMotion.addEventListener('change', function(){
        if (reduceMotion.checked) {
          if (animSelect) animSelect.value = 'minimal';
          applyAnimationMode('minimal');
        }
      });
    }
    
    // Load saved preferences on open
    try {
      const saved = localStorage.getItem('hq_admin_prefs');
      if(saved){
        const prefs = JSON.parse(saved);
        const setVal = (id,val)=>{ const el=document.getElementById(id); if(el) el.value=val; };
        const setCheck = (id,val)=>{ const el=document.getElementById(id); if(el) el.checked=!!val; };
        setVal('settingsTheme', prefs.theme||'light');
        setVal('settingsDensity', prefs.density||'comfortable');
        setCheck('settingsReduceMotion', prefs.reduceMotion);
        setVal('settingsAnimationMode', prefs.animationMode || 'auto');
        setCheck('settingsEmailSystem', prefs.notifSystem);
        setCheck('settingsEmailUsers', prefs.notifUsers);
        setCheck('settingsEmailPayments', prefs.notifPayments);
        setCheck('settingsInAppAlerts', prefs.notifInApp);
        setCheck('settingsShowOnline', prefs.showOnline);
        setCheck('settingsShowLastActive', prefs.showLastActive);
      }
    } catch(e){}

    // Account actions
    const sendCodeBtn = document.getElementById('settingsSendCode');
    const verifyBtn = document.getElementById('settingsVerifyBtn');
    const saveProfileBtn = document.getElementById('settingsSaveProfile');
    const changePassBtn = document.getElementById('settingsChangePassword');

    sendCodeBtn?.addEventListener('click', async () => {
      const currentEmail = document.getElementById('settingsEmail')?.value || '';
      if (!currentEmail) return;
      try {
        const res = await fetch(ADMIN_BASE + '/api/send_verification_code.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ type: 'email', value: currentEmail, purpose: 'account' }),
          credentials: 'same-origin'
        });
        const data = await parseJsonSafe(res);
        if (!data.success) throw new Error(data.message || 'Failed to send code');
        accountVerified = false;
        if (window.Swal) Swal.fire({icon:'success',title:'Code Sent',text:'Check your email for the verification code.'});
      } catch (e) {
        if (window.Swal) Swal.fire({icon:'error',title:'Failed',text:e.message});
      }
    });

    verifyBtn?.addEventListener('click', async () => {
      const code = document.getElementById('settingsVerifyCode')?.value || '';
      if (!code) return;
      try {
        const res = await fetch(ADMIN_BASE + '/api/verify_code.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code }),
          credentials: 'same-origin'
        });
        const data = await parseJsonSafe(res);
        if (!data.success) throw new Error(data.message || 'Verification failed');
        accountVerified = true;
        if (window.Swal) Swal.fire({icon:'success',title:'Verified',text:'You can now update your profile or password.'});
      } catch (e) {
        if (window.Swal) Swal.fire({icon:'error',title:'Failed',text:e.message});
      }
    });

    saveProfileBtn?.addEventListener('click', async () => {
      const name = document.getElementById('settingsName')?.value || '';
      const newEmail = document.getElementById('settingsNewEmail')?.value || '';
      const phone = document.getElementById('settingsPhone')?.value || '';
      const currentEmail = document.getElementById('settingsEmail')?.value || '';

      const changingSensitive = (newEmail && newEmail !== currentEmail) || (phone && phone !== originalPhone);
      if (changingSensitive && !accountVerified) {
        if (window.Swal) Swal.fire({icon:'warning',title:'Verification Required',text:requireVerificationMessage()});
        return;
      }

      const formData = new FormData();
      formData.append('name', name);
      if (newEmail) formData.append('email', newEmail);
      if (phone) formData.append('phone', phone);

      try {
        const res = await fetch(ADMIN_BASE + '/api/update_profile.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await parseJsonSafe(res);
        if (!data.success) throw new Error(data.message || 'Update failed');
        if (window.Swal) Swal.fire({icon:'success',title:'Saved',text:data.message});
        document.getElementById('settingsNewEmail').value = '';
        accountVerified = false;
        await loadProfile();
      } catch (e) {
        if (window.Swal) Swal.fire({icon:'error',title:'Failed',text:e.message});
      }
    });

    changePassBtn?.addEventListener('click', async () => {
      if (!accountVerified) {
        if (window.Swal) Swal.fire({icon:'warning',title:'Verification Required',text:requireVerificationMessage()});
        return;
      }

      const currentPassword = document.getElementById('settingsCurrentPassword')?.value || '';
      const newPassword = document.getElementById('settingsNewPassword')?.value || '';
      const confirmPassword = document.getElementById('settingsConfirmPassword')?.value || '';
      const formData = new FormData();
      formData.append('current_password', currentPassword);
      formData.append('new_password', newPassword);
      formData.append('confirm_password', confirmPassword);
      try {
        const res = await fetch(ADMIN_BASE + '/api/update_password.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await parseJsonSafe(res);
        if (!data.success) throw new Error(data.message || 'Password update failed');
        if (window.Swal) Swal.fire({icon:'success',title:'Updated',text:data.message});
        document.getElementById('settingsCurrentPassword').value = '';
        document.getElementById('settingsNewPassword').value = '';
        document.getElementById('settingsConfirmPassword').value = '';
        accountVerified = false;
      } catch (e) {
        if (window.Swal) Swal.fire({icon:'error',title:'Failed',text:e.message});
      }
    });

    // If no saved pref, sync from device capability current mode
    if (!localStorage.getItem('hq_admin_prefs') && animSelect && window.HQDeviceCapability && typeof window.HQDeviceCapability.getPreference === 'function') {
      const current = window.HQDeviceCapability.getPreference();
      if (current) animSelect.value = current;
    }
  }

  window.openAccountSettings = function(){
    open();
    accountVerified = false;
    loadProfile();
  };
  window.openProfileModal = window.openAccountSettings;
  window.accountSettingsModal = { open, close };
  initListeners();
})();
