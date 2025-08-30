<?php
require_once __DIR__ . "/../includes/auth.php";
requireLogin();

echo "<h1>Welcome, " . $_SESSION['user']['name'] . " (" . $_SESSION['user']['role_name'] . ")</h1>";

if (userRole() === 'admin') {
    echo "<a href='users.php'>Manage Users</a><br>";
    echo "<a href='roles.php'>Manage Roles</a><br>";
}

echo "<a href='courses.php'>Manage Courses</a><br>";
echo "<a href='posts.php'>Manage Posts</a><br>";
echo "<a href='../logout.php'>Logout</a>";
