<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
/**
 * Login Page - For website users (non-Telegram)
 * Provides Google Sign-In option for web access
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['samesite' => 'None', 'secure' => true]);
    session_start();
}

require_once 'db.php';

// If already logged in, redirect to app
if (isset($_SESSION['tg_id']) && $_SESSION['tg_id']) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0f">
    <title>Login - Paxyo SMM</title>
    <meta name="description" content="Sign in to Paxyo SMM Panel - Grow your social media presence with our powerful engagement tools.">
    
    <!-- Preconnect for faster loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #1a1a24;
            --bg-card: #12121a;
            --accent: #6c5ce7;
            --accent-light: #a29bfe;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --border: #2d2d3a;
            --success: #00d26a;
            --google-blue: #4285f4;
            --google-red: #ea4335;
        }
        
        html, body {
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        body {
            background: var(--bg-primary);
            background-image: 
                radial-gradient(ellipse 80% 100% at 50% -20%, rgba(108, 92, 231, 0.15), transparent),
                radial-gradient(ellipse 60% 60% at 100% 100%, rgba(108, 92, 231, 0.1), transparent);
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 
                0 20px 40px rgba(108, 92, 231, 0.3),
                0 0 60px rgba(108, 92, 231, 0.15);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .logo svg {
            width: 44px;
            height: 44px;
            fill: white;
        }
        
        .brand-name {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #fff, #a0aec0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .brand-tagline {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 8px;
        }
        
        /* Login Card */
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }
        
        .login-title {
            font-size: 20px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            text-align: center;
            margin-bottom: 28px;
        }
        
        /* Google Button */
        .google-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: white;
            color: #1f1f1f;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .google-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        
        .google-btn:active {
            transform: translateY(0);
        }
        
        .google-btn svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        
        .divider span {
            padding: 0 16px;
        }
        
        /* Telegram Button */
        .telegram-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, #0088cc, #00a8e8);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
        }
        
        .telegram-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 136, 204, 0.4);
        }
        
        .telegram-btn:active {
            transform: translateY(0);
        }
        
        .telegram-btn svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
        
        /* Info Box */
        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: rgba(108, 92, 231, 0.1);
            border: 1px solid rgba(108, 92, 231, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-top: 24px;
        }
        
        .info-box svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            color: var(--accent);
        }
        
        .info-box p {
            color: var(--text-secondary);
            font-size: 13px;
            line-height: 1.5;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 32px;
            color: var(--text-secondary);
            font-size: 12px;
        }
        
        .login-footer a {
            color: var(--accent);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .login-footer a:hover {
            color: var(--accent-light);
        }
        
        /* Features */
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 32px;
        }
        
        .feature {
            text-align: center;
            padding: 16px 8px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .feature:hover {
            background: rgba(108, 92, 231, 0.05);
            border-color: rgba(108, 92, 231, 0.3);
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, rgba(108, 92, 231, 0.2), rgba(162, 155, 254, 0.1));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            color: var(--accent);
        }
        
        .feature-icon svg {
            width: 20px;
            height: 20px;
        }
        
        .feature-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 24px;
            }
            
            .logo {
                width: 64px;
                height: 64px;
            }
            
            .brand-name {
                font-size: 28px;
            }
            
            .features {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .feature {
                display: flex;
                align-items: center;
                gap: 12px;
                text-align: left;
                padding: 12px 16px;
            }
            
            .feature-icon {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="brand-name">Paxyo</div>
            <div class="brand-tagline">Grow Your Social Presence</div>
        </div>
        
        <!-- Login Card -->
        <div class="login-card">
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to access your SMM dashboard</p>
            
            <!-- Google Sign In -->
            <a href="google_auth.php?action=login" class="google-btn">
                <svg viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Continue with Google
            </a>
            
            <div class="divider"><span>or</span></div>
            
            <!-- Telegram Bot -->
            <a href="https://t.me/PaxyoBot" target="_blank" class="telegram-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
                Open Telegram Bot
            </a>
            
            <!-- Info Box -->
            <div class="info-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>For the best experience, use our Telegram Mini App. Web login via Google is available for convenience.</p>
            </div>
        </div>
        
        <!-- Features -->
        <div class="features">
            <div class="feature">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="feature-title">Fast Delivery</div>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="feature-title">Secure</div>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="feature-title">24/7 Support</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="login-footer">
            <p>By signing in, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></p>
        </div>
    </div>
</body>
</html>
