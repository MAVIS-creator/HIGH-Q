@echo off
REM ============================================================================
REM Windows Task Scheduler Setup for Security Scan Scheduler
REM ============================================================================
REM 
REM This script creates a Windows Task Scheduler job that runs the security 
REM scan scheduler automatically every day at 2:00 AM.
REM
REM Requirements:
REM - Windows 7 or later
REM - Administrator privileges
REM - PHP installed (typically in C:\xampp\php\php.exe)
REM
REM Usage: Run this script as Administrator
REM        Right-click → "Run as administrator"
REM

echo.
echo ============================================================
echo Windows Task Scheduler Setup - Security Scan Scheduler
echo ============================================================
echo.

REM Check for admin privileges
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script requires Administrator privileges!
    echo.
    echo Please:
    echo 1. Right-click this file (setup-task-scheduler.bat)
    echo 2. Select "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo [1/5] Checking PHP installation...
set PHP_PATH=C:\xampp\php\php.exe

if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo.
    echo Please update the PHP_PATH variable in this script to match your installation.
    echo.
    pause
    exit /b 1
)

echo       Found PHP at: %PHP_PATH%
echo.

echo [2/5] Checking scan-scheduler.php...
set SCHEDULER_PATH=C:\xampp\htdocs\HIGH-Q\bin\scan-scheduler.php

if not exist "%SCHEDULER_PATH%" (
    echo ERROR: scan-scheduler.php not found at %SCHEDULER_PATH%
    echo.
    echo Please update the SCHEDULER_PATH variable in this script to match your installation.
    echo.
    pause
    exit /b 1
)

echo       Found scheduler at: %SCHEDULER_PATH%
echo.

echo [3/5] Creating scheduled task...
echo       Task Name: HIGH-Q Security Scan
echo       Schedule: Daily at 2:00 AM
echo       Action: Run PHP scanner
echo.

REM Delete existing task if it exists
schtasks /delete /tn "HIGH-Q Security Scan" /f >nul 2>&1

REM Create the new task
schtasks /create /tn "HIGH-Q Security Scan" ^
    /tr "\"%PHP_PATH%\" \"%SCHEDULER_PATH%\"" ^
    /sc daily ^
    /st 02:00 ^
    /f

if %errorLevel% neq 0 (
    echo ERROR: Failed to create task!
    echo.
    pause
    exit /b 1
)

echo       ✓ Task created successfully!
echo.

echo [4/5] Verifying task...
schtasks /query /tn "HIGH-Q Security Scan" >nul 2>&1

if %errorLevel% neq 0 (
    echo ERROR: Task verification failed!
    echo.
    pause
    exit /b 1
)

echo       ✓ Task verified!
echo.

echo [5/5] Testing scanner...
echo       Running manual test of scan-scheduler.php...
echo.

"%PHP_PATH%" "%SCHEDULER_PATH%"

echo.
echo ============================================================
echo Setup Complete!
echo ============================================================
echo.
echo The security scan scheduler is now configured to run:
echo   • Every day at 2:00 AM
echo   • Automatically scans your system
echo   • Sends email reports on critical findings
echo.
echo Task Details:
echo   Name: HIGH-Q Security Scan
echo   Schedule: Daily 02:00
echo   Command: %PHP_PATH% %SCHEDULER_PATH%
echo.
echo Management:
echo   • View tasks: Open Task Scheduler (search in Start menu)
echo   • Disable: Right-click task → Disable
echo   • Enable: Right-click task → Enable
echo   • Delete: Right-click task → Delete
echo   • Modify: Right-click task → Properties → Triggers/Actions
echo.
echo Logs:
echo   • Check: C:\xampp\htdocs\HIGH-Q\storage\logs\php-error.log
echo.
echo Next Steps:
echo   1. Verify task appears in Task Scheduler
echo   2. Check logs directory for confirmation
echo   3. Configure scan settings in Admin panel if needed
echo.
pause
