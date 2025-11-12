<?php
// Central menu definition used by sidebar and roles management
// Key = slug used in role_permissions.menu_slug
// url should point to the pages loader by default: index.php?pages=<slug>

return [
    'dashboard' => ['title' => 'Dashboard', 'icon' => 'bx bxs-dashboard', 'url' => 'index.php?pages=dashboard'],
    'users'     => ['title' => 'Manage Users', 'icon' => 'bx bxs-user-detail', 'url' => 'index.php?pages=users'],
    'roles'     => ['title' => 'Roles Management', 'icon' => 'bx bxs-shield', 'url' => 'index.php?pages=roles'],
    'settings'  => ['title' => 'Site Settings', 'icon' => 'bx bxs-cog', 'url' => 'index.php?pages=settings'],
    'courses'   => ['title' => 'Courses', 'icon' => 'bx bxs-book', 'url' => 'index.php?pages=courses'],
    'tutors'    => ['title' => 'Tutors', 'icon' => 'bx bxs-chalkboard', 'url' => 'index.php?pages=tutors'],
    'students'  => ['title' => 'Students', 'icon' => 'bx bxs-graduation', 'url' => 'index.php?pages=students'],
    'payments'  => ['title' => 'Payments', 'icon' => 'bx bxs-credit-card', 'url' => 'index.php?pages=payments'],
    'create_payment_link' => ['title' => 'Create Payment Link', 'icon' => 'bx bx-link', 'url' => 'index.php?pages=payment'],
    'icons'     => ['title' => 'Icons', 'icon' => 'bx bx-image', 'url' => 'index.php?pages=icons'],
    'post'      => ['title' => 'News / Blog', 'icon' => 'bx bxs-news', 'url' => 'index.php?pages=post'],
    'comments'  => ['title' => 'Comments', 'icon' => 'bx bxs-comment-detail', 'url' => 'index.php?pages=comments'],
    'chat'      => ['title' => 'Chat Support', 'icon' => 'bx bxs-message-dots', 'url' => 'index.php?pages=chat'],
    'audit_logs' => ['title' => 'Audit Logs', 'icon' => 'bx bxs-report', 'url' => 'index.php?pages=audit_logs'],
    'appointments' => ['title' => 'Appointments', 'icon' => 'bx bx-calendar', 'url' => 'index.php?pages=appointments'],
];
