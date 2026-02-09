// Client-side logic for post page: comments, likes, share
// Enhanced for modern React-UI inspired design
(function () {
  'use strict';
  const POST_ID = window.POST_ID || 0;
  const likeBtn = document.getElementById('likeBtn');
  const likesCountEl = document.getElementById('likesCount');
  const commentsCountEl = document.getElementById('commentsCount');
  const commentsCountBadge = document.getElementById('commentsCountBadge');
  const commentForm = document.getElementById('commentForm');
  const commentsList = document.getElementById('commentsList');
  const cancelReply = document.getElementById('cancelReply');
  const parentInput = document.getElementById('parent_id');

  function updateCommentCounters(count) {
    const n = typeof count === 'number' ? count : 0;
    if (commentsCountEl) commentsCountEl.textContent = n;
    if (commentsCountBadge) commentsCountBadge.textContent = n;
    const clone = document.getElementById('commentsCountClone');
    if (clone) clone.textContent = n;
  }

  function fetchComments() {
    fetch('api/comments.php?post_id=' + encodeURIComponent(POST_ID))
      .then(r => r.json())
      .then(data => {
        commentsList.innerHTML = '';
        if (!Array.isArray(data)) return;
        data.forEach((c, i) => {
          const el = renderComment(c);
          el.style.animationDelay = (i * 0.1) + 's';
          commentsList.appendChild(el);
        });
        updateCommentCounters(data.length);
      }).catch(() => { 
        commentsList.innerHTML = '<p class="post-toc-empty">Unable to load comments.</p>'; 
      });
  }

  function renderComment(c, isReply) {
    const wrapper = document.createElement('div');
    wrapper.className = 'comment-card';
    if (isReply) wrapper.classList.add('reply');
    wrapper.setAttribute('data-id', c.id || '');

    const avatarClass = c.user_id ? 'comment-avatar admin' : 'comment-avatar';
    const initial = (c.name || 'A').substr(0, 1).toUpperCase();
    const adminBadge = c.user_id ? '<span class="admin-badge">Admin</span>' : '';
    const dateFormatted = c.created_at ? formatDate(c.created_at) : '';

    wrapper.innerHTML = `
      <div class="comment-header">
        <div class="${avatarClass}">${escapeHtml(initial)}</div>
        <div class="comment-meta">
          <div class="comment-author">${escapeHtml(c.name || 'Anonymous')} ${adminBadge}</div>
          <div class="comment-date">${escapeHtml(dateFormatted)}</div>
        </div>
      </div>
      <div class="comment-body">${escapeHtml(c.content || '').replace(/\n/g, '<br>')}</div>
      <div class="comment-actions">
        <button class="comment-action-btn like-btn" data-id="${c.id || ''}">
          <i class="bx bx-heart"></i> <span class="like-count">${c.likes || 0}</span>
        </button>
        <button class="comment-action-btn reply-btn" data-id="${c.id || ''}">
          <i class="bx bx-reply"></i> Reply
        </button>
        ${c.can_delete ? '<button class="comment-action-btn delete-btn" data-id="' + (c.id || '') + '"><i class="bx bx-trash"></i> Delete</button>' : ''}
      </div>
    `;

    // Pending comment indicator
    if (c && c.status === 'pending') {
      wrapper.classList.add('pending');
      const bodyEl = wrapper.querySelector('.comment-body');
      if (bodyEl) bodyEl.insertAdjacentHTML('beforeend', '<div class="comment-pending-badge"><i class="bx bx-time-five"></i> Awaiting moderation</div>');
    }

    // bind reply
    wrapper.querySelector('.reply-btn').addEventListener('click', function () {
      parentInput.value = c.id;
      cancelReply.style.display = 'inline-block';
      commentForm.scrollIntoView({behavior:'smooth'});
      commentForm.querySelector('[name="content"]').focus();
    });

    // bind like for comment
    const likeBtnC = wrapper.querySelector('.like-btn');
    likeBtnC.addEventListener('click', function () {
      const cid = this.getAttribute('data-id');
      const isCurrentlyLiked = getCookie('liked_comment_' + cid) === '1';
      
      // Optimistically toggle UI
      if (isCurrentlyLiked) {
        this.classList.remove('liked');
      } else {
        this.classList.add('liked');
      }
      
      fetch('api/comment_like.php', { method: 'POST', body: new URLSearchParams({ comment_id: cid }) })
        .then(r => r.json())
        .then(j => {
          if (j && typeof j.likes !== 'undefined') {
            this.querySelector('.like-count').textContent = j.likes;
          }
          if (j && typeof j.liked !== 'undefined') {
            if (j.liked) {
              this.classList.add('liked');
              setCookie('liked_comment_' + cid, '1', 30);
            } else {
              this.classList.remove('liked');
              deleteCookie('liked_comment_' + cid);
            }
          }
        }).catch(() => {
          // Revert UI on error
          if (isCurrentlyLiked) {
            this.classList.add('liked');
          } else {
            this.classList.remove('liked');
          }
        });
    });

    // render replies if provided
    if (Array.isArray(c.replies) && c.replies.length) {
      const repliesWrap = document.createElement('div');
      repliesWrap.className = 'comment-replies';
      c.replies.forEach(r => {
        const relEl = renderComment(r, true);
        repliesWrap.appendChild(relEl);
      });
      wrapper.appendChild(repliesWrap);
    }

    return wrapper;
  }

  // Format date nicely
  function formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
  }

  // Delegated delete handler on commentsList
  if (commentsList) {
    commentsList.addEventListener('click', function (ev) {
      var btn = ev.target.closest && ev.target.closest('.delete-btn');
      if (!btn) return;
      var cid = btn.getAttribute('data-id');
      if (!cid) return;
      if (typeof Swal !== 'undefined') {
        Swal.fire({ 
          title: 'Delete comment?', 
          text: 'This will remove your comment permanently', 
          icon: 'warning', 
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          confirmButtonText: 'Yes, delete it'
        }).then(function (res) {
          if (!res.isConfirmed) return;
          doDelete(cid);
        });
      } else {
        if (!confirm('Delete comment?')) return; 
        doDelete(cid);
      }
    });
  }

  function doDelete(cid) {
    var fd = new URLSearchParams(); 
    fd.append('_method','delete'); 
    fd.append('comment_id', cid);
    fetch('api/comments.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(j => { 
        if (j && j.status === 'ok') {
          fetchComments(); 
        } else { 
          if (typeof Swal !== 'undefined') Swal.fire('Error', j.message || 'Delete failed','error'); 
          else alert(j.message || 'Delete failed'); 
        } 
      })
      .catch(() => { 
        if (typeof Swal !== 'undefined') Swal.fire('Error','Network error','error'); 
        else alert('Network error'); 
      });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (m) { 
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; 
    });
  }

  // Like handling for post
  function setLikedUI(isLiked) {
    if (!likeBtn) return;
    if (isLiked) {
      likeBtn.classList.add('liked');
      likeBtn.innerHTML = '<i class="bx bxs-heart"></i> <span>Liked</span>';
    } else {
      likeBtn.classList.remove('liked');
      likeBtn.innerHTML = '<i class="bx bx-heart"></i> <span>Like</span>';
    }
  }

  // Cookie helpers
  function setCookie(name, value, days) {
    var d = new Date(); 
    d.setTime(d.getTime() + (days*24*60*60*1000));
    document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
  }
  
  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? match[2] : null;
  }
  
  function deleteCookie(name) {
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;SameSite=Lax';
  }

  if (likeBtn && POST_ID) {
    // Initial GET to learn current likes and whether visitor has liked
    fetch('api/like_post.php?post_id=' + encodeURIComponent(POST_ID))
      .then(r => r.json())
      .then(j => {
        if (j && typeof j.likes !== 'undefined') likesCountEl.textContent = j.likes;
        var cookieLiked = getCookie('liked_post_' + POST_ID) === '1';
        setLikedUI(j && j.liked || cookieLiked);
      }).catch(() => {});

    likeBtn.addEventListener('click', function () {
      const currentlyLiked = getCookie('liked_post_' + POST_ID) === '1';
      
      // Optimistically flip UI with animation
      setLikedUI(!currentlyLiked);
      
      fetch('api/like_post.php', { method: 'POST', body: new URLSearchParams({ post_id: POST_ID }) })
        .then(r => r.json())
        .then(j => {
          if (j && typeof j.likes !== 'undefined') {
            likesCountEl.textContent = j.likes;
            // Animate the count
            likesCountEl.style.transform = 'scale(1.3)';
            setTimeout(() => { likesCountEl.style.transform = 'scale(1)'; }, 200);
          }
          if (j && typeof j.liked !== 'undefined') {
            if (j.liked) {
              setCookie('liked_post_' + POST_ID, '1', 30);
            } else {
              deleteCookie('liked_post_' + POST_ID);
            }
            setLikedUI(j.liked);
          } else {
            // No definite info: revert to previous
            if (currentlyLiked) setCookie('liked_post_' + POST_ID, '1', 30); 
            else deleteCookie('liked_post_' + POST_ID);
            setLikedUI(currentlyLiked);
          }
        }).catch(() => {
          // On error, revert UI change
          if (currentlyLiked) setCookie('liked_post_' + POST_ID, '1', 30);
          else deleteCookie('liked_post_' + POST_ID);
          setLikedUI(currentlyLiked);
        });
    });
  }

  if (commentForm) {
    commentForm.addEventListener('submit', function (ev) {
      ev.preventDefault();
      const submitBtn = commentForm.querySelector('.btn-submit-comment');
      const originalText = submitBtn.innerHTML;
      
      // Show loading state
      submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Submitting...';
      submitBtn.disabled = true;
      
      const fd = new FormData(commentForm);
      fetch('api/comments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(j => { 
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
          
          if (j && (j.success || j.status === 'ok')) { 
            commentForm.reset(); 
            parentInput.value = ''; 
            cancelReply.style.display = 'none'; 
            fetchComments();
            
            // Success feedback
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                title: 'Comment Submitted!',
                text: j.message || 'Your comment is awaiting moderation.',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
              });
            }
          } else { 
            var m = j && j.message ? j.message : 'Unable to post comment'; 
            if (typeof Swal !== 'undefined') Swal.fire('Error', m, 'error'); 
            else alert(m); 
          } 
        })
        .catch(() => { 
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
          var m = 'Unable to post comment'; 
          if (typeof Swal !== 'undefined') Swal.fire('Error', m, 'error'); 
          else alert(m); 
        });
    });
  }

  if (cancelReply) {
    cancelReply.addEventListener('click', function () { 
      parentInput.value = ''; 
      cancelReply.style.display = 'none'; 
    });
  }

  // Initial load
  if (POST_ID) fetchComments();
})();
