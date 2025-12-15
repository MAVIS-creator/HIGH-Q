<?php
// Shared renderer for community question cards (page + API)
function hq_render_question_card(array $qq, array $replies, array $userQuestionVotes, array $userReplyVotes): string {
    ob_start();
    $hue = (int)(hexdec(substr(md5(($qq['name'] ?? 'A')), 0, 2)) / 255 * 360);
    $iso = htmlspecialchars(date('c', strtotime($qq['created_at'])));
    $qUserVote = $userQuestionVotes[$qq['id']] ?? 0;

    // Build reply tree
    $byParent = [];
    foreach ($replies as $rep) {
        $byParent[(int)($rep['parent_id'] ?? 0)][] = $rep;
    }

    $renderReplies = function ($parentId, $depth = 0) use (&$renderReplies, $byParent, $qq, $userReplyVotes) {
        if (empty($byParent[$parentId])) return;
        $limit = ($parentId === 0) ? 3 : null;
        $children = $byParent[$parentId];
        $total = count($children);
        foreach ($children as $idx => $rep) {
            $h2 = (int)(hexdec(substr(md5(($rep['name'] ?? 'A')), 0, 2)) / 255 * 360);
            $isoR = htmlspecialchars(date('c', strtotime($rep['created_at'])));
            $hideClass = ($limit !== null && $idx >= $limit && $total > $limit) ? ' hidden-reply' : '';
            $rUserVote = $userReplyVotes[$rep['id']] ?? 0;
            ?>
            <div class="forum-reply<?= $hideClass ?>" data-qid="<?= $qq['id'] ?>" data-rid="<?= $rep['id'] ?>" style="margin-left:<?= max(0, $depth) * 16 ?>px">
              <div class="post-header">
                <div class="vote-stack">
                  <button class="vote-btn vote-up <?= $rUserVote === 1 ? 'active' : '' ?>" data-type="reply" data-id="<?= $rep['id'] ?>" data-vote="1"><i class='bx bx-chevron-up'></i></button>
                  <div class="vote-score" id="rscore-<?= $rep['id'] ?>"><?= (int)($rep['vote_score'] ?? 0) ?></div>
                  <button class="vote-btn vote-down <?= $rUserVote === -1 ? 'active' : '' ?>" data-type="reply" data-id="<?= $rep['id'] ?>" data-vote="-1"><i class='bx bx-chevron-down'></i></button>
                </div>
                <div class="avatar" style="background:linear-gradient(135deg,hsl(<?= $h2 ?> 75% 55%),hsl(<?= ($h2 + 30) % 360 ?> 75% 45%))"><?= strtoupper(substr($rep['name'], 0, 1)) ?></div>
                <div class="post-content">
                  <div class="post-meta">
                    <strong class="username"><?= htmlspecialchars($rep['name']) ?></strong>
                    <span class="time" data-time="<?= $isoR ?>"><?= htmlspecialchars($rep['created_at']) ?></span>
                  </div>
                  <div class="post-body"><?= nl2br(htmlspecialchars($rep['content'])) ?></div>
                  <div class="post-actions" style="margin-top:4px;">
                    <button class="btn-lite reply-toggle" data-id="<?= $qq['id'] ?>" data-parent="<?= $rep['id'] ?>"><i class='bx bx-reply'></i> Reply</button>
                  </div>
                </div>
              </div>
            </div>
            <?php
            $renderReplies((int)$rep['id'], $depth + 1);
        }
        if ($limit !== null && $total > $limit) {
            ?>
            <button class="expand-replies" data-qid="<?= $qq['id'] ?>"><i class='bx bx-chevron-down'></i> Show <?= $total - $limit ?> more replies</button>
            <?php
        }
    };
    ?>
    <div class="forum-question" id="q<?= (int)$qq['id'] ?>">
      <div class="post-header">
        <div class="vote-stack">
          <button class="vote-btn vote-up <?= $qUserVote === 1 ? 'active' : '' ?>" data-type="question" data-id="<?= $qq['id'] ?>" data-vote="1"><i class='bx bx-chevron-up'></i></button>
          <div class="vote-score" id="qscore-<?= $qq['id'] ?>"><?= (int)($qq['vote_score'] ?? 0) ?></div>
          <button class="vote-btn vote-down <?= $qUserVote === -1 ? 'active' : '' ?>" data-type="question" data-id="<?= $qq['id'] ?>" data-vote="-1"><i class='bx bx-chevron-down'></i></button>
        </div>
        <div class="avatar" style="background:linear-gradient(135deg,hsl(<?= $hue ?> 75% 55%),hsl(<?= ($hue + 30) % 360 ?> 75% 45%))">
          <?= strtoupper(substr($qq['name'], 0, 1)) ?>
        </div>
        <div class="post-content">
          <div class="post-meta">
            <strong class="username"><?= htmlspecialchars($qq['name']) ?></strong>
            <span class="time" data-time="<?= $iso ?>"><?= htmlspecialchars($qq['created_at']) ?></span>
            <?php if (!empty($qq['topic'])): ?><span class="badge"><i class='bx bx-purchase-tag-alt'></i><?= htmlspecialchars($qq['topic']) ?></span><?php endif; ?>
          </div>
          <div class="post-body">
            <?= nl2br(htmlspecialchars($qq['content'])) ?>
          </div>
          <div class="post-actions">
            <button class="btn-lite reply-toggle" data-id="<?= $qq['id'] ?>"><i class='bx bx-message-rounded-dots'></i>Reply (<?= (int)($qq['replies_count'] ?? 0) ?>)</button>
            <button class="btn-lite" onclick="navigator.clipboard.writeText(location.origin+location.pathname+'#q<?= (int)$qq['id'] ?>'); this.innerHTML='<i class=\'bx bx-check\'></i>Copied'; setTimeout(()=>this.innerHTML='<i class=\'bx bx-link-alt\'></i>Share',1500)"><i class='bx bx-link-alt'></i>Share</button>
          </div>
        </div>
      </div>

      <?php if (!empty($byParent[0])): ?>
        <div class="post-replies" id="replies-<?= $qq['id'] ?>">
          <?php $renderReplies(0); ?>
        </div>
      <?php endif; ?>

      <form method="post" class="forum-reply-form" data-qid="<?= $qq['id'] ?>" style="display:none;">
        <input type="hidden" name="question_id" value="<?= $qq['id'] ?>">
        <input type="hidden" name="parent_id" value="">
        <div class="form-row">
          <input class="form-input" type="text" name="rname" placeholder="Name (optional)">
        </div>
        <div class="form-row">
          <textarea class="form-textarea" name="rcontent" rows="3" placeholder="Write your reply..." required></textarea>
        </div>
        <div class="form-actions">
          <button class="btn-approve" type="submit">Post Reply</button>
        </div>
      </form>
    </div>
    <?php
    return ob_get_clean();
}
