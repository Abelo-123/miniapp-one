<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include 'db.php';

$user_id = 111; // Hardcoded default from process_order.php

echo "Checking user $user_id...\n";
$user = db('select', 'auth', 'tg_id', $user_id);

if ($user) {
    echo "User found:\n";
    print_r($user);
    
    // Test Update
    $current_balance = floatval($user['balance']);
    $new_balance = $current_balance - 0; // Just update same value to see if query works
    
    echo "Attempting update...\n";
    $result = db('update', 'auth', 'tg_id', $user_id, ['balance' => $current_balance]);
    
    if ($result) {
        echo "Update reported success.\n";
    } else {
        echo "Update FAILED: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "User $user_id NOT found in 'auth' table.\n";
    
    // List all users to see what's there
    $users = db_query("SELECT * FROM auth LIMIT 5");
    echo "First 5 users in db:\n";
    print_r($users);
}
?>
