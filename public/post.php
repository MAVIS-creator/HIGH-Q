<?php
// Clean single post template - consolidated and free of duplicated blocks
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

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

// support either ?id= or ?slug=
$postId = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if (!$postId && $slug === '') { header('Location: index.php'); exit; }

// fetch post
if ($postId) {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
  $stmt->execute([$postId]);
} else {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
  $stmt->execute([$slug]);
}
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { echo '<p>Post not found.</p>'; exit; }

// fetch approved comments
$postId = (int)$post['id'];
$cstmt = $pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND status = "approved" ORDER BY created_at DESC');
$cstmt->execute([$postId]);
$allComments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// build nested comments map
$comments = [];
$repliesMap = [];
foreach ($allComments as $c) {
  if (empty($c['parent_id'])) { $comments[$c['id']] = $c; $comments[$c['id']]['replies'] = []; }
  else { $repliesMap[$c['parent_id']][] = $c; }
}
foreach ($repliesMap as $pid => $list) {
  if (isset($comments[$pid])) { $comments[$pid]['replies'] = $list; }
  else { foreach ($list as $l) $comments[$l['id']] = $l; }
}

// prepare rendered content and TOC
$rendered = '';
$toc = [];
try {
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $doc->loadHTML('<div>' . $post['content'] . '</div>');
  $xpath = new DOMXPath($doc);
  $nodes = $xpath->query('//h2|//h3');
  $counter = 0;
  foreach ($nodes as $n) {
    $text = trim($n->textContent);
    $id = $n->getAttribute('id');
    if (!$id) { $id = preg_replace('/[^a-z0-9]+/i', '-', strtolower($text)); $id = trim($id, '-') . '-' . (++$counter); $n->setAttribute('id', $id); }
    $toc[] = ['id' => $id, 'text' => $text, 'level' => (strtolower($n->nodeName) === 'h3' ? 3 : 2)];
  }
  $body = $doc->getElementsByTagName('body')->item(0);
  $div = $body ? $body->getElementsByTagName('div')->item(0) : null;
  if ($div) { foreach ($div->childNodes as $cn) $rendered .= $doc->saveHTML($cn); }
  $allowed = '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><img><br><blockquote><pre><code>';
  $rendered = strip_tags($rendered, $allowed);
} catch (Throwable $e) { $rendered = nl2br(htmlspecialchars($post['content'])); }

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<div class="container" style="max-width:1100px;margin:24px auto;padding:0 12px;">
  <div class="post-grid">
    <article class="post-article">
      <h1><?= htmlspecialchars($post['title']) ?></h1>
      <div class="meta muted"><?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?> · <?= htmlspecialchars(time_ago($post['created_at'])) ?></div>
      <div class="post-content" style="margin-top:12px;">
        <?php if (!empty($post['excerpt'])): ?>
          <div class="post-excerpt" style="border:1px solid #f0e8e8;padding:18px;border-radius:8px;margin-bottom:14px;background:#fff"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($post['featured_image'])): $fi = $post['featured_image']; if (preg_match('#^https?://#i',$fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) $imgSrc=$fi; else $imgSrc = '/HIGH-Q/'.ltrim($fi,'/'); ?>
          <div class="post-thumb" style="margin-bottom:12px;"><img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:auto;display:block;border-radius:6px;object-fit:cover"></div>
        <?php endif; ?>

        <?= $rendered ?>
      </div>
    </article>

    <aside class="post-sidebar">
      <?php
      $likesCount = 0;
      try {
        $ls = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
        $ls->execute([$postId]);
        $likesCount = (int)$ls->fetchColumn();
      } catch (Throwable $e) {
        if (isset($post['likes'])) $likesCount = (int)$post['likes'];
      }
      $commentsCount = max(0, count($allComments));
      ?>

      <div class="post-actions">
        <div class="post-stats">
          <button id="likeBtn" class="icon-btn" aria-label="Like this post"><i class="bx bx-heart"></i> <span id="likesCount"><?= $likesCount ?></span></button>
          <button class="icon-btn" aria-label="Comments"><i class="bx bx-comment"></i> <span class="count"><?= $commentsCount ?></span></button>
          <button id="shareBtn" class="icon-btn" aria-label="Share"><i class="bx bx-share-alt"></i> Share</button>
        </div>
      </div>

      <div class="toc-box"><h4>Table of Contents</h4><div id="tocInner">
        <?php if (!empty($toc)): ?><ul class="toc-list"><?php foreach ($toc as $t): ?><li class="toc-item toc-level-<?= $t['level'] ?>"><a href="#<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['text']) ?></a></li><?php endforeach; ?></ul><?php else: ?><p class="muted">No headings found.</p><?php endif; ?>
      </div></div>
    </aside>
  </div>

  <section id="commentsSection" class="comments-section">
    <h2>Comments</h2>
    <div id="commentsList" class="comments-list">
      <?php foreach ($comments as $c): ?>
        <article class="comment" data-id="c<?= $c['id'] ?>">
          <div class="comment-avatar"><div class="avatar-circle"><?= strtoupper(substr($c['name']?:'A',0,1)) ?></div></div>
          <div class="comment-main">
            <div class="comment-meta"><strong><?= htmlspecialchars($c['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($c['created_at'])) ?></span></div>
            <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
            <div class="comment-actions"><button class="btn-link btn-reply" data-id="<?= $c['id'] ?>">Reply</button></div>
            <?php if (!empty($c['replies'])): ?><div class="replies"><?php foreach ($c['replies'] as $rep): ?><div class="comment reply"><div class="comment-avatar"><div class="avatar-circle muted"><?= strtoupper(substr($rep['name']?:'A',0,1)) ?></div></div><div class="comment-main"><div class="comment-meta"><strong><?= htmlspecialchars($rep['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($rep['created_at'])) ?></span></div><div class="comment-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div></div></div><?php endforeach; ?></div><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="comment-form-wrap">
      <h3>Join the conversation</h3>
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <!-- honeypot -->
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
const POST_ID = <?= json_encode((int)$postId) ?>;

function escapeHtml(s){ return String(s).replace(/[&<>\"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
function nl2br(s){ return s.replace(/\r?\n/g,'<br>'); }

function renderCommentNode(c){
  const node=document.createElement('article');
  node.className='comment'; node.setAttribute('data-id','c'+c.id);
  const av=document.createElement('div'); av.className='comment-avatar'; av.innerHTML='<div class="avatar-circle">'+(c.name?c.name.charAt(0).toUpperCase():'A')+'</div>';
  const main=document.createElement('div'); main.className='comment-main';
  main.innerHTML='<div class="comment-meta"><strong>'+escapeHtml(c.name||'Anonymous')+'</strong> <span class="muted">· just now</span></div><div class="comment-body">'+nl2br(escapeHtml(c.content))+'</div><div class="comment-actions"><button class="btn-link btn-reply" data-id="'+c.id+'">Reply</button></div>';
  node.appendChild(av); node.appendChild(main);
  const reply=main.querySelector('.btn-reply'); if (reply) reply.addEventListener('click', function(){ document.getElementById('parent_id').value=this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); });
  return node;
}

function handleLikeResponse(j){
  if (!j) return;
  const count = j.count ?? j.likes ?? j.likes_count ?? j.like_count ?? j.likesCount ?? j.data?.count ?? j.data?.likes;
  if (typeof count !== 'undefined') document.getElementById('likesCount').textContent = count;
}

function handleCommentResponse(j, form){
  const ok = j && (j.status === 'ok' || j.success === true || j.result === 'ok');
  if (!ok) return alert(j?.message || j?.msg || 'Error saving comment');
  const comment = j.comment || j.data?.comment || j.result?.comment;
  if (comment) {
    const node = renderCommentNode(comment);
    const list = document.getElementById('commentsList');
    if (comment.parent_id) {
      const parent = list.querySelector('.comment[data-id="c'+comment.parent_id+'"]');
      if (parent) {
        let replies = parent.querySelector('.replies');
        if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); }
        replies.insertBefore(node, replies.firstChild);
      } else list.insertBefore(node, list.firstChild);
    } else list.insertBefore(node, list.firstChild);
  } else {
    alert(j?.message || 'Comment submitted');
  }
  if (form) { form.reset(); document.getElementById('parent_id').value=''; document.getElementById('cancelReply').style.display='none'; }
}

document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('parent_id').value = this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }));

  const form = document.getElementById('commentForm');
  if (form) form.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled=true;
    fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{ handleCommentResponse(j, form); }).catch(()=>alert('Network error')).finally(()=>{ if (btn) btn.disabled=false; });
  });

  document.getElementById('cancelReply')?.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

  document.getElementById('likeBtn')?.addEventListener('click', function(){ const b=this; b.disabled=true; fetch('api/like_post.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+encodeURIComponent(POST_ID)}).then(r=>r.json()).then(j=>{ handleLikeResponse(j); }).catch(()=>{}).finally(()=>b.disabled=false); });

  document.getElementById('shareBtn')?.addEventListener('click', function(e){ e.stopPropagation(); const url=window.location.href; const title=document.querySelector('h1')?.textContent||document.title; const items=[{label:'Twitter',href:'https://twitter.com/intent/tweet?text='+encodeURIComponent(title)+'&url='+encodeURIComponent(url)},{label:'Facebook',href:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)},{label:'WhatsApp',href:'https://api.whatsapp.com/send?text='+encodeURIComponent(title+' '+url)},{label:'Copy',href:'copy'}]; let menu=document.querySelector('.share-menu'); if (menu) { menu.remove(); menu=null; } if (!menu) { menu=document.createElement('div'); menu.className='share-menu'; menu.style.position='absolute'; menu.style.right='20px'; menu.style.top=(e.pageY||80)+'px'; menu.style.background='#fff'; menu.style.border='1px solid #eee'; menu.style.padding='8px'; items.forEach(it=>{ const a=document.createElement('a'); a.href=it.href==='copy'?'#':it.href; a.textContent=it.label; a.style.display='block'; a.style.padding='6px 8px'; a.addEventListener('click', ev=>{ ev.preventDefault(); if (it.href==='copy') { navigator.clipboard?.writeText(url).then(()=>alert('Link copied')).catch(()=>prompt('Copy this URL',url)); } else { window.open(it.href,'_blank','noopener'); } }); menu.appendChild(a); }); document.body.appendChild(menu); setTimeout(()=>{ const rm=()=>{ menu.remove(); window.removeEventListener('click',rm); }; window.addEventListener('click',rm); },50); } });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
// Single clean post template (no duplicates)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

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

// support either ?id= or ?slug=
$postId = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if (!$postId && $slug === '') { header('Location: index.php'); exit; }

// fetch post
if ($postId) {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
  $stmt->execute([$postId]);
} else {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
  $stmt->execute([$slug]);
}
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { echo '<p>Post not found.</p>'; exit; }

// fetch approved comments
$postId = (int)$post['id'];
$cstmt = $pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND status = "approved" ORDER BY created_at DESC');
$cstmt->execute([$postId]);
$allComments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// build nested comments map
$comments = [];
$repliesMap = [];
foreach ($allComments as $c) {
  if (empty($c['parent_id'])) { $comments[$c['id']] = $c; $comments[$c['id']]['replies'] = []; }
  else { $repliesMap[$c['parent_id']][] = $c; }
}
foreach ($repliesMap as $pid => $list) {
  if (isset($comments[$pid])) { $comments[$pid]['replies'] = $list; }
  else { foreach ($list as $l) $comments[$l['id']] = $l; }
}

// prepare rendered content and TOC
$rendered = '';
$toc = [];
try {
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $doc->loadHTML('<div>' . $post['content'] . '</div>');
  $xpath = new DOMXPath($doc);
  $nodes = $xpath->query('//h2|//h3');
  $counter = 0;
  foreach ($nodes as $n) {
    $text = trim($n->textContent);
    $id = $n->getAttribute('id');
    if (!$id) { $id = preg_replace('/[^a-z0-9]+/i', '-', strtolower($text)); $id = trim($id, '-') . '-' . (++$counter); $n->setAttribute('id', $id); }
    $toc[] = ['id' => $id, 'text' => $text, 'level' => (strtolower($n->nodeName) === 'h3' ? 3 : 2)];
  }
  $body = $doc->getElementsByTagName('body')->item(0);
  $div = $body ? $body->getElementsByTagName('div')->item(0) : null;
  if ($div) { foreach ($div->childNodes as $cn) $rendered .= $doc->saveHTML($cn); }
  $allowed = '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><img><br><blockquote><pre><code>';
  $rendered = strip_tags($rendered, $allowed);
} catch (Throwable $e) { $rendered = nl2br(htmlspecialchars($post['content'])); }

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<div class="container" style="max-width:1100px;margin:24px auto;padding:0 12px;">
  <div class="post-grid">
    <article class="post-article">
      <h1><?= htmlspecialchars($post['title']) ?></h1>
      <div class="meta muted"><?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?> · <?= htmlspecialchars(time_ago($post['created_at'])) ?></div>
      <div class="post-content" style="margin-top:12px;">
        <?php if (!empty($post['excerpt'])): ?>
          <div class="post-excerpt" style="border:1px solid #f0e8e8;padding:18px;border-radius:8px;margin-bottom:14px;background:#fff"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($post['featured_image'])): $fi = $post['featured_image']; if (preg_match('#^https?://#i',$fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) $imgSrc=$fi; else $imgSrc = '/HIGH-Q/'.ltrim($fi,'/'); ?>
          <div class="post-thumb" style="margin-bottom:12px;"><img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:auto;display:block;border-radius:6px;object-fit:cover"></div>
        <?php endif; ?>

        <?= $rendered ?>
      </div>
    </article>

    <aside class="post-sidebar">
      <?php
      $likesCount = 0;
      try {
        $ls = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
        $ls->execute([$postId]);
        $likesCount = (int)$ls->fetchColumn();
      } catch (Throwable $e) {
        if (isset($post['likes'])) $likesCount = (int)$post['likes'];
      }
      $commentsCount = max(0, count($allComments));
      ?>

      <div class="post-actions">
        <div class="post-stats">
          <button id="likeBtn" class="icon-btn" aria-label="Like this post"><i class="bx bx-heart"></i> <span id="likesCount"><?= $likesCount ?></span></button>
          <button class="icon-btn" aria-label="Comments"><i class="bx bx-comment"></i> <span class="count"><?= $commentsCount ?></span></button>
          <button id="shareBtn" class="icon-btn" aria-label="Share"><i class="bx bx-share-alt"></i> Share</button>
        </div>
      </div>

      <div class="toc-box"><h4>Table of Contents</h4><div id="tocInner">
        <?php if (!empty($toc)): ?><ul class="toc-list"><?php foreach ($toc as $t): ?><li class="toc-item toc-level-<?= $t['level'] ?>"><a href="#<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['text']) ?></a></li><?php endforeach; ?></ul><?php else: ?><p class="muted">No headings found.</p><?php endif; ?>
      </div></div>
    </aside>
  </div>

  <section id="commentsSection" class="comments-section">
    <h2>Comments</h2>
    <div id="commentsList" class="comments-list">
      <?php foreach ($comments as $c): ?>
        <article class="comment" data-id="c<?= $c['id'] ?>">
          <div class="comment-avatar"><div class="avatar-circle"><?= strtoupper(substr($c['name']?:'A',0,1)) ?></div></div>
          <div class="comment-main">
            <div class="comment-meta"><strong><?= htmlspecialchars($c['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($c['created_at'])) ?></span></div>
            <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
            <div class="comment-actions"><button class="btn-link btn-reply" data-id="<?= $c['id'] ?>">Reply</button></div>
            <?php if (!empty($c['replies'])): ?><div class="replies"><?php foreach ($c['replies'] as $rep): ?><div class="comment reply"><div class="comment-avatar"><div class="avatar-circle muted"><?= strtoupper(substr($rep['name']?:'A',0,1)) ?></div></div><div class="comment-main"><div class="comment-meta"><strong><?= htmlspecialchars($rep['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($rep['created_at'])) ?></span></div><div class="comment-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div></div></div><?php endforeach; ?></div><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="comment-form-wrap">
      <h3>Join the conversation</h3>
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <!-- honeypot -->
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
const POST_ID = <?= json_encode((int)$postId) ?>;
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('parent_id').value = this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }));

  const form = document.getElementById('commentForm'); if (form) form.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled=true;
    fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
      if (j.status==='ok') {
        if (j.comment) {
          const node = renderCommentNode(j.comment);
          const list = document.getElementById('commentsList');
          if (j.comment.parent_id) {
            const parent = list.querySelector('.comment[data-id="c'+j.comment.parent_id+'"]');
            if (parent) {
              let replies = parent.querySelector('.replies');
              if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); }
              replies.insertBefore(node, replies.firstChild);
            } else list.insertBefore(node, list.firstChild);
          } else list.insertBefore(node, list.firstChild);
        } else { alert(j.message||'Comment submitted'); }
        form.reset(); document.getElementById('parent_id').value=''; document.getElementById('cancelReply').style.display='none';
      } else { alert(j.message||'Error'); }
    }).catch(()=>alert('Network error')).finally(()=>{ if (btn) btn.disabled=false; });
  });

  document.getElementById('cancelReply')?.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

  document.getElementById('likeBtn')?.addEventListener('click', function(){ const b=this; b.disabled=true; fetch('api/like_post.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+encodeURIComponent(POST_ID)}).then(r=>r.json()).then(j=>{ if (j.status==='ok') document.getElementById('likesCount').textContent = j.count; else if (j.count) document.getElementById('likesCount').textContent = j.count; }).finally(()=>b.disabled=false); });

  document.getElementById('shareBtn')?.addEventListener('click', function(e){ e.stopPropagation(); const url=window.location.href; const title=document.querySelector('h1')?.textContent||document.title; const items=[{label:'Twitter',href:'https://twitter.com/intent/tweet?text='+encodeURIComponent(title)+'&url='+encodeURIComponent(url)},{label:'Facebook',href:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)},{label:'WhatsApp',href:'https://api.whatsapp.com/send?text='+encodeURIComponent(title+' '+url)},{label:'Copy',href:'copy'}]; let menu=document.querySelector('.share-menu'); if (menu) { menu.remove(); menu=null; } if (!menu) { menu=document.createElement('div'); menu.className='share-menu'; menu.style.position='absolute'; menu.style.right='20px'; menu.style.top=(e.pageY||80)+'px'; menu.style.background='#fff'; menu.style.border='1px solid #eee'; menu.style.padding='8px'; items.forEach(it=>{ const a=document.createElement('a'); a.href=it.href==='copy'?'#':it.href; a.textContent=it.label; a.style.display='block'; a.style.padding='6px 8px'; a.addEventListener('click', ev=>{ ev.preventDefault(); if (it.href==='copy') { navigator.clipboard?.writeText(url).then(()=>alert('Link copied')).catch(()=>prompt('Copy this URL',url)); } else { window.open(it.href,'_blank','noopener'); } }); menu.appendChild(a); }); document.body.appendChild(menu); setTimeout(()=>{ const rm=()=>{ menu.remove(); window.removeEventListener('click',rm); }; window.addEventListener('click',rm); },50); } });

});

