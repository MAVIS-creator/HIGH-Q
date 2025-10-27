@echo off
set SQLFILE="c:\xampp\htdocs\HIGH-Q\migrations\postutme_create_only.sql"
rem detect mysql in PATH first
where mysql >nul 2>nul
if %ERRORLEVEL%==0 (
  set MYSQL_CMD=mysql
) else if exist "C:\xampp\mysql\bin\mysql.exe" (
  set MYSQL_CMD="C:\xampp\mysql\bin\mysql.exe"
) else (
  echo mysql client not found in PATH or XAMPP. Please run the migration manually.
  exit /b 1
)

rem Try to add columns (ignore failures if columns already exist)
%MYSQL_CMD% -u root -e "ALTER TABLE payments ADD COLUMN form_fee_paid TINYINT(1) DEFAULT 0;" highq 2>nul || echo "form_fee_paid may already exist or ALTER failed"
%MYSQL_CMD% -u root -e "ALTER TABLE payments ADD COLUMN tutor_fee_paid TINYINT(1) DEFAULT 0;" highq 2>nul || echo "tutor_fee_paid may already exist or ALTER failed"
%MYSQL_CMD% -u root -e "ALTER TABLE payments ADD COLUMN registration_type VARCHAR(20) DEFAULT 'regular';" highq 2>nul || echo "registration_type may already exist or ALTER failed"

nrem Create table from SQL file
%MYSQL_CMD% -u root highq < %SQLFILE% 2>nul || echo "CREATE TABLE may have failed or table already exists"

nrem Verification queries
%MYSQL_CMD% -u root -e "SHOW TABLES LIKE 'post_utme_registrations';" highq
%MYSQL_CMD% -u root -e "DESCRIBE post_utme_registrations;" highq
%MYSQL_CMD% -u root -e "SHOW COLUMNS FROM post_utme_registrations LIKE 'waec_token';" highq
%MYSQL_CMD% -u root -e "SHOW COLUMNS FROM post_utme_registrations LIKE 'waec_serial';" highq
%MYSQL_CMD% -u root -e "SHOW COLUMNS FROM post_utme_registrations LIKE 'jamb_subjects_text';" highq
%MYSQL_CMD% -u root -e "SHOW COLUMNS FROM payments LIKE 'form_fee_paid';" highq
%MYSQL_CMD% -u root -e "SHOW COLUMNS FROM payments LIKE 'registration_type';" highq

necho Migration script completed.
