<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HIGH Q SOLID ACADEMY — Admin Access</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">

  <!-- Boxicons -->
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    :root{
      --hq-yellow: #FFD600;
      --hq-yellow-light: #FFE566;
      --hq-red: #FF4B2B;
      --hq-black: #0A0A0A;
      --hq-gray: #F3F4F6;
      --max-width: 960px;
      --card-width: 760px;
      --radius: 12px;
    }

    *{box-sizing: border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: linear-gradient(135deg, var(--hq-yellow) 0%, var(--hq-yellow-light) 40%, #FF9A4D 65%, var(--hq-red) 100%);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      color:var(--hq-black);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:40px 18px;
    }

    .container{
      width:100%;
      max-width:var(--max-width);
      margin:0 auto;
      display:block;
    }

    /* Header (title area) */
    .site-header{
      text-align:center;
      margin-bottom:28px;
    }
    .logo-circle{
      width:96px;height:96px;border-radius:999px;
      background:var(--hq-black);
      margin:0 auto 16px;
      display:flex;align-items:center;justify-content:center;
      box-shadow:0 6px 18px rgba(0,0,0,0.18);
    }
    .logo-circle i{ color:var(--hq-yellow); font-size:34px; }

    .site-title{
      margin:0;font-weight:800;font-size:34px;letter-spacing:0.6px;
    }
    .site-sub{
      margin:6px 0 0;font-weight:600;font-size:18px;opacity:0.9;
    }
    .site-tagline{ margin:6px 0 0;font-style:italic;color:rgba(0,0,0,0.65);font-weight:500 }

    /* Card */
    .card-wrap{ display:flex; justify-content:center; }
    .card{
      width:100%;
      max-width:var(--card-width);
      background:#ffffff;
      border-radius:var(--radius);
      box-shadow: 0 20px 60px rgba(10,10,10,0.18);
      padding:28px;
      position:relative;
    }

    /* Card header shield */
    .card-header{
      text-align:center;
      margin-top:4px;
      margin-bottom:6px;
    }
    .shield-circle{
      width:64px;height:64px;border-radius:50%;
      background:var(--hq-yellow);
      display:flex;align-items:center;justify-content:center;margin:0 auto 10px;
      box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    }
    .shield-circle i{ color:var(--hq-black); font-size:22px; }

    .card-title{ font-size:20px; font-weight:700; margin:6px 0; }
    .card-desc{ font-size:15px; color:rgba(0,0,0,0.7); margin-bottom:18px; }

    /* Features grid */
    .features{
      display:grid;
      grid-template-columns: repeat(1, 1fr);
      gap:12px;
      margin: 14px 0 20px;
      text-align:center;
    }
    .feature{
      padding:14px 8px;
    }
    .feature i{ font-size:28px; display:block; margin:0 auto 8px; }
    .feature h4{ margin:0;font-weight:600;font-size:15px; }
    .feature p{ margin:6px 0 0; font-size:13px; color:rgba(0,0,0,0.65) }

    /* Buttons row */
    .btn-row{
      display:flex;
      gap:12px;
      margin-top:12px;
      align-items:center;
      flex-direction:column;
    }
    .btn {
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:12px 18px;
      border-radius:8px;
      font-weight:700;
      text-decoration:none;
      cursor:pointer;
      min-width:200px;
      transition:all .18s ease;
      border:2px solid transparent;
      font-size:16px;
    }
    .btn-primary{
      background:var(--hq-yellow);
      color:var(--hq-black);
      border-color:var(--hq-yellow);
    }
    .btn-primary:hover{ transform:translateY(-2px); box-shadow:0 8px 22px rgba(255,166,0,0.14) }

    .btn-outline{
      background:#fff;
      color:var(--hq-black);
      border:2px solid var(--hq-black);
    }
    .btn-outline:hover{
      background:var(--hq-black);
      color:#fff;
      transform:translateY(-2px);
    }

    /* Role box */
    .role-box{
      background: rgba(243,244,246,0.7);
      border-radius:10px;
      padding:12px;
      margin-top:18px;
      font-size:14px;
      color:rgba(0,0,0,0.8);
    }
    .role-box strong{ display:block; margin-bottom:8px; font-weight:700; }

    /* Footer */
    .page-footer{
      text-align:center;
      margin-top:22px;
      color:rgba(0,0,0,0.7);
      font-size:13px;
    }

    /* Responsive */
    @media(min-width:720px){
      .features{ grid-template-columns: repeat(3, 1fr); text-align:center; }
      .btn-row{ flex-direction:row; justify-content:center; }
      .card{ padding:36px; }
      .site-title{ font-size:40px; }
    }

    /* small tweak for icon colors */
    .feat-icon-red{ color: var(--hq-red); }
    .feat-icon-yellow{ color: var(--hq-yellow); }
  </style>
</head>
<body>
  <div class="container">

    <!-- Header -->
    <header class="site-header" role="banner">
      <div class="logo-circle" aria-hidden="true">
        <i class="bx bx-graduation"></i>
      </div>
      <h1 class="site-title">HIGH Q SOLID ACADEMY</h1>
      <p class="site-sub">LIMITED</p>
      <p class="site-tagline">Always Ahead of Others</p>
    </header>

    <!-- Card -->
    <div class="card-wrap">
      <section class="card" aria-labelledby="admin-title">
        <div class="card-header">
          <div class="shield-circle" aria-hidden="true">
            <i class='bx bx-shield'></i>
          </div>
          <h2 id="admin-title" class="card-title">Admin Panel Access</h2>
          <p class="card-desc">Manage your academy with our comprehensive admin dashboard</p>
        </div>

        <!-- Features -->
        <div class="features" role="list">
          <div class="feature" role="listitem">
            <i class="bx bx-group feat-icon-red" aria-hidden="true"></i>
            <h4>User Management</h4>
            <p>Manage staff and students</p>
          </div>

          <div class="feature" role="listitem">
            <i class="bx bx-book-open feat-icon-red" aria-hidden="true"></i>
            <h4>Content Management</h4>
            <p>Courses, news &amp; tutorials</p>
          </div>

          <div class="feature" role="listitem">
            <i class='bxr  bx-education'  ></i> 
            <h4>Admissions</h4>
            <p>Student applications</p>
          </div>
        </div>

        <!-- Buttons -->
        <div class="btn-row" aria-hidden="false">
          <!-- ACTUAL LINK to signup.php -->
          <a href="signup.php" class="btn btn-primary" aria-label="Access Admin Panel (signup)">
            Access Admin Panel
          </a>

          <!-- Learn more - replace link as needed -->
          <a href="learnmore.php" class="btn btn-outline" aria-label="Learn more about admin panel">
            Learn More
          </a>
        </div>

        <!-- Role box -->
        <div class="role-box" aria-live="polite">
          <strong>Role-Based Access Control</strong>
          <ul style="margin:0;padding-left:18px">
            <li><strong>Admin:</strong> Full system access and management</li>
            <li><strong>Sub-Admin:</strong> Content and user management</li>
            <li><strong>Moderator:</strong> Comment and content moderation</li>
          </ul>
        </div>
      </section>
    </div>

    <!-- Footer -->
    <footer class="page-footer" role="contentinfo">
      <p>© 2024 HIGH Q SOLID ACADEMY LIMITED. All rights reserved.</p>
      <p style="margin-top:6px;font-size:12px">Empowering students with quality education since 2018</p>
    </footer>
  </div>
</body>
</html>
