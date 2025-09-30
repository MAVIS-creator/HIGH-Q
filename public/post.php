<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// small helper to show human-friendly elapsed time for comments
    <div class="comment-form-wrap">
      <h3>Join the conversation</h3>
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <!-- honeypot field to trap bots -->
        <div style="display:none"><input type="text" name="hp_name" autocomplete="off" tabindex="-1"></div>
        <div class="form-row"><input type="text" name="name" placeholder="Your name (optional)"></div>
        <div class="form-row"><input type="email" name="email" placeholder="Email (optional)"></div>
        <div class="form-row"><textarea name="content" rows="4" placeholder="Share your thoughts on this article..." required></textarea></div>
        <div class="form-actions"><button type="submit" class="btn-approve">Post Comment</button> <button type="button" id="cancelReply" class="btn-link" style="display:none">Cancel Reply</button></div>
      </form>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // handle reply button: set parent_id and scroll to form
  document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click',function(e){
    e.preventDefault();
    var id = this.dataset.id; document.getElementById('parent_id').value = id;
    // show cancel link
    var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display = 'inline-block';
    // scroll to form
    var formTop = document.querySelector('.comment-form-wrap').getBoundingClientRect().top + window.scrollY;
    window.scrollTo({top: formTop - 80, behavior: 'smooth'});
  }));

  // submit comment form via fetch to public/api/comments.php
  var formEl = document.getElementById('commentForm');
  formEl.addEventListener('submit', function(e){
    e.preventDefault();
    var fd = new FormData(this);
    var submitBtn = this.querySelector('button[type=submit]'); if (submitBtn) submitBtn.disabled = true;
    fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
      if (j.status === 'ok') {
        // If comment is pending moderation, notify user
        if (j.message && j.message.toLowerCase().indexOf('awaiting') !== -1) {
          alert(j.message);
          formEl.reset();
          document.getElementById('parent_id').value = '';
          var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display='none';
          return;
        }
        // Append the returned comment object instantly
        if (j.comment) {
          var c = j.comment;
          var list = document.getElementById('commentsList');
          var node = renderCommentNode(c);
          // if parent_id present, find parent and append to its replies container
          if (c.parent_id) {
            var parent = list.querySelector('.comment[data-id="c'+c.parent_id+'"]');
            if (parent) {
              var replies = parent.querySelector('.replies');
              if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); }
              node.setAttribute('data-id','c'+c.id);
              replies.appendChild(node);
            } else {
              node.setAttribute('data-id','c'+c.id);
              list.insertBefore(node, list.firstChild);
            }
          } else {
            node.setAttribute('data-id','c'+c.id);
            list.insertBefore(node, list.firstChild);
          }
          formEl.reset(); document.getElementById('parent_id').value = '';
          var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display='none';
        }
      } else { alert(j.message||'Error'); }
    }).catch(()=>alert('Network error')).finally(()=>{ if (submitBtn) submitBtn.disabled = false; });
  });

  // cancel reply
  var cancelBtn = document.getElementById('cancelReply'); if (cancelBtn) cancelBtn.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

  // like button
  document.getElementById('likeBtn')?.addEventListener('click', function(){
    var btn = this; btn.disabled = true;
    fetch('api/like_post.php', { method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: 'post_id=' + encodeURIComponent(<?= $postId ?>) })
      .then(r=>r.json()).then(j=>{
        if (j.status === 'ok') { document.getElementById('likesCount').textContent = j.count; }
      }).catch(()=>{}).finally(()=>{ btn.disabled=false; });
  });

  // share button: open small menu
  document.getElementById('shareBtn')?.addEventListener('click', function(){
    var url = window.location.href; var title = document.querySelector('h1')?.textContent || document.title;
    var items = [
      {label:'Twitter', href: 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url)},
      {label:'Facebook', href: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)},
      {label:'WhatsApp', href: 'https://api.whatsapp.com/send?text=' + encodeURIComponent(title + ' ' + url)},
      {label:'Copy link', href: 'copy'}
    ];
    var menu = document.createElement('div'); menu.className='share-menu';
    items.forEach(it=>{ var a = document.createElement('a'); a.href = it.href === 'copy' ? '#' : it.href; a.target='_blank'; a.textContent = it.label; a.className='share-item'; a.addEventListener('click', function(ev){ ev.preventDefault(); if (it.href==='copy') { navigator.clipboard?.writeText(url).then(()=>{ alert('Link copied'); }).catch(()=>{ prompt('Copy this URL', url); }); } else { window.open(it.href,'_blank','noopener'); } }); menu.style.position='absolute'; menu.style.right='12px'; menu.style.top='48px'; menu.style.background='#fff'; menu.style.border='1px solid #eee'; menu.style.padding='8px'; menu.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'; menu.appendChild(a); });
    // remove existing
    var existing = document.querySelector('.share-menu'); if (existing) existing.remove();
    document.body.appendChild(menu);
    setTimeout(()=>{ window.addEventListener('click', function remover(){ menu.remove(); window.removeEventListener('click', remover); }); }, 50);
  });
});
</script>
        <div class="form-row"><input type="text" name="name" placeholder="Your name (optional)"></div>
        <div class="form-row"><input type="email" name="email" placeholder="Email (optional)"></div>
                <?= nl2br(htmlspecialchars($post['content'])) ?>
        <div class="form-actions"><button type="submit" class="btn-approve">Post Comment</button> <button type="button" id="cancelReply" class="btn-link" style="display:none">Cancel Reply</button></div>
      </form>
              <!-- Sidebar: actions, stats, TOC -->
              <aside class="post-sidebar">
                <div class="post-actions">
                  <div class="post-stats">
                    <?php
                      // Likes: show real count from post_likes if table exists, otherwise fallback to posts.likes column
                      $likesCount = 0;
                      try {
                        $lstmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
                        $lstmt->execute([$postId]);
                        $likesCount = (int)$lstmt->fetchColumn();
                      } catch (Throwable $e) {
                        // fallback to posts.likes column if present
                        if (isset($post['likes'])) $likesCount = (int)$post['likes'];
                      }
                      $commentsCount = max(0, count($allComments));
                    ?>
                    <button id="likeBtn" class="icon-btn">❤ <span id="likesCount" class="count"><?= $likesCount ?></span></button>
                    <button class="icon-btn">💬 <span class="count"><?= $commentsCount ?></span></button>
                    <button id="shareBtn" class="icon-btn">🔗 Share</button>
                  </div>
                </div>

                <div class="toc-box" id="tocBox">
                  <h4>Table of Contents</h4>
                  <div id="tocInner">
                    <?php
                      // Build TOC by parsing headings from post content (supports <h2>, <h3>)
                      $tocHtml = '';
                      try {
                        libxml_use_internal_errors(true);
                        $doc = new DOMDocument();
                        // wrap content to ensure a root element
                        $doc->loadHTML('<div>' . $post['content'] . '</div>');
                        $xpath = new DOMXPath($doc);
                        $nodes = $xpath->query('//h2|//h3');
                        if ($nodes && $nodes->length) {
                          $lastLevel = 2;
                          $tocHtml .= "<ul class=\"toc-list\">";
                          foreach ($nodes as $n) {
                            $tag = strtolower($n->nodeName);
                            $level = ($tag === 'h3') ? 3 : 2;
                            $text = trim($n->textContent);
                            // generate id if not present
                            $id = $n->getAttribute('id');
                            if (!$id) {
                              $id = preg_replace('/[^a-z0-9]+/i', '-', strtolower($text));
                              $id = trim($id, '-');
                              // ensure unique by appending index
                              $id .= '-' . spl_object_id($n);
                              $n->setAttribute('id', $id);
                            }
                            $tocHtml .= '<li class="toc-item toc-level-' . $level . '"><a href="#' . htmlspecialchars($id) . '">' . htmlspecialchars($text) . '</a></li>';
                          }
                          $tocHtml .= "</ul>";
                        } else {
                          $tocHtml = '<p class="muted">No headings found.</p>';
                        }
                      } catch (Throwable $e) { $tocHtml = '<p class="muted">Unable to build TOC</p>'; }
                      echo $tocHtml;
                    ?>
                  </div>
                </div>
              </aside>

    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // handle reply button: set parent_id and scroll to form
  document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click',function(e){
    e.preventDefault();
    var id = this.dataset.id; document.getElementById('parent_id').value = id;
    // show cancel link
    var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display = 'inline-block';
    // scroll to form
    var formTop = document.querySelector('.comment-form-wrap').getBoundingClientRect().top + window.scrollY;
    window.scrollTo({top: formTop - 80, behavior: 'smooth'});
  }));

  // submit comment form via fetch to public/api/comments.php
  var formEl = document.getElementById('commentForm');
  formEl.addEventListener('submit', function(e){
    e.preventDefault();
    var fd = new FormData(this);
    fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
    if (j.status === 'ok') {
        // If comment is pending moderation, notify user
        if (j.message && j.message.toLowerCase().indexOf('awaiting') !== -1) {
          alert(j.message);
          document.getElementById('commentForm').reset();
          document.getElementById('parent_id').value = '';
          var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display='none';
          return;
        }
        // Append the returned comment object instantly
        if (j.comment) {
          var c = j.comment;
          var list = document.getElementById('commentsList');
          var node = renderCommentNode(c);
          // if parent_id present, find parent and append to its replies container
          if (c.parent_id) {
            var parent = list.querySelector('.comment[data-id="c'+c.parent_id+'"]');
            if (parent) {
              var replies = parent.querySelector('.replies');
        <!-- honeypot field to trap bots -->
        <div style="display:none"><input type="text" name="hp_name" autocomplete="off" tabindex="-1"></div>
              if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); }
              node.setAttribute('data-id','c'+c.id);
              replies.appendChild(node);
            } else {
              node.setAttribute('data-id','c'+c.id);
              list.insertBefore(node, list.firstChild);
            }
          } else {
            node.setAttribute('data-id','c'+c.id);
            list.insertBefore(node, list.firstChild);
          }
          document.getElementById('commentForm').reset(); document.getElementById('parent_id').value = '';
          var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display='none';
        }
      } else { alert(j.message||'Error'); }
    }).catch(()=>alert('Network error'));
  });

  // cancel reply
  var cancelBtn = document.getElementById('cancelReply'); if (cancelBtn) cancelBtn.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

});

