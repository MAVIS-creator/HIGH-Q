<?php
// admin/includes/sidebar.php
// Determine which “page” is currently loaded
$current = $_GET['page'] ?? 'dashboard';
?>

<aside class="admin-sidebar">
  <div class="sidebar-logo">
    <img src="../public/assets/images/logo.png" alt="Academy Logo">
    <h3>HIGH Q SOLID ACADEMY</h3>
    <small><?= htmlspecialchars($_SESSION['user']['role_name']); ?></small>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <!-- Dashboard: all roles -->
      <li>
        <a 
          href="index.php?page=dashboard" 
          class="<?= $current === 'dashboard' ? 'active' : '' ?>"
        >
          <i class='bx bxs-dashboard'></i> Dashboard
        </a>
      </li>

      <!-- Admin only -->
      <?php if ($_SESSION['user']['role_slug'] === 'admin'): ?>
        <li>
          <a 
            href="index.php?page=users" 
            class="<?= $current === 'users' ? 'active' : '' ?>"
          >
            <i class='bx bxs-user-detail'></i> Manage Users
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=roles" 
            class="<?= $current === 'roles' ? 'active' : '' ?>"
          >
            <i class='bx bxs-shield'></i> Roles
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=settings" 
            class="<?= $current === 'settings' ? 'active' : '' ?>"
          >
            <i class='bx bxs-cog'></i> Site Settings
          </a>
        </li>
      <?php endif; ?>

      <!-- Admin + Sub-Admin -->
      <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin'])): ?>
        <li>
          <a 
            href="index.php?page=courses" 
            class="<?= $current === 'courses' ? 'active' : '' ?>"
          >
            <i class='bx bxs-book'></i> Courses
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=tutors" 
            class="<?= $current === 'tutors' ? 'active' : '' ?>"
          >
            <i class='bx bxs-chalkboard'></i> Tutors
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=students" 
            class="<?= $current === 'students' ? 'active' : '' ?>"
          >
            <i class='bx bxs-graduation'></i> Students
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=payments" 
            class="<?= $current === 'payments' ? 'active' : '' ?>"
          >
            <i class='bx bxs-credit-card'></i> Payments
          </a>
        </li>
      <?php endif; ?>

      <!-- Admin + Sub-Admin + Moderator -->
      <?php if (in_array($_SESSION['user']['role_slug'], ['admin','sub-admin','moderator'])): ?>
        <li>
          <a 
            href="index.php?page=posts" 
            class="<?= $current === 'posts' ? 'active' : '' ?>"
          >
            <i class='bx bxs-news'></i> News / Blog
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=comments" 
            class="<?= $current === 'comments' ? 'active' : '' ?>"
          >
            <i class='bx bxs-comment-detail'></i> Comments
          </a>
        </li>
        <li>
          <a 
            href="index.php?page=chat" 
            class="<?= $current === 'chat' ? 'active' : '' ?>"
          >
            <i class='bx bxs-message-dots'></i> Chat Support
          </a>
        </li>
      <?php endif; ?>

      <!-- Logout -->
      <li>
        <a href="../logout.php" class="logout-link">
          <i class='bx bx-log-out'></i> Logout
        </a>
      </li>
    </ul>
  </nav>
</aside>
