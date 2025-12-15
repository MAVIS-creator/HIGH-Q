<?php
// Paginated community feed for infinite scroll
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/community_renderer.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json');

try {
    $PAGE_SIZE = 6;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $PAGE_SIZE;
    $qterm = trim($_GET['q'] ?? '');
    $ftopic = trim($_GET['topic'] ?? '');
    $sortParam = $_GET['sort'] ?? null;
    $sort = in_array($sortParam, ['newest', 'active'], true) ? $sortParam : 'newest';

    $baseSql = ' FROM forum_questions q';
    $where = [];
    $params = [];
    if ($qterm !== '') {
        $where[] = ' (q.content LIKE ? OR q.name LIKE ?) ';
        $params[] = "%$qterm%";
        $params[] = "%$qterm%";
    }
    if ($ftopic !== '') {
        $where[] = ' q.topic = ? ';
        $params[] = $ftopic;
    }
    $whereSql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

    // Count for pagination
    $countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql . $whereSql);
    foreach ($params as $i => $p) { $countStmt->bindValue($i + 1, $p, PDO::PARAM_STR); }
    $countStmt->execute();
    $totalQuestions = (int)$countStmt->fetchColumn();

    $sql = 'SELECT
        q.id,
        q.name,
        q.topic,
        q.content,
        q.created_at,
        (SELECT COALESCE(SUM(vote),0) FROM forum_votes v WHERE v.question_id = q.id) AS vote_score,
        (SELECT COUNT(*) FROM forum_replies fr WHERE fr.question_id = q.id) AS replies_count,
        (SELECT COALESCE(MAX(created_at), q.created_at) FROM forum_replies fr2 WHERE fr2.question_id = q.id) AS last_activity
      ' . $baseSql . $whereSql;
    $sql .= ($sort === 'active') ? ' ORDER BY last_activity DESC ' : ' ORDER BY q.created_at DESC ';
    $sql .= ' LIMIT ? OFFSET ?';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $p) { $stmt->bindValue($i + 1, $p, PDO::PARAM_STR); }
    $stmt->bindValue(count($params) + 1, $PAGE_SIZE, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $questionIds = array_column($questions, 'id');
    if (empty($questionIds)) {
        echo json_encode(['status' => 'ok', 'html' => '', 'has_more' => false, 'next_page' => null]);
        exit;
    }

    // Fetch user votes for questions
    $userQuestionVotes = [];
    $inQ = implode(',', array_fill(0, count($questionIds), '?'));
    $vq = $pdo->prepare("SELECT question_id, vote FROM forum_votes WHERE question_id IN ($inQ) AND (session_id = ? OR ip = ?)");
    foreach ($questionIds as $i => $qid) { $vq->bindValue($i + 1, $qid, PDO::PARAM_INT); }
    $vq->bindValue(count($questionIds) + 1, session_id(), PDO::PARAM_STR);
    $vq->bindValue(count($questionIds) + 2, $_SERVER['REMOTE_ADDR'] ?? null, PDO::PARAM_STR);
    $vq->execute();
    foreach ($vq->fetchAll(PDO::FETCH_ASSOC) as $row) { $userQuestionVotes[(int)$row['question_id']] = (int)$row['vote']; }

    // Replies and vote scores
    $repliesByQuestion = [];
    $replyIds = [];
    $in = implode(',', array_fill(0, count($questionIds), '?'));
    $rs = $pdo->prepare("SELECT id, question_id, parent_id, name, content, created_at, (SELECT COALESCE(SUM(vote),0) FROM forum_votes v WHERE v.reply_id = fr.id) AS vote_score FROM forum_replies fr WHERE fr.question_id IN ($in) ORDER BY fr.created_at ASC");
    foreach ($questionIds as $i => $qid) { $rs->bindValue($i + 1, $qid, PDO::PARAM_INT); }
    $rs->execute();
    $rows = $rs->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $replyIds[] = (int)$r['id'];
        $repliesByQuestion[(int)$r['question_id']][] = $r;
    }

    // User votes for replies
    $userReplyVotes = [];
    if (!empty($replyIds)) {
        $inR = implode(',', array_fill(0, count($replyIds), '?'));
        $vr = $pdo->prepare("SELECT reply_id, vote FROM forum_votes WHERE reply_id IN ($inR) AND (session_id = ? OR ip = ?)");
        foreach ($replyIds as $i => $rid) { $vr->bindValue($i + 1, $rid, PDO::PARAM_INT); }
        $vr->bindValue(count($replyIds) + 1, session_id(), PDO::PARAM_STR);
        $vr->bindValue(count($replyIds) + 2, $_SERVER['REMOTE_ADDR'] ?? null, PDO::PARAM_STR);
        $vr->execute();
        foreach ($vr->fetchAll(PDO::FETCH_ASSOC) as $row) { $userReplyVotes[(int)$row['reply_id']] = (int)$row['vote']; }
    }

    $hasMore = ($offset + count($questions) < $totalQuestions);

    $htmlParts = [];
    foreach ($questions as $qq) {
        $htmlParts[] = hq_render_question_card($qq, $repliesByQuestion[$qq['id']] ?? [], $userQuestionVotes, $userReplyVotes);
    }

    echo json_encode([
        'status' => 'ok',
        'html' => implode('', $htmlParts),
        'has_more' => $hasMore,
        'next_page' => $hasMore ? $page + 1 : null
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to load feed']);
}
