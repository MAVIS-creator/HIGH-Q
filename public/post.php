<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// small helper to show human-friendly elapsed time for comments
if (!function_exists('time_ago')) {
  function time_ago($ts) {
    $t = strtotime($ts);
    if (!$t) return $ts;
    $diff = time() - $t;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
  }
}

// support either ?id= or ?slug= links (home.php uses slug)
$postId = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if (!$postId && $slug === '') { header('Location: index.php'); exit; }

// fetch post by id or slug
if ($postId) {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
  $stmt->execute([$postId]);
} else {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
  $stmt->execute([$slug]);
}
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { echo "<p>Post not found.</p>"; exit; }

// fetch approved comments (top-level) and their replies in one go
$postId = $post['id'];
$cstmt = $pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND status = "approved" ORDER BY created_at DESC');
$cstmt->execute([$postId]);
$allComments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// build nested structure in PHP for deterministic ordering
$comments = [];
$repliesMap = [];
foreach ($allComments as $c) {
  if (empty($c['parent_id'])) {
    $comments[$c['id']] = $c;
    $comments[$c['id']]['replies'] = [];
  } else {
    $repliesMap[$c['parent_id']][] = $c;
  }
}
foreach ($repliesMap as $parentId => $list) {
  if (isset($comments[$parentId])) {
    $comments[$parentId]['replies'] = $list;
  } else {
    // orphan replies: attach to top-level comments list end
    foreach ($list as $l) $comments[$l['id']] = $l;
  }
}

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:1100px;margin:24px auto;padding:0 12px;">
  <div class="post-grid">
    <article class="post-article">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <div class="meta muted">Published: <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></div>
    <div class="post-content" style="margin-top:12px;">
      <?php if (!empty($post['excerpt'])): ?>
        <div class="post-excerpt" style="border:1px solid #f0e8e8;padding:18px;border-radius:8px;margin-bottom:14px;background:#fff"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($post['featured_image'])): ?>
        <?php
          $fi = $post['featured_image'];
          if (preg_match('#^https?://#i', $fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) {
            $imgSrc = $fi;
          } else {
            $imgSrc = '/HIGH-Q/' . ltrim($fi, '/');
          }
        ?>
        <div class="post-thumb" style="margin-bottom:12px;">
          <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:auto;display:block;border-radius:6px;object-fit:cover">
        </div>
      <?php endif; ?>

      <?= nl2br(htmlspecialchars($post['content'])) ?>
    </div>
  </article>

  <section id="commentsSection" class="comments-section">
    <h2>Comments</h2>

    <div id="commentsList" class="comments-list">
      <?php foreach($comments as $c): ?>
        <article class="comment" data-id="<?= $c['id'] ?>">
          <div class="comment-avatar"><div class="avatar-circle"><?= strtoupper(substr($c['name'] ?: 'A',0,1)) ?></div></div>
          <div class="comment-main">
            <div class="comment-meta"><strong><?= htmlspecialchars($c['name'] ?: 'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($c['created_at'])) ?></span></div>
            <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
            <div class="comment-actions">
              <button class="btn-link btn-reply" data-id="<?= $c['id'] ?>">Reply</button>
            </div>

            <?php if (!empty($c['replies'])): ?>
              <div class="replies">
                <?php foreach($c['replies'] as $rep): ?>
                  <div class="comment reply">
                    <div class="comment-avatar"><div class="avatar-circle muted"><?= strtoupper(substr($rep['name'] ?: 'A',0,1)) ?></div></div>
                    <div class="comment-main">
                      <div class="comment-meta"><strong><?= htmlspecialchars($rep['name'] ?: 'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($rep['created_at'])) ?></span></div>
                      <div class="comment-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                        </div>
                        <!-- Sidebar (TOC, share, stats) -->
                        <aside class="post-sidebar">
                          <div class="post-actions">
                            <div class="post-stats">
                              <button class="icon-btn">❤ <span class="count"><?= rand(20,400) ?></span></button>
                              <button class="icon-btn">💬 <span class="count"><?= count($comments) ?></span></button>
                              <button class="icon-btn">🔗 Share</button>
                            </div>
                          </div>
                          <div class="toc-box">
                            <h4>Table of Contents</h4>
                            <!-- simple TOC by headings in content (placeholder) -->
                            <ul>
                              <li>The Rise of Renewable Energy Solutions</li>
                              <li>Smart Cities: The Urban Revolution</li>
                              <li>Circular Economy and Waste Reduction</li>
                              <li>The Role of Artificial Intelligence</li>
                              <li>Looking Ahead: Challenges and Opportunities</li>
                            </ul>
                          </div>
                        </aside>
                      </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="comment-form-wrap">
      <h3>Join the conversation</h3>
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
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
