@echo off
echo Starting Paxyo Order Checker (every 10 seconds)...
echo Press Ctrl+C to stop
echo.

:loop
d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php
timeout /t 10 /nobreak > nul
goto loop
