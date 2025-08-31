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

  <!-- Font Awesome (for graduation cap) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

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

    body{
      margin:0;
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, var(--hq-yellow), var(--hq-yellow-light), var(--hq-red));
      display:flex;
      justify-content:center;
      padding:30px 18px;
      color:var(--hq-black);
    }

    .container{ max-width:var(--max-width); width:100%; }

    /* Header */
    .site-header{text-align:center;margin-bottom:28px;}
    .logo-circle{
      width:72px;height:72px;border-radius:50%;
      background:var(--hq-black);
      margin:0 auto 16px;
      display:flex;align-items:center;justify-content:center;
    }
    .logo-circle i{ color:var(--hq-yellow); font-size:28px; }

    .site-title{font-size:32px;font-weight:800;margin:0;}
    .site-sub{margin:4px 0 0;font-weight:600;font-size:18px;}
    .site-tagline{margin:6px 0 0;font-style:italic;color:rgba(0,0,0,0.7);}

    /* Card */
    .card{background:#fff;border-radius:var(--radius);box-shadow:0 15px 40px rgba(0,0,0,0.15);padding:28px;}
    .card-header{text-align:center;margin-bottom:20px;}
    .shield-circle{
      width:60px;height:60px;border-radius:50%;
      background:var(--hq-yellow);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;
    }
    .shield-circle i{color:var(--hq-black);font-size:20px;}
    .card-title{font-size:20px;font-weight:700;margin:0;}
    .card-desc{font-size:15px;color:rgba(0,0,0,0.7);}

    /* Features */
    .features{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:20px 0;}
    .feature{text-align:center;padding:14px;}
    .feature i{font-size:28px;margin-bottom:8px;color:var(--hq-red);}
    .feature h4{margin:0;font-size:15px;font-weight:600;}
    .feature p{margin:6px 0 0;font-size:13px;color:rgba(0,0,0,0.65);}

    /* Buttons */
    .btn-row{display:flex;flex-direction:column;gap:12px;margin-top:12px;}
    .btn{display:inline-block;padding:12px 18px;border-radius:8px;font-weight:700;text-align:center;text-decoration:none;font-size:16px;}
    .btn-primary{background:var(--hq-yellow);color:var(--hq-black);}
    .btn-outline{border:2px solid var(--hq-black);color:var(--hq-black);background:#fff;}
    .btn-outline:hover{background:var(--hq-black);color:#fff;}

    /* Role box */
    .role-box{background:var(--hq-gray);border-radius:10px;padding:12px;margin-top:18px;font-size:14px;}

    .page-footer{text-align:center;margin-top:22px;color:rgba(0,0,0,0.7);font-size:13px;}
  </style>
</head>

<body>
  <div class="container">

    <!-- Header -->
    <header class="site-header">
      <div class="logo-circle"><i class="fas fa-graduation-cap"></i></div>
      <h1 class="site-title">HIGH Q SOLID ACADEMY</h1>
      <p class="site-sub">LIMITED</p>
      <p class="site-tagline">Always Ahead of Others</p>
    </header>

    <!-- Card -->
    <section class="card">
      <div class="card-header">
        <div class="shield-circle"><i class="bx bx-shield"></i></div>
        <h2 class="card-title">Admin Panel Access</h2>
        <p class="card-desc">Manage your academy with our comprehensive admin dashboard</p>
      </div>

      <!-- Features -->
      <div class="features">
        <div class="feature">
          <i class="bx bx-group"></i>
          <h4>User Management</h4>
          <p>Manage staff and students</p>
        </div>
        <div class="feature">
          <i class="bx bx-book-open"></i>
          <h4>Content Management</h4>
          <p>Courses, news & tutorials</p>
        </div>
        <div class="feature">
          <i class="fas fa-graduation-cap"></i>
          <h4>Admissions</h4>
          <p>Student applications</p>
        </div>
      </div>

      <!-- Buttons -->
      <div class="btn-row">
        <a href="signup.php" class="btn btn-primary">Access Admin Panel</a>
        <a href="learnmore.php" class="btn btn-outline">Learn More</a>
      </div>

      <!-- Role box -->
      <div class="role-box">
        <strong>Role-Based Access Control</strong>
        <ul>
          <li><strong>Admin:</strong> Full system access and management</li>
          <li><strong>Sub-Admin:</strong> Content and user management</li>
          <li><strong>Moderator:</strong> Comment and content moderation</li>
        </ul>
      </div>
    </section>

    <!-- Footer -->
    <footer class="page-footer">
      <p>© 2024 HIGH Q SOLID ACADEMY LIMITED. All rights reserved.</p>
      <p>Empowering students with quality education since 2018</p>
    </footer>
  </div>
</body>
</html>
