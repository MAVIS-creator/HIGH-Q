<?php
http_response_code(404);
if (!function_exists('app_url')) {
    if (file_exists(__DIR__ . '/../config/functions.php')) require_once __DIR__ . '/../config/functions.php';
}
$home = function_exists('app_url') ? app_url('index.php') : '../index.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .error-container { min-height: 60vh; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 2rem; }
  .error-code { font-size: 4rem; color: var(--hq-red); font-weight: bold; }
  .error-message { margin: 1rem 0; font-size: 1.2rem; color: var(--hq-gray); }
  .back-link { margin-top: 1rem; color: var(--hq-yellow); text-decoration: none; font-weight: 700; }
  .back-link:hover { text-decoration: underline; }
</style>

<div class="error-container">
  <div class="error-code">404</div>
  <h1>Page Not Found</h1>
  <p class="error-message">The page you're looking for does not exist or has been moved.</p>
  <a href="<?= htmlspecialchars($home) ?>" class="back-link">← Return to Homepage</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>