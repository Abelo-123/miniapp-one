@echo off
:loop
d:\next\xampp\php\php.exe d:\next\xampp\htdocs\paxyo\cron_check_orders.php
timeout /t 60 /nobreak
goto loop