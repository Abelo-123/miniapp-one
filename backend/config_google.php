<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Google OAuth Configuration
 * SECURITY: This file contains sensitive credentials - add to .gitignore
 * 
 * Setup Instructions:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select existing one
 * 3. Go to APIs & Services > Credentials
 * 4. Click "Create Credentials" > "OAuth 2.0 Client ID"
 * 5. Configure the OAuth consent screen first if prompted
 * 6. Choose "Web application" as application type
 * 7. Add authorized redirect URI: https://yourdomain.com/google_auth.php
 * 8. Copy the Client ID and Client Secret below
 */

// Google OAuth 2.0 Credentials
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');

// Redirect URI - Update this to your production URL
define('GOOGLE_REDIRECT_URI', 'https://paxyo.com/google_auth.php');

// OAuth Endpoints
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

// Scopes for Google OAuth
define('GOOGLE_SCOPES', 'openid email profile');
?>
