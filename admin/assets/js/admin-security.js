// admin/assets/js/admin-security.js
(function(){
    function start(){
        // ensure small helper styles are available in modals (keeps assets self-contained)
        var styleId = 'hq-admin-security-styles';
        if (!document.getElementById(styleId)) {
            var s = document.createElement('style');
            s.id = styleId;
            s.innerHTML = '.hq-table{width:100%;border-collapse:collapse}.hq-table th,.hq-table td{padding:8px;border-bottom:1px solid #eee;text-align:left}.hq-table th{background:#fafafa;font-weight:700}.hq-btn{padding:6px 8px;border-radius:6px;border:1px solid #ddd;background:#fff;cursor:pointer}.hq-btn.danger{background:#ffefef;border-color:#f5c6cb;color:#a94442}';
            document.head.appendChild(s);
        }
        if (typeof Swal === 'undefined') return; // SweetAlert required

        function ajaxJson(method, url, data, cb){
            var xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
            xhr.onload = function(){
                try { var j = JSON.parse(xhr.responseText); cb(null,j); }
                catch(e){ cb(new Error('Invalid JSON response')); }
            };
            xhr.onerror = function(){ cb(new Error('Network error')); };
            if (data instanceof FormData) xhr.send(data); else xhr.send(data || null);
        }

        // Open MAC manager modal
        var macBtn = document.getElementById('openMacManager');
        if (macBtn) macBtn.addEventListener('click', function(){
            Swal.fire({
                title: 'MAC Blocklist',
                html: '<div id="mac-list" style="text-align:left;max-height:300px;overflow:auto"></div>'+
                      '<div style="margin-top:10px;display:flex;gap:8px;">'+
                      '<input id="newMac" placeholder="MAC or Identifier" class="swal2-input" />'+
                      '<input id="newReason" placeholder="Reason (optional)" class="swal2-input" />'+
                      '</div>',
                showCancelButton: true,
                confirmButtonText: 'Add',
                preConfirm: function(){
                    var mac = document.getElementById('newMac').value.trim();
                    var reason = document.getElementById('newReason').value.trim();
                    if (!mac) { Swal.showValidationMessage('Enter a MAC / identifier'); return false; }
                    return { mac: mac, reason: reason };
                },
                didOpen: function(){
                    // load list
                    ajaxJson('GET',(window.HQ_ADMIN_BASE || '') + '/api/mac_blocklist.php', null, function(err,res){
                        var cont = document.getElementById('mac-list');
                        if (err || !res || !res.rows) { cont.innerHTML = '<div class="muted">Failed to load list</div>'; return; }
                        var html = '<table class="hq-table" style="width:100%;"><thead><tr><th>MAC</th><th>Reason</th><th>Enabled</th><th>Actions</th></tr></thead><tbody>';
                        res.rows.forEach(function(r){
                html += '<tr><td>'+Swal.escapeHtml(r.mac)+'</td><td>'+Swal.escapeHtml(r.reason||'')+'</td><td>'+(r.enabled ? 'Yes' : 'No')+'</td>'+
                    '<td><button data-id="'+r.id+'" class="hq-btn toggle-enable">Toggle</button> <button data-id="'+r.id+'" class="hq-btn danger del">Delete</button></td></tr>';
                        });
                        html += '</tbody></table>';
                        cont.innerHTML = html;

                        // attach handlers
                        cont.querySelectorAll('button.toggle-enable').forEach(function(b){
                            b.addEventListener('click', function(){
                                var id = this.getAttribute('data-id');
                                var fd = new FormData(); fd.append('id', id); fd.append('action','toggle');
                                ajaxJson('POST',(window.HQ_ADMIN_BASE || '') + '/api/mac_blocklist.php', fd, function(err,res){ if (err || res.status!=='ok') return Swal.fire('Error','Failed to toggle','error'); Swal.fire('Updated',res.message||'','success').then(()=> macBtn.click()); });
                            });
                        });
                        cont.querySelectorAll('button.del').forEach(function(b){
                            b.addEventListener('click', function(){
                                var id = this.getAttribute('data-id');
                                Swal.fire({title:'Delete entry?',text:'This will remove the MAC blocklist entry',icon:'warning',showCancelButton:true}).then(function(r){ if (!r.isConfirmed) return; var fd = new FormData(); fd.append('id',id); fd.append('action','delete'); ajaxJson('POST',(window.HQ_ADMIN_BASE || '') + '/api/mac_blocklist.php', fd, function(err,res){ if (err||res.status!=='ok') return Swal.fire('Error','Failed','error'); Swal.fire('Deleted','Entry removed','success').then(()=> macBtn.click()); }); });
                            });
                        });
                    });
                }
            }).then(function(result){
                if (result.isConfirmed && result.value && result.value.mac){
                    var fd = new FormData(); fd.append('action','add'); fd.append('mac', result.value.mac); fd.append('reason', result.value.reason || ''); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value);
                    ajaxJson('POST',(window.HQ_ADMIN_BASE || '') + '/api/mac_blocklist.php', fd, function(err,res){ if (err || res.status!=='ok') return Swal.fire('Error','Failed to add entry','error'); Swal.fire('Added','MAC blocklist entry added','success').then(()=> macBtn.click()); });
                }
            });
        });
        
        // Open IP logs viewer
        var ipBtn = document.getElementById('openIpLogs');
        if (ipBtn) ipBtn.addEventListener('click', function(){
            Swal.fire({
                title: 'IP Logs',
                html: '<div style="text-align:left;max-height:420px;overflow:auto">'+
                      '<div style="margin-bottom:8px;display:flex;gap:6px;"><input id="filterIp" placeholder="Filter by IP" class="swal2-input" /> <input id="filterUser" placeholder="Filter by User ID" class="swal2-input" /> <button id="applyFilter" class="btn">Filter</button></div>'+
                      '<div id="ip-rows"></div></div>',
                width: '80%',
                showCancelButton: true,
                didOpen: function(){
                    function load(filter){
                        var qs = '';
                        if (filter){ qs = '?'+new URLSearchParams(filter).toString(); }
                        ajaxJson('GET',(window.HQ_ADMIN_BASE || '') + '/api/ip_logs.php'+qs, null, function(err,res){
                            var cont = document.getElementById('ip-rows');
                            if (err||!res||!res.rows) { cont.innerHTML = '<div class="muted">Failed to load</div>'; return; }
                            var html = '<table class="hq-table"><thead><tr><th>ID</th><th>IP</th><th>User</th><th>UA</th><th>Path</th><th>When</th></tr></thead><tbody>';
                            res.rows.forEach(function(r){
                                html += '<tr><td>'+r.id+'</td><td>'+Swal.escapeHtml(r.ip)+'</td><td>'+ (r.user_id||'') +'</td><td>'+Swal.escapeHtml((r.user_agent||'').slice(0,80))+'</td><td>'+Swal.escapeHtml(r.path||'') +'</td><td>'+r.created_at+'</td></tr>';
                            });
                            html += '</tbody></table>';
                            cont.innerHTML = html;
                        }); }
                    load();
                    document.getElementById('applyFilter').addEventListener('click', function(){ var ip = document.getElementById('filterIp').value.trim(); var user = document.getElementById('filterUser').value.trim(); var f = {}; if (ip) f.ip = ip; if (user) f.user_id = user; load(f); });
                }
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
