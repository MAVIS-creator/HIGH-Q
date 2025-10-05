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

  function renderComment(c, isReply) {
    const wrapper = document.createElement('div');
    wrapper.className = 'comment';
    if (isReply) wrapper.classList.add('reply');
    const avatar = '<div class="avatar">' + escapeHtml((c.name||'A').substr(0,1).toUpperCase()) + '</div>';
    const head = '<div class="comment-head">' + avatar + '<div><strong>' + escapeHtml(c.name || 'Anonymous') + '</strong><div class="meta small">' + escapeHtml(c.created_at || '') + '</div></div></div>';
    const body = '<div class="comment-body">' + (c.content || '') + '</div>';
  const likes = '<button class="like-btn" data-id="' + (c.id||'') + '"><i class="bx bx-heart"></i> <span class="like-count">' + (c.likes||0) + '</span></button>';
  const reply = '<button class="reply-btn" data-id="' + (c.id||'') + '"><i class="bx bx-reply"></i> Reply</button>';

    wrapper.innerHTML = head + body + '<div class="comment-actions">' + likes + ' ' + reply + '</div>';

    // if comment is pending and was created in this session, show awaiting-moderation marker
    if (c && c.status === 'pending') {
      wrapper.classList.add('pending-comment');
      const bodyEl = wrapper.querySelector('.comment-body');
      if (bodyEl) bodyEl.insertAdjacentHTML('beforeend', '<div class="muted small">⏳ Awaiting moderation</div>');
    }

    // bind reply
    wrapper.querySelector('.reply-btn').addEventListener('click', function () {
      parentInput.value = c.id;
      cancelReply.style.display = 'inline-block';
      commentForm.scrollIntoView({behavior:'smooth'});
    });

    // bind like for comment
    const likeBtnC = wrapper.querySelector('.like-btn');
    likeBtnC.addEventListener('click', function () {
      const cid = this.getAttribute('data-id');
      if (sessionStorage.getItem('liked_comment_' + cid) === '1') return;
      fetch('/HIGH-Q/public/api/comment_like.php', { method: 'POST', body: new URLSearchParams({ comment_id: cid }) })
        .then(r => r.json())
        .then(j => {
          if (j && typeof j.likes !== 'undefined') {
            this.querySelector('.like-count').textContent = j.likes;
          }
          if (j && j.liked) sessionStorage.setItem('liked_comment_' + cid, '1');
        }).catch(()=>{});
    });

    // render replies if provided
    if (Array.isArray(c.replies) && c.replies.length) {
      const repliesWrap = document.createElement('div');
      repliesWrap.className = 'replies';
      c.replies.forEach(r => {
        const relEl = renderComment(r, true);
        repliesWrap.appendChild(relEl);
      });
      wrapper.appendChild(repliesWrap);
    }

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
      // toggle like: allow unliking by calling the same endpoint which now toggles
      // optimistically flip UI
      const currentlyLiked = sessionStorage.getItem('liked_post_' + POST_ID) === '1';
      setLikedUI(!currentlyLiked);
      fetch('/HIGH-Q/public/api/like_post.php', { method: 'POST', body: new URLSearchParams({ post_id: POST_ID }) })
        .then(r => r.json())
        .then(j => {
          if (j && typeof j.likes !== 'undefined') likesCountEl.textContent = j.likes;
          if (j && typeof j.liked !== 'undefined') {
            if (j.liked) sessionStorage.setItem('liked_post_' + POST_ID, '1'); else sessionStorage.removeItem('liked_post_' + POST_ID);
            setLikedUI(j.liked);
          } else {
            // no definite info: revert to previous
            if (currentlyLiked) sessionStorage.setItem('liked_post_' + POST_ID, '1'); else sessionStorage.removeItem('liked_post_' + POST_ID);
            setLikedUI(currentlyLiked);
          }
        }).catch(() => {
          // on error, revert UI change
          if (currentlyLiked) sessionStorage.setItem('liked_post_' + POST_ID, '1'); else sessionStorage.removeItem('liked_post_' + POST_ID);
          setLikedUI(currentlyLiked);
        });
    });
  }

  if (commentForm) {
    commentForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      const fd = new FormData(commentForm);
      fetch('/HIGH-Q/public/api/comments.php', { method: 'POST', body: fd })
  .then(r => r.json())
  .then(j => { if (j && j.success) { commentForm.reset(); parentInput.value=''; cancelReply.style.display='none'; fetchComments(); } else { var m = j && j.message ? j.message : 'Unable to post comment'; if (typeof Swal !== 'undefined') Swal.fire('Error', m, 'error'); else alert(m); } })
  .catch(() => { var m = 'Unable to post comment'; if (typeof Swal !== 'undefined') Swal.fire('Error', m, 'error'); else alert(m); });
    });
  }

  if (cancelReply) cancelReply.addEventListener('click', function () { parentInput.value=''; cancelReply.style.display='none'; });

  // initial load
  if (POST_ID) fetchComments();
})();
