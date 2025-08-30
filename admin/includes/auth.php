<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit;
}
function requireRole($roles = []) {
    if (!in_array($_SESSION['user']['role_slug'], $roles)) {
        header("Location: index.php");
        exit;
    }
}
