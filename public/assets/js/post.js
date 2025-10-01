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
