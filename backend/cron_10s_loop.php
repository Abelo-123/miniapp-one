<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// cron_10s_loop.php
// Run this script ONCE PER MINUTE in cPanel cron
// It will execute the worker script every 10 seconds

$worker_script = __DIR__ . '/cron_check_orders.php';
$bot_script = __DIR__ . '/cron_bot_tasks.php';
$php_binary = 'php'; 

// Run bot tasks once per minute
shell_exec("$php_binary \"$bot_script\" > /dev/null 2>&1");

set_time_limit(70); 

for ($i = 0; $i < 6; $i++) {
    // Run the worker script
    // Using shell_exec to ensure a fresh environment for each run
    shell_exec("$php_binary \"$worker_script\" > /dev/null 2>&1");
    
    // Wait 10 seconds (except after the last run)
    if ($i < 5) {
        sleep(10);
    }
}
?>
