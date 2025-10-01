// Client-side logic for post page: comments, likes, share
// Kept minimal to avoid triggering the JS language server on embedded PHP
(function () {
  'use strict';
  const POST_ID = window.POST_ID || 0;
  const likeBtn = document.getElementById('likeBtn');
  const likesCountEl = document.getElementById('likesCount');
  const commentsCountEl = document.getElementById('commentsCount');
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

  // like handling: query initial state and allow one like per visitor (sessionStorage + server guard)
  function setLikedUI(isLiked) {
    if (!likeBtn) return;
    if (isLiked) {
      likeBtn.classList.add('liked');
      likeBtn.innerHTML = '<i class="fa-regular fa-heart"></i> Liked';
    } else {
      likeBtn.classList.remove('liked');
      likeBtn.innerHTML = '<i class="fa-regular fa-heart"></i> Like';
    }
  }

  if (likeBtn && POST_ID) {
    // initial GET to learn current likes and whether visitor has liked
    fetch('/HIGH-Q/public/api/like_post.php?post_id=' + encodeURIComponent(POST_ID))
      .then(r => r.json())
      .then(j => {
        if (j && typeof j.likes !== 'undefined') likesCountEl.textContent = j.likes;
        var sessionLiked = sessionStorage.getItem('liked_post_' + POST_ID) === '1';
        setLikedUI(j && j.liked || sessionLiked);
      }).catch(() => {});

    likeBtn.addEventListener('click', function () {
      // client-side guard
      if (sessionStorage.getItem('liked_post_' + POST_ID) === '1') return;
      // optimistically set UI
      setLikedUI(true);
      fetch('/HIGH-Q/public/api/like_post.php', { method: 'POST', body: new URLSearchParams({ post_id: POST_ID }) })
        .then(r => r.json())
        .then(j => {
          if (j && typeof j.likes !== 'undefined') likesCountEl.textContent = j.likes;
          if (j && j.liked) {
            sessionStorage.setItem('liked_post_' + POST_ID, '1');
            setLikedUI(true);
          } else {
            // server refused as duplicate, revert UI
            setLikedUI(false);
          }
        }).catch(() => {
          // on error, revert UI change
          setLikedUI(false);
        });
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
