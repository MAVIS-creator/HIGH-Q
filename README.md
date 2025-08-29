admin/
 ├─ login.php
 ├─ logout.php
 ├─ index.php           -- dashboard (role-aware)
 ├─ users.php           -- list/create/edit (admins only)
 ├─ roles.php           -- view (admins only)
 ├─ courses.php         -- manage courses
 ├─ posts.php           -- list posts, draft/publish controls
 ├─ post_edit.php       -- WYSIWYG editor, save drafts
 ├─ comments.php        -- moderate comments + reply inline
 ├─ tutors.php          -- manage tutors
 ├─ chat.php            -- live admin chat interface (threads)
 ├─ students.php        -- registrations
 ├─ payments.php
 ├─ settings.php
 ├─ uploads/            -- store uploaded files
 └─ includes/
     ├─ db.php
     ├─ auth.php        -- login helpers, session + role checks
     ├─ csrf.php
     ├─ header.php
     └─ footer.php
public/
 ├─ index.php
 ├─ about.php
 ├─ tutors.php
 ├─ tutor_profile.php
 ├─ programs.php
 ├─ post.php
 ├─ register.php
 └─ assets/