function escapeHtml(s){ return String(s).replace(/[&<>\"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
function nl2br(s){ return s.replace(/\r?\n/g,'<br>'); }

// Render a comment node from a comment object returned by API
    // simple rate-limit UI: disable for a short while
    var submitBtn = this.querySelector('button[type=submit]'); if (submitBtn) { submitBtn.disabled = true; }
function renderCommentNode(c) {
  var node = document.createElement('article'); node.className='comment';
  var av = document.createElement('div'); av.className='comment-avatar'; av.innerHTML='<div class="avatar-circle">'+(c.name?c.name.charAt(0).toUpperCase():'A')+'</div>';
  var main = document.createElement('div'); main.className='comment-main';
  main.innerHTML = '<div class="comment-meta"><strong>'+escapeHtml(c.name || 'Anonymous')+'</strong> <span class="muted">· just now</span> <span class="collapse-toggle" data-target="c'+c.id+'">Collapse</span></div>' +
                   '<div class="comment-body">'+nl2br(escapeHtml(c.content))+'</div>' +
                   '<div class="comment-actions"><button class="btn-link btn-reply" data-id="'+c.id+'">Reply</button></div>';
  node.appendChild(av); node.appendChild(main);
  // hook reply button
  var replyBtn = main.querySelector('.btn-reply'); if (replyBtn) replyBtn.addEventListener('click', function(){ document.getElementById('parent_id').value = this.dataset.id; var cancel = document.getElementById('cancelReply'); if (cancel) cancel.style.display='inline-block'; window.scrollTo({top: document.querySelector('.comment-form-wrap').getBoundingClientRect().top + window.scrollY - 80, behavior:'smooth'}); });
  // collapse toggle
  var coll = main.querySelector('.collapse-toggle'); if (coll) coll.addEventListener('click', function(){ var target = this.dataset.target; var el = document.querySelector('.comment[data-id="'+target+'"] .comment-main'); if (el) { el.classList.toggle('collapsed'); this.textContent = el.classList.contains('collapsed') ? 'Expand' : 'Collapse'; } });
  node.setAttribute('data-id','c'+c.id);
  return node;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php';
