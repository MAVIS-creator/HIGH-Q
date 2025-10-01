// Client-side logic for post page: comments, likes, share
// Consolidated and externalized from post.php
(function () {
  'use strict';
  const POST_ID = window.POST_ID || 0;
  const likeBtn = document.getElementById('likeBtn');
  const likesCountEl = document.getElementById('likesCount');
  const commentForm = document.getElementById('commentForm');
  const commentsList = document.getElementById('commentsList');
  const cancelReply = document.getElementById('cancelReply');
  const parentInput = document.getElementById('parent_id');

  function fetchComments() {
    if (!commentsList) return;
    fetch('/HIGH-Q/public/api/comments.php?post_id=' + encodeURIComponent(POST_ID))
      .then(r => r.json())
      .then(data => {
        commentsList.innerHTML = '';
        if (!Array.isArray(data)) {
          commentsList.innerHTML = '<p class="muted">No comments</p>';
          return;
        }
        data.forEach(c => commentsList.appendChild(renderCommentNode(c)));
      }).catch(() => { commentsList.innerHTML = '<p class="muted">Unable to load comments.</p>'; });
  }

  function renderCommentNode(c) {
    const node = document.createElement('article');
    node.className = 'comment';
    node.setAttribute('data-id', 'c' + c.id);

    const av = document.createElement('div'); av.className = 'comment-avatar'; av.innerHTML = '<div class="avatar-circle">' + (c.name ? c.name.charAt(0).toUpperCase() : 'A') + '</div>';
    const main = document.createElement('div'); main.className = 'comment-main';
    main.innerHTML = '<div class="comment-meta"><strong>' + escapeHtml(c.name || 'Anonymous') + '</strong> <span class="muted">· ' + escapeHtml(c.created_at || '') + '</span></div>' +
      '<div class="comment-body">' + nl2br(escapeHtml(c.content || '')) + '</div>' +
      '<div class="comment-actions"><button class="btn-link btn-reply" data-id="' + c.id + '">Reply</button></div>';

    node.appendChild(av); node.appendChild(main);
    const reply = main.querySelector('.btn-reply');
    if (reply) reply.addEventListener('click', function () {
      parentInput.value = this.dataset.id;
      if (cancelReply) cancelReply.style.display = 'inline-block';
      document.querySelector('.comment-form-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    if (c.replies && Array.isArray(c.replies) && c.replies.length) {
      const replies = document.createElement('div'); replies.className = 'replies';
      c.replies.forEach(r => replies.appendChild(renderCommentNode(r)));
      main.appendChild(replies);
    }
    return node;
  }

  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; }); }
  function nl2br(s) { return String(s).replace(/\r?\n/g, '<br>'); }

  function handleLikeResponse(j) {
    if (!j) return;
    if (typeof j.count !== 'undefined') {
      if (likesCountEl) likesCountEl.textContent = j.count;
    } else if (typeof j.likes !== 'undefined') {
      if (likesCountEl) likesCountEl.textContent = j.likes;
    }
  }

  if (likeBtn) {
    likeBtn.addEventListener('click', function () {
      const b = this; b.disabled = true;
      fetch('/HIGH-Q/public/api/like_post.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'post_id=' + encodeURIComponent(POST_ID) })
        .then(r => r.json()).then(handleLikeResponse).catch(() => { }).finally(() => b.disabled = false);
    });
  }

  if (commentForm) {
    commentForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(this);
      const btn = this.querySelector('button[type=submit]'); if (btn) btn.disabled = true;
      fetch('/HIGH-Q/public/api/comments.php', { method: 'POST', body: fd }).then(r => r.json()).then(j => {
        if (j && j.status === 'ok') {
          if (j.comment) {
            const node = renderCommentNode(j.comment);
            if (j.comment.parent_id) {
              const parent = document.getElementById('commentsList')?.querySelector('.comment[data-id="c' + j.comment.parent_id + '"]');
              if (parent) {
                let replies = parent.querySelector('.replies');
                if (!replies) { replies = document.createElement('div'); replies.className = 'replies'; parent.querySelector('.comment-main').appendChild(replies); }
                replies.insertBefore(node, replies.firstChild);
              } else document.getElementById('commentsList')?.insertBefore(node, document.getElementById('commentsList').firstChild);
            } else document.getElementById('commentsList')?.insertBefore(node, document.getElementById('commentsList').firstChild);
          } else {
            alert(j.message || 'Comment submitted');
          }
          this.reset(); if (parentInput) parentInput.value = ''; if (cancelReply) cancelReply.style.display = 'none';
        } else {
          alert(j?.message || 'Error');
        }
      }).catch(() => alert('Network error')).finally(() => { if (btn) btn.disabled = false; });
    });
  }

  if (cancelReply) cancelReply.addEventListener('click', function () { if (parentInput) parentInput.value = ''; this.style.display = 'none'; });

  // Share menu
  const shareBtn = document.getElementById('shareBtn');
  if (shareBtn) shareBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    const url = window.location.href; const title = document.querySelector('h1')?.textContent || document.title;
    const items = [
      { label: 'Twitter', href: 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url) },
      { label: 'Facebook', href: 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url) },
      { label: 'WhatsApp', href: 'https://api.whatsapp.com/send?text=' + encodeURIComponent(title + ' ' + url) },
      { label: 'Copy', href: 'copy' }
    ];
    let menu = document.querySelector('.share-menu'); if (menu) { menu.remove(); menu = null; }
    if (!menu) {
      menu = document.createElement('div'); menu.className = 'share-menu'; menu.style.position = 'absolute'; menu.style.right = '20px'; menu.style.top = (e.pageY || 80) + 'px'; menu.style.background = '#fff'; menu.style.border = '1px solid #eee'; menu.style.padding = '8px';
      items.forEach(it => {
        const a = document.createElement('a'); a.href = it.href === 'copy' ? '#' : it.href; a.textContent = it.label; a.style.display = 'block'; a.style.padding = '6px 8px';
        a.addEventListener('click', ev => { ev.preventDefault(); if (it.href === 'copy') { navigator.clipboard?.writeText(url).then(() => alert('Link copied')).catch(() => prompt('Copy this URL', url)); } else { window.open(it.href, '_blank', 'noopener'); } });
        menu.appendChild(a);
      });
      document.body.appendChild(menu);
      setTimeout(() => { const rm = () => { menu.remove(); window.removeEventListener('click', rm); }; window.addEventListener('click', rm); }, 50);
    }
  });

  // Initial load
  if (POST_ID) fetchComments();
})();
// Client-side logic for post page: comments, likes, share
// Kept minimal to avoid triggering the JS language server on embedded PHP
(function () {
  'use strict';
  const POST_ID = window.POST_ID || 0;
  const likeBtn = document.getElementById('likeBtn');
  const likesCountEl = document.getElementById('likesCount');
  const commentForm = document.getElementById('commentForm');
  const commentsList = document.getElementById('commentsList');
  const cancelReply = document.getElementById('cancelReply');
  const parentInput = document.getElementById('parent_id');

  function fetchComments() {
    fetch('/HIGH-Q/public/api/comments.php?post_id=' + encodeURIComponent(POST_ID))
      .then(r => r.json())
      .then(data => {
        commentsList.innerHTML = '';
        if (!Array.isArray(data)) return;
        data.forEach(c => {
          const el = renderComment(c);
          commentsList.appendChild(el);
        });
      }).catch(() => { commentsList.innerHTML = '<p class="muted">Unable to load comments.</p>'; });
  }

  function renderComment(c) {
    const wrapper = document.createElement('div');
    wrapper.className = 'comment';
    wrapper.innerHTML = '<div class="comment-head"><strong>' + escapeHtml(c.name || 'Anonymous') + '</strong> <span class="muted small">' + escapeHtml(c.created_at || '') + '</span></div><div class="comment-body">' + (c.content || '') + '</div><div class="comment-actions"><button class="reply-btn">Reply</button></div>';
    const replyBtn = wrapper.querySelector('.reply-btn');
    replyBtn.addEventListener('click', function () {
      parentInput.value = c.id;
      cancelReply.style.display = 'inline-block';
      commentForm.scrollIntoView({behavior:'smooth'});
    });
    return wrapper;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; });
  }

  if (likeBtn) {
    likeBtn.addEventListener('click', function () {
      fetch('/HIGH-Q/public/api/like_post.php', { method: 'POST', body: new URLSearchParams({ post_id: POST_ID }) })
        .then(r => r.json())
        .then(j => { if (j && typeof j.likes !== 'undefined') likesCountEl.textContent = j.likes; })
        .catch(() => {});
    });
  }

  if (commentForm) {
    commentForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      const fd = new FormData(commentForm);
      fetch('/HIGH-Q/public/api/comments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(j => { if (j && j.success) { commentForm.reset(); parentInput.value=''; cancelReply.style.display='none'; fetchComments(); } else { alert(j && j.message ? j.message : 'Unable to post comment'); } })
        .catch(() => { alert('Unable to post comment'); });
    });
  }

  if (cancelReply) cancelReply.addEventListener('click', function () { parentInput.value=''; cancelReply.style.display='none'; });

  // initial load
  if (POST_ID) fetchComments();
})();