function escapeHtml(s){ return String(s).replace(/[&<>\"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
function nl2br(s){ return s.replace(/\r?\n/g,'<br>'); }
function renderCommentNode(c){ const node=document.createElement('article'); node.className='comment'; node.setAttribute('data-id','c'+c.id); const av=document.createElement('div'); av.className='comment-avatar'; av.innerHTML='<div class="avatar-circle">'+(c.name?c.name.charAt(0).toUpperCase():'A')+'</div>'; const main=document.createElement('div'); main.className='comment-main'; main.innerHTML='<div class="comment-meta"><strong>'+escapeHtml(c.name||'Anonymous')+'</strong> <span class="muted">· just now</span></div><div class="comment-body">'+nl2br(escapeHtml(c.content))+'</div><div class="comment-actions"><button class="btn-link btn-reply" data-id="'+c.id+'">Reply</button></div>'; node.appendChild(av); node.appendChild(main); const reply=main.querySelector('.btn-reply'); if (reply) reply.addEventListener('click', function(){ document.getElementById('parent_id').value=this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }); return node; }

<?php require_once __DIR__ . '/includes/footer.php'; ?>
    $t = strtotime($ts);
    if (!$t) return $ts;
    $diff = time() - $t;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
  }
}

<?php
// Clean single template for post view
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

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

// support either ?id= or ?slug=
$postId = (int)($_GET['id'] ?? 0);
$slug = trim($_GET['slug'] ?? '');
if (!$postId && $slug === '') { header('Location: index.php'); exit; }

// fetch post
if ($postId) {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
  $stmt->execute([$postId]);
} else {
  $stmt = $pdo->prepare('SELECT * FROM posts WHERE slug = ? LIMIT 1');
  $stmt->execute([$slug]);
}
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { echo '<p>Post not found.</p>'; exit; }

// fetch approved comments
$postId = (int)$post['id'];
$cstmt = $pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND status = "approved" ORDER BY created_at DESC');
$cstmt->execute([$postId]);
$allComments = $cstmt->fetchAll(PDO::FETCH_ASSOC);

// build nested comments map
$comments = [];
$repliesMap = [];
foreach ($allComments as $c) {
  if (empty($c['parent_id'])) { $comments[$c['id']] = $c; $comments[$c['id']]['replies'] = []; }
  else { $repliesMap[$c['parent_id']][] = $c; }
}
foreach ($repliesMap as $pid => $list) {
  if (isset($comments[$pid])) { $comments[$pid]['replies'] = $list; }
  else { foreach ($list as $l) $comments[$l['id']] = $l; }
}

// prepare rendered content and TOC
$rendered = '';
$toc = [];
try {
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $doc->loadHTML('<div>' . $post['content'] . '</div>');
  $xpath = new DOMXPath($doc);
  $nodes = $xpath->query('//h2|//h3');
  $counter = 0;
  foreach ($nodes as $n) {
    $text = trim($n->textContent);
    $id = $n->getAttribute('id');
    if (!$id) { $id = preg_replace('/[^a-z0-9]+/i', '-', strtolower($text)); $id = trim($id, '-') . '-' . (++$counter); $n->setAttribute('id', $id); }
    $toc[] = ['id' => $id, 'text' => $text, 'level' => (strtolower($n->nodeName) === 'h3' ? 3 : 2)];
  }
  $body = $doc->getElementsByTagName('body')->item(0);
  $div = $body ? $body->getElementsByTagName('div')->item(0) : null;
  if ($div) { foreach ($div->childNodes as $cn) $rendered .= $doc->saveHTML($cn); }
  $allowed = '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><img><br><blockquote><pre><code>';
  $rendered = strip_tags($rendered, $allowed);
} catch (Throwable $e) { $rendered = nl2br(htmlspecialchars($post['content'])); }

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<!-- load Boxicons from CDN for consistent icons -->
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<div class="container" style="max-width:1100px;margin:24px auto;padding:0 12px;">
  <div class="post-grid">
    <article class="post-article">
      <h1><?= htmlspecialchars($post['title']) ?></h1>
      <div class="meta muted"><?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?> · <?= htmlspecialchars(time_ago($post['created_at'])) ?></div>
      <div class="post-content" style="margin-top:12px;">
        <?php if (!empty($post['excerpt'])): ?>
          <div class="post-excerpt" style="border:1px solid #f0e8e8;padding:18px;border-radius:8px;margin-bottom:14px;background:#fff"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($post['featured_image'])): $fi = $post['featured_image']; if (preg_match('#^https?://#i',$fi) || strpos($fi,'//')===0 || strpos($fi,'/')===0) $imgSrc=$fi; else $imgSrc = '/HIGH-Q/'.ltrim($fi,'/'); ?>
          <div class="post-thumb" style="margin-bottom:12px;"><img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:auto;display:block;border-radius:6px;object-fit:cover"></div>
        <?php endif; ?>

        <?= $rendered ?>
      </div>
    </article>

    <aside class="post-sidebar">
      <?php
      $likesCount = 0;
      try {
        $ls = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
        $ls->execute([$postId]);
        $likesCount = (int)$ls->fetchColumn();
      } catch (Throwable $e) {
        if (isset($post['likes'])) $likesCount = (int)$post['likes'];
      }
      $commentsCount = max(0, count($allComments));
      ?>

      <div class="post-actions">
        <div class="post-stats">
          <button id="likeBtn" class="icon-btn" aria-label="Like this post"><i class="bx bx-heart"></i> <span id="likesCount"><?= $likesCount ?></span></button>
          <button class="icon-btn" aria-label="Comments"><i class="bx bx-comment"></i> <span class="count"><?= $commentsCount ?></span></button>
          <button id="shareBtn" class="icon-btn" aria-label="Share"><i class="bx bx-share-alt"></i> Share</button>
        </div>
      </div>

      <div class="toc-box"><h4>Table of Contents</h4><div id="tocInner">
        <?php if (!empty($toc)): ?><ul class="toc-list"><?php foreach ($toc as $t): ?><li class="toc-item toc-level-<?= $t['level'] ?>"><a href="#<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['text']) ?></a></li><?php endforeach; ?></ul><?php else: ?><p class="muted">No headings found.</p><?php endif; ?>
      </div></div>
    </aside>
  </div>

  <section id="commentsSection" class="comments-section">
    <h2>Comments</h2>
    <div id="commentsList" class="comments-list">
      <?php foreach ($comments as $c): ?>
        <article class="comment" data-id="c<?= $c['id'] ?>">
          <div class="comment-avatar"><div class="avatar-circle"><?= strtoupper(substr($c['name']?:'A',0,1)) ?></div></div>
          <div class="comment-main">
            <div class="comment-meta"><strong><?= htmlspecialchars($c['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($c['created_at'])) ?></span></div>
            <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
            <div class="comment-actions"><button class="btn-link btn-reply" data-id="<?= $c['id'] ?>">Reply</button></div>
            <?php if (!empty($c['replies'])): ?><div class="replies"><?php foreach ($c['replies'] as $rep): ?><div class="comment reply"><div class="comment-avatar"><div class="avatar-circle muted"><?= strtoupper(substr($rep['name']?:'A',0,1)) ?></div></div><div class="comment-main"><div class="comment-meta"><strong><?= htmlspecialchars($rep['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($rep['created_at'])) ?></span></div><div class="comment-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div></div></div><?php endforeach; ?></div><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="comment-form-wrap">
      <h3>Join the conversation</h3>
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
        <!-- honeypot -->
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
const POST_ID = <?= json_encode((int)$postId) ?>;
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('parent_id').value = this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }));

  const form = document.getElementById('commentForm'); if (form) form.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled=true;
    fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
      if (j.status==='ok') {
        if (j.comment) {
          const node = renderCommentNode(j.comment);
          const list = document.getElementById('commentsList');
          if (j.comment.parent_id) {
            const parent = list.querySelector('.comment[data-id="c'+j.comment.parent_id+'"]');
            if (parent) {
              let replies = parent.querySelector('.replies');
              if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); }
              replies.insertBefore(node, replies.firstChild);
            } else list.insertBefore(node, list.firstChild);
          } else list.insertBefore(node, list.firstChild);
        } else { alert(j.message||'Comment submitted'); }
        form.reset(); document.getElementById('parent_id').value=''; document.getElementById('cancelReply').style.display='none';
      } else { alert(j.message||'Error'); }
    }).catch(()=>alert('Network error')).finally(()=>{ if (btn) btn.disabled=false; });
  });

  document.getElementById('cancelReply')?.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

  document.getElementById('likeBtn')?.addEventListener('click', function(){ const b=this; b.disabled=true; fetch('api/like_post.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+encodeURIComponent(POST_ID)}).then(r=>r.json()).then(j=>{ if (j.status==='ok') document.getElementById('likesCount').textContent = j.count; else if (j.count) document.getElementById('likesCount').textContent = j.count; }).finally(()=>b.disabled=false); });

  document.getElementById('shareBtn')?.addEventListener('click', function(e){ e.stopPropagation(); const url=window.location.href; const title=document.querySelector('h1')?.textContent||document.title; const items=[{label:'Twitter',href:'https://twitter.com/intent/tweet?text='+encodeURIComponent(title)+'&url='+encodeURIComponent(url)},{label:'Facebook',href:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)},{label:'WhatsApp',href:'https://api.whatsapp.com/send?text='+encodeURIComponent(title+' '+url)},{label:'Copy',href:'copy'}]; let menu=document.querySelector('.share-menu'); if (menu) { menu.remove(); menu=null; } if (!menu) { menu=document.createElement('div'); menu.className='share-menu'; menu.style.position='absolute'; menu.style.right='20px'; menu.style.top=(e.pageY||80)+'px'; menu.style.background='#fff'; menu.style.border='1px solid #eee'; menu.style.padding='8px'; items.forEach(it=>{ const a=document.createElement('a'); a.href=it.href==='copy'?'#':it.href; a.textContent=it.label; a.style.display='block'; a.style.padding='6px 8px'; a.addEventListener('click', ev=>{ ev.preventDefault(); if (it.href==='copy') { navigator.clipboard?.writeText(url).then(()=>alert('Link copied')).catch(()=>prompt('Copy this URL',url)); } else { window.open(it.href,'_blank','noopener'); } }); menu.appendChild(a); }); document.body.appendChild(menu); setTimeout(()=>{ const rm=()=>{ menu.remove(); window.removeEventListener('click',rm); }; window.addEventListener('click',rm); },50); } });
});

