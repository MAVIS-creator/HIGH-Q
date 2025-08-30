admin/
 ├─ login.php
 ├─ logout.php
 ├─ pages/
 ├  ├─ index.php           -- dashboard (role-aware)
 ├  ├─ users.php           -- list/create/edit (admins only)
 ├  ├─ roles.php           -- view (admins only)
 ├  ├─ courses.php         -- manage courses
 ├  ├─ posts.php           -- list posts, draft/publish controls
 ├  ├─ post_edit.php       -- WYSIWYG editor, save drafts
 ├  ├─ comments.php        -- moderate comments + reply inline
 ├  ├─ tutors.php          -- manage tutors
 ├  ├─ chat.php            -- live admin chat interface (threads)
 ├  ├─ students.php        -- registrations
 ├─ payments.php
 ├─ settings.php
 ├─ uploads/            -- store uploaded files
 └─ includes/
     ├─ db.php
     ├─ auth.php        -- login helpers, session + role checks
     ├─ csrf.php
     ├─ header.php
     └─ footer.php
├─ .env                     # Environment variables (DB creds, API keys, etc.)
├─ .gitignore               # Git ignore rules
├─ composer.json            # Composer dependencies
├─ composer.lock
├─ .htaccess                # Rewrite rules, security headers, etc.
│
├─ public/                  # Publicly accessible files
│   ├─ index.php            # Entry point / router
│   ├─ about.php            # Static about page
│   ├─ tutors.php           # Tutors listing
│   ├─ tutor_profile.php    # Individual tutor profile
│   ├─ programs.php         # Programs listing
│   ├─ post.php             # Blog/article/post single page
│   ├─ register.php         # User registration
│   │
│   └─ assets/              # Frontend assets
│       ├─ css/
│       ├─ js/
│       └─ images/
│
├─ config/                  # App-wide config files
│   ├─ db.php               # Database connection (PDO or mysqli)
│   └─ app.php              # General settings (site name, debug, timezone)
│
├─ src/                     # Core PHP classes & logic
│   ├─ Security/
│   │   ├─ csrf.php         # CSRF token handler
│   │   └─ auth.php         # Authentication logic
│   │
│   ├─ Controllers/         # Page controllers
│   ├─ Models/              # DB models (User, Tutor, Post, Program, etc.)
│   └─ Helpers/             # Utility functions (validation, upload, etc.)
│
├─ views/                   # HTML/PHP templates
│   ├─ layouts/             # Main layout, headers, footers
│   ├─ partials/            # Navbars, sidebars, etc.
│   ├─ pages/               # Page templates (about, tutors, programs…)
│   └─ components/          # Reusable bits (cards, modals, forms…)
│
└─ vendor/                  # Composer dependencies


