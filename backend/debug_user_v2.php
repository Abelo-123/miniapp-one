<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

$user_id = 111; 

echo "Checking user $user_id...\n";
$user = db('select', 'auth', 'tg_id', $user_id);

if ($user) {
    echo "Current Balance: " . $user['balance'] . "\n";
    
    // Attempt Update
    $new_balance = floatval($user['balance']) + 0.0001; 
    echo "Updating to: $new_balance\n";
    
    $result = db('update', 'auth', 'tg_id', $user_id, ['balance' => $new_balance]);
    
    if ($result) {
        echo "Update returned TRUE.\n";
        
        // Verify
        $user_after = db('select', 'auth', 'tg_id', $user_id);
        echo "Balance after update: " . $user_after['balance'] . "\n";
    } else {
        echo "Update returned FALSE.\n";
        echo "MySQL Error: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "User 111 not found.\n";
}
?>