function escapeHtml(s){ return String(s).replace(/[&<>\"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
function nl2br(s){ return s.replace(/\r?\n/g,'<br>'); }
function renderCommentNode(c){ const node=document.createElement('article'); node.className='comment'; node.setAttribute('data-id','c'+c.id); const av=document.createElement('div'); av.className='comment-avatar'; av.innerHTML='<div class="avatar-circle">'+(c.name?c.name.charAt(0).toUpperCase():'A')+'</div>'; const main=document.createElement('div'); main.className='comment-main'; main.innerHTML='<div class="comment-meta"><strong>'+escapeHtml(c.name||'Anonymous')+'</strong> <span class="muted">· just now</span></div><div class="comment-body">'+nl2br(escapeHtml(c.content))+'</div><div class="comment-actions"><button class="btn-link btn-reply" data-id="'+c.id+'">Reply</button></div>'; node.appendChild(av); node.appendChild(main); const reply=main.querySelector('.btn-reply'); if (reply) reply.addEventListener('click', function(){ document.getElementById('parent_id').value=this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }); return node; }

<?php require_once __DIR__ . '/includes/footer.php'; ?>
      </div>
    </section>
  </div>

  <script>
  const POST_ID = <?= json_encode((int)$postId) ?>;
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('parent_id').value = this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }));

    const form = document.getElementById('commentForm'); if (form) form.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(this);
      const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled=true;
      fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{
        if (j.status==='ok') {
          if (j.comment) {
            const node = renderCommentNode(j.comment);
            const list = document.getElementById('commentsList');
            if (j.comment.parent_id) {
              const parent = list.querySelector('.comment[data-id="c'+j.comment.parent_id+'"]');
              if (parent) {
                let replies = parent.querySelector('.replies');
                if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); }
                replies.insertBefore(node, replies.firstChild);
              } else list.insertBefore(node, list.firstChild);
            } else list.insertBefore(node, list.firstChild);
          } else { alert(j.message||'Comment submitted'); }
          form.reset(); document.getElementById('parent_id').value=''; document.getElementById('cancelReply').style.display='none';
        } else { alert(j.message||'Error'); }
      }).catch(()=>alert('Network error')).finally(()=>{ if (btn) btn.disabled=false; });
    });

    document.getElementById('cancelReply')?.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

    document.getElementById('likeBtn')?.addEventListener('click', function(){ const b=this; b.disabled=true; fetch('api/like_post.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+encodeURIComponent(POST_ID)}).then(r=>r.json()).then(j=>{ if (j.status==='ok') document.getElementById('likesCount').textContent = j.count; else if (j.count) document.getElementById('likesCount').textContent = j.count; }).finally(()=>b.disabled=false); });

    document.getElementById('shareBtn')?.addEventListener('click', function(e){ e.stopPropagation(); const url=window.location.href; const title=document.querySelector('h1')?.textContent||document.title; const items=[{label:'Twitter',href:'https://twitter.com/intent/tweet?text='+encodeURIComponent(title)+'&url='+encodeURIComponent(url)},{label:'Facebook',href:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)},{label:'WhatsApp',href:'https://api.whatsapp.com/send?text='+encodeURIComponent(title+' '+url)},{label:'Copy',href:'copy'}]; let menu=document.querySelector('.share-menu'); if (menu) { menu.remove(); menu=null; } if (!menu) { menu=document.createElement('div'); menu.className='share-menu'; menu.style.position='absolute'; menu.style.right='20px'; menu.style.top=(e.pageY||80)+'px'; menu.style.background='#fff'; menu.style.border='1px solid #eee'; menu.style.padding='8px'; items.forEach(it=>{ const a=document.createElement('a'); a.href=it.href==='copy'?'#':it.href; a.textContent=it.label; a.style.display='block'; a.style.padding='6px 8px'; a.addEventListener('click', ev=>{ ev.preventDefault(); if (it.href==='copy') { navigator.clipboard?.writeText(url).then(()=>alert('Link copied')).catch(()=>prompt('Copy this URL',url)); } else { window.open(it.href,'_blank','noopener'); } }); menu.appendChild(a); }); document.body.appendChild(menu); setTimeout(()=>{ const rm=()=>{ menu.remove(); window.removeEventListener('click',rm); }; window.addEventListener('click',rm); },50); } });
  });

  function escapeHtml(s){ return String(s).replace(/[&<>\"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
  function nl2br(s){ return s.replace(/\r?\n/g,'<br>'); }
  function renderCommentNode(c){ const node=document.createElement('article'); node.className='comment'; node.setAttribute('data-id','c'+c.id); const av=document.createElement('div'); av.className='comment-avatar'; av.innerHTML='<div class="avatar-circle">'+(c.name?c.name.charAt(0).toUpperCase():'A')+'</div>'; const main=document.createElement('div'); main.className='comment-main'; main.innerHTML='<div class="comment-meta"><strong>'+escapeHtml(c.name||'Anonymous')+'</strong> <span class="muted">· just now</span></div><div class="comment-body">'+nl2br(escapeHtml(c.content))+'</div><div class="comment-actions"><button class="btn-link btn-reply" data-id="'+c.id+'">Reply</button></div>'; node.appendChild(av); node.appendChild(main); const reply=main.querySelector('.btn-reply'); if (reply) reply.addEventListener('click', function(){ document.getElementById('parent_id').value=this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }); return node; }

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
  if (isset($comments[$pid])) $comments[$pid]['replies'] = $list; else foreach ($list as $l) $comments[$l['id']] = $l;
}

// build TOC and sanitized content
$rendered = '';
$toc = [];
try {
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $doc->loadHTML('<div>' . $post['content'] . '</div>');
  $xpath = new DOMXPath($doc);
  $nodes = $xpath->query('//h2|//h3');
  $i = 0;
  foreach ($nodes as $n) {
    $txt = trim($n->textContent);
    $id = $n->getAttribute('id');
    if (!$id) { $id = preg_replace('/[^a-z0-9]+/i','-',strtolower($txt)); $id = trim($id,'-') . '-' . (++$i); $n->setAttribute('id',$id); }
    $toc[] = ['id'=>$id,'text'=>$txt,'level'=>(strtolower($n->nodeName)==='h3'?3:2)];
  }
  $body = $doc->getElementsByTagName('body')->item(0);
  $div = $body ? $body->getElementsByTagName('div')->item(0) : null;
  if ($div) { foreach ($div->childNodes as $cn) $rendered .= $doc->saveHTML($cn); }
  $allowed = '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><img><br><blockquote><pre><code>';
  $rendered = strip_tags($rendered, $allowed);
} catch (Throwable $e) { $rendered = nl2br(htmlspecialchars($post['content'])); }

$pageTitle = $post['title'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:1100px;margin:24px auto;padding:0 12px;">
  <div class="post-grid">
    <article class="post-article">
      <h1><?= htmlspecialchars($post['title']) ?></h1>
      <div class="meta muted"><?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?> · <?= htmlspecialchars(time_ago($post['created_at'])) ?></div>
      <div class="post-content" style="margin-top:12px;">
        <?php if (!empty($post['excerpt'])): ?>
          <div class="post-excerpt"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($post['featured_image'])): $fi=$post['featured_image']; if (preg_match('#^https?://#i',$fi)||strpos($fi,'//')===0||strpos($fi,'/')===0) $img=$fi; else $img='/HIGH-Q/'.ltrim($fi,'/'); ?>
          <div class="post-thumb"><img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:auto;display:block;border-radius:6px;"></div>
        <?php endif; ?>
        <?= $rendered ?>
      </div>
    </article>

    <aside class="post-sidebar">
      <?php $likesCount=0; try{$ls=$pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id=?');$ls->execute([$postId]);$likesCount=(int)$ls->fetchColumn();}catch(Throwable$e){ if(isset($post['likes'])) $likesCount=(int)$post['likes']; } $commentsCount = max(0,count($allComments)); ?>
      <div class="post-actions"><div class="post-stats"><button id="likeBtn" class="icon-btn">❤ <span id="likesCount"><?= $likesCount ?></span></button> <button class="icon-btn">💬 <span class="count"><?= $commentsCount ?></span></button> <button id="shareBtn" class="icon-btn">🔗 Share</button></div></div>

      <div class="toc-box"><h4>Table of Contents</h4><div id="tocInner">
        <?php if (!empty($toc)): ?><ul class="toc-list"><?php foreach ($toc as $t): ?><li class="toc-item toc-level-<?= $t['level'] ?>"><a href="#<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['text']) ?></a></li><?php endforeach; ?></ul><?php else: ?><p class="muted">No headings found.</p><?php endif; ?>
      </div></div>
    </aside>
  </div>

  <section id="commentsSection" class="comments-section">
    <h2>Comments</h2>
    <div id="commentsList" class="comments-list">
      <?php foreach ($comments as $c): ?>
        <article class="comment" data-id="c<?= $c['id'] ?>">
          <div class="comment-avatar"><div class="avatar-circle"><?= strtoupper(substr($c['name']?:'A',0,1)) ?></div></div>
          <div class="comment-main">
            <div class="comment-meta"><strong><?= htmlspecialchars($c['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($c['created_at'])) ?></span></div>
            <div class="comment-body"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
            <div class="comment-actions"><button class="btn-link btn-reply" data-id="<?= $c['id'] ?>">Reply</button></div>
            <?php if (!empty($c['replies'])): ?><div class="replies"><?php foreach ($c['replies'] as $rep): ?><div class="comment reply"><div class="comment-avatar"><div class="avatar-circle muted"><?= strtoupper(substr($rep['name']?:'A',0,1)) ?></div></div><div class="comment-main"><div class="comment-meta"><strong><?= htmlspecialchars($rep['name']?:'Anonymous') ?></strong> <span class="muted">· <?= htmlspecialchars(time_ago($rep['created_at'])) ?></span></div><div class="comment-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div></div></div><?php endforeach; ?></div><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="comment-form-wrap">
      <h3>Join the conversation</h3>
      <form id="commentForm">
        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="">
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
const POST_ID = <?= json_encode((int)$postId) ?>;
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.btn-reply').forEach(b=>b.addEventListener('click', function(e){ e.preventDefault(); document.getElementById('parent_id').value = this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }));

  const form = document.getElementById('commentForm'); if (form) form.addEventListener('submit', function(e){ e.preventDefault(); const fd = new FormData(this); const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled=true; fetch('api/comments.php',{method:'POST',body:fd}).then(r=>r.json()).then(j=>{ if (j.status==='ok') { if (j.comment) { const node = renderCommentNode(j.comment); const list = document.getElementById('commentsList'); if (j.comment.parent_id) { const parent = list.querySelector('.comment[data-id="c'+j.comment.parent_id+'"]'); if (parent) { let replies = parent.querySelector('.replies'); if (!replies) { replies = document.createElement('div'); replies.className='replies'; parent.querySelector('.comment-main').appendChild(replies); } replies.insertBefore(node, replies.firstChild); } else list.insertBefore(node, list.firstChild); } else list.insertBefore(node, list.firstChild); } else { alert(j.message||'Comment submitted'); } form.reset(); document.getElementById('parent_id').value=''; document.getElementById('cancelReply').style.display='none'; } else { alert(j.message||'Error'); } }).catch(()=>alert('Network error')).finally(()=>{ if (btn) btn.disabled=false; }); });

  document.getElementById('cancelReply')?.addEventListener('click', function(){ document.getElementById('parent_id').value=''; this.style.display='none'; });

  document.getElementById('likeBtn')?.addEventListener('click', function(){ const b=this; b.disabled=true; fetch('api/like_post.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'post_id='+encodeURIComponent(POST_ID)}).then(r=>r.json()).then(j=>{ if (j.status==='ok') document.getElementById('likesCount').textContent = j.count; }).finally(()=>b.disabled=false); });

  document.getElementById('shareBtn')?.addEventListener('click', function(){ const url=window.location.href; const title=document.querySelector('h1')?.textContent||document.title; const items=[{label:'Twitter',href:'https://twitter.com/intent/tweet?text='+encodeURIComponent(title)+'&url='+encodeURIComponent(url)},{label:'Facebook',href:'https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)},{label:'WhatsApp',href:'https://api.whatsapp.com/send?text='+encodeURIComponent(title+' '+url)},{label:'Copy',href:'copy'}]; let menu=document.querySelector('.share-menu'); if (menu) { menu.remove(); menu=null; } if (!menu) { menu=document.createElement('div'); menu.className='share-menu'; menu.style.position='absolute'; menu.style.right='20px'; menu.style.top='80px'; menu.style.background='#fff'; menu.style.border='1px solid #eee'; menu.style.padding='8px'; items.forEach(it=>{ const a=document.createElement('a'); a.href=it.href==='copy'?'#':it.href; a.textContent=it.label; a.style.display='block'; a.style.padding='6px 8px'; a.addEventListener('click', ev=>{ ev.preventDefault(); if (it.href==='copy') { navigator.clipboard?.writeText(url).then(()=>alert('Link copied')).catch(()=>prompt('Copy this URL',url)); } else { window.open(it.href,'_blank','noopener'); } }); menu.appendChild(a); }); document.body.appendChild(menu); setTimeout(()=>{ const rm=()=>{ menu.remove(); window.removeEventListener('click',rm); }; window.addEventListener('click',rm); },50); } });
});

function escapeHtml(s){ return String(s).replace(/[&<>\"]/g,function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
function nl2br(s){ return s.replace(/\r?\n/g,'<br>'); }
function renderCommentNode(c){ const node=document.createElement('article'); node.className='comment'; node.setAttribute('data-id','c'+c.id); const av=document.createElement('div'); av.className='comment-avatar'; av.innerHTML='<div class="avatar-circle">'+(c.name?c.name.charAt(0).toUpperCase():'A')+'</div>'; const main=document.createElement('div'); main.className='comment-main'; main.innerHTML='<div class="comment-meta"><strong>'+escapeHtml(c.name||'Anonymous')+'</strong> <span class="muted">· just now</span></div><div class="comment-body">'+nl2br(escapeHtml(c.content))+'</div><div class="comment-actions"><button class="btn-link btn-reply" data-id="'+c.id+'">Reply</button></div>'; node.appendChild(av); node.appendChild(main); const reply=main.querySelector('.btn-reply'); if (reply) reply.addEventListener('click', function(){ document.getElementById('parent_id').value=this.dataset.id; document.getElementById('cancelReply').style.display='inline-block'; document.querySelector('.comment-form-wrap').scrollIntoView({behavior:'smooth', block:'center'}); }); return node; }

<?php require_once __DIR__ . '/includes/footer.php';
