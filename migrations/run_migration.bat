@echo off
set SQL="c:\xampp\htdocs\HIGH-Q\migrations\2025-10-26-create-postutme-and-payments-columns_mysql.sql"
where mysql >nul 2>nul
if %ERRORLEVEL%==0 (
  mysql -u root highq < %SQL%
  mysql -u root -e "SHOW TABLES LIKE 'post_utme_registrations'; DESCRIBE post_utme_registrations; SHOW COLUMNS FROM post_utme_registrations LIKE 'waec_token'; SHOW COLUMNS FROM post_utme_registrations LIKE 'waec_serial'; SHOW COLUMNS FROM post_utme_registrations LIKE 'jamb_subjects_text'; SHOW COLUMNS FROM payments LIKE 'form_fee_paid'; SHOW COLUMNS FROM payments LIKE 'registration_type';"
) else if exist "C:\xampp\mysql\bin\mysql.exe" (
  "C:\xampp\mysql\bin\mysql.exe" -u root highq < %SQL%
  "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW TABLES LIKE 'post_utme_registrations'; DESCRIBE post_utme_registrations; SHOW COLUMNS FROM post_utme_registrations LIKE 'waec_token'; SHOW COLUMNS FROM post_utme_registrations LIKE 'waec_serial'; SHOW COLUMNS FROM post_utme_registrations LIKE 'jamb_subjects_text'; SHOW COLUMNS FROM payments LIKE 'form_fee_paid'; SHOW COLUMNS FROM payments LIKE 'registration_type';"
) else (
  echo mysql client not found in PATH or XAMPP. Please run: mysql -u root -p YOUR_DB_NAME ^< "c:\xampp\htdocs\HIGH-Q\migrations\2025-10-26-create-postutme-and-payments-columns_mysql.sql"
)
