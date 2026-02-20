<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Logout Handler
 * Clears session and cookies for both Telegram and Google auth
 */

session_set_cookie_params(['samesite' => 'Lax', 'secure' => true]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Clear the ID cookie
setcookie("id", "", time() - 3600, "/", "", true, true);
setcookie("id", "", time() - 3600, "/", "paxyo.com", true, true);

// Redirect to home page
header('Location: index.php');
exit;
?>
