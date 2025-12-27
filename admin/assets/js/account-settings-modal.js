// Account Settings Modal - Unique, focused, no scrolling
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
          <button class="settings-tab active" data-tab="appearance"><i class='bx bx-paint'></i><span>Appearance</span></button>
          <button class="settings-tab" data-tab="notifications"><i class='bx bx-bell'></i><span>Notifications</span></button>
          <button class="settings-tab" data-tab="privacy"><i class='bx bx-lock'></i><span>Privacy</span></button>
        </div>
        <div class="settings-content">
          <div class="settings-pane active" data-pane="appearance">
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

  function save(){
    // Gather preferences
    const prefs = {
      theme: document.getElementById('settingsTheme')?.value || 'light',
      density: document.getElementById('settingsDensity')?.value || 'comfortable',
      reduceMotion: document.getElementById('settingsReduceMotion')?.checked || false,
      notifSystem: document.getElementById('settingsEmailSystem')?.checked || false,
      notifUsers: document.getElementById('settingsEmailUsers')?.checked || false,
      notifPayments: document.getElementById('settingsEmailPayments')?.checked || false,
      notifInApp: document.getElementById('settingsInAppAlerts')?.checked || false,
      showOnline: document.getElementById('settingsShowOnline')?.checked || false,
      showLastActive: document.getElementById('settingsShowLastActive')?.checked || false
    };
    // Store preferences locally
    try { localStorage.setItem('hq_admin_prefs', JSON.stringify(prefs)); } catch(e) {}
    
    if (window.Swal) {
      Swal.fire({icon:'success',title:'Saved',text:'Preferences saved locally',timer:1200,showConfirmButton:false});
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
        if(result.isConfirmed) window.location.href = window.HQ_ADMIN_BASE + '/pages/index.php?pages=support';
      });
    }
  }

  function initListeners(){
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    saveBtn?.addEventListener('click', save);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });

    const log = msg=>console.log('[account-settings]', msg);
    const bindClick = (id, msg)=>{ const el=document.getElementById(id); if(el) el.addEventListener('click', ()=>log(msg)); };
    bindClick('settingsDownloadData','download data');
    bindClick('settingsDeleteAccount','delete account');
  }

  window.openAccountSettings = open;
  window.accountSettingsModal = { open, close };
  initListeners();
})();
