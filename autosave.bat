@echo off
:loop
git add .
git commit -m "Auto-save %date% %time%" >nul 2>&1
git push >nul 2>&1
timeout /t 20 >nul
goto loop
