<?php
$docrootRaw = $_SERVER['DOCUMENT_ROOT'] ?? '';
$docrootNorm = $docrootRaw ? str_replace('\\', '/', rtrim($docrootRaw, '/\\')) : '';
$publicDirRaw = realpath(__DIR__ . '/public') ?: '';
$publicDirNorm = $publicDirRaw ? str_replace('\\', '/', $publicDirRaw) : '';
$docrootLower = strtolower($docrootNorm);
$publicLower = strtolower($publicDirNorm);
$relativePublic = '';
if ($docrootLower !== '' && $publicLower !== '' && strpos($publicLower, $docrootLower) === 0) {
    $relativePublic = ltrim(substr($publicDirNorm, strlen($docrootNorm)), '/');
}

echo "DOCUMENT_ROOT raw: $docrootRaw\n";
echo "DOCUMENT_ROOT norm: $docrootNorm\n";
echo "publicDir raw: $publicDirRaw\n";
echo "publicDir norm: $publicDirNorm\n";
echo "relativePublic: $relativePublic\n";
