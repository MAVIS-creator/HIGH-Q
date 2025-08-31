<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIGH Q SOLID ACADEMY LIMITED</title>
    <style>
        /* ====== Reset & Fonts ====== */
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #FFD600, #FF8C00, #FF2E00);
            color: #111;
        }

        /* ====== Container ====== */
        .card {
            background: #fff;
            max-width: 600px;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .logo {
            background: black;
            color: yellow;
            font-size: 1.8rem;
            padding: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 15px;
        }

        h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 800;
            color: black;
        }

        h2 {
            font-size: 1rem;
            margin: 5px 0 15px;
            font-weight: normal;
            font-style: italic;
            color: #444;
        }

        /* ====== Sections ====== */
        .features {
            display: flex;
            justify-content: space-around;
            margin: 1.5rem 0;
        }

        .features div {
            flex: 1;
            margin: 0 10px;
        }

        .features i {
            font-size: 2rem;
            color: red;
            margin-bottom: 5px;
            display: block;
        }

        .features p {
            font-size: 0.9rem;
            color: #333;
        }

        /* ====== Buttons ====== */
        .btn-group {
            margin: 20px 0;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            margin: 0 5px;
            transition: 0.3s ease;
        }

        .btn-primary {
            background: #FFD600;
            color: #000;
        }

        .btn-primary:hover {
            background: #FFB300;
        }

        .btn-outline {
            background: #fff;
            border: 2px solid black;
            color: black;
        }

        .btn-outline:hover {
            background: black;
            color: white;
        }

        /* ====== Role Info ====== */
        .roles {
            margin-top: 15px;
            text-align: left;
            font-size: 0.9rem;
            line-height: 1.5;
            background: #f7f7f7;
            padding: 10px;
            border-radius: 6px;
        }

        /* ====== Footer ====== */
        footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.75rem;
            color: #333;
        }
    </style>
    <!-- Icons (Font Awesome CDN) -->
    <script src="https://kit.fontawesome.com/yourkitid.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="card">
        <div class="logo"><i class="fas fa-graduation-cap"></i></div>
        <h1>HIGH Q SOLID ACADEMY</h1>
        <h2>LIMITED<br>Always Ahead of Others</h2>

        <h3>Admin Panel Access</h3>
        <p>Manage your academy with our comprehensive admin dashboard</p>

        <div class="features">
            <div>
                <i class="fas fa-users"></i>
                <strong>User Management</strong>
                <p>Manage staff and students</p>
            </div>
            <div>
                <i class="fas fa-book"></i>
                <strong>Content Management</strong>
                <p>Courses, news & tutorials</p>
            </div>
            <div>
                <i class="fas fa-user-graduate"></i>
                <strong>Admissions</strong>
                <p>Student applications</p>
            </div>
        </div>

        <div class="btn-group">
            <a href="signup.php" class="btn btn-primary">Access Admin Panel</a>
            <a href="learnmore.php" class="btn btn-outline">Learn More</a>
        </div>

        <div class="roles">
            <strong>Role-Based Access Control</strong>
            <ul>
                <li><b>Admin:</b> Full system access and management</li>
                <li><b>Sub-Admin:</b> Content and user management</li>
                <li><b>Moderator:</b> Comment and content moderation</li>
            </ul>
        </div>

        <footer>
            © 2024 HIGH Q SOLID ACADEMY LIMITED. All rights reserved.<br>
            Empowering students with quality education since 2018
        </footer>
    </div>
</body>

</html>