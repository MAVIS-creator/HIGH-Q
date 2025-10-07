<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Helper: format plain-text (or simple Markdown) into safe HTML blocks
function format_plain_text_to_html($txt) {
  $txt = (string)$txt;
  if ($txt === '') return '';
  // Convert simple Markdown headings
  $txt = preg_replace(['/^###\s*(.+)$/m','/^##\s*(.+)$/m','/^#\s*(.+)$/m'], ['<h4>$1</h4>','<h3>$1</h3>','<h2>$1</h2>'], $txt);
  // Split by blank lines into blocks
  $blocks = preg_split('/\n\s*\n/', $txt);
  $out = '';
  foreach ($blocks as $b) {
    $b = trim($b);
    if ($b === '') continue;
    if (preg_match('/<[^>]+>/', $b)) {
      // includes HTML tag — trust it
      $out .= $b;
    } else {
      $out .= '<p>' . nl2br(htmlspecialchars($b)) . '</p>';
    }
  }
  return $out;
}
