# Google OAuth Setup Guide for Paxyo

This guide will help you set up Google Sign-In for your Paxyo SMM panel.

## Prerequisites

- Access to [Google Cloud Console](https://console.cloud.google.com/)
- A Google account
- Your production domain (e.g., `paxyo.com`)

## Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click on the project dropdown at the top
3. Click **New Project**
4. Enter project name: `Paxyo SMM`
5. Click **Create**

## Step 2: Configure OAuth Consent Screen

1. In the left sidebar, go to **APIs & Services** → **OAuth consent screen**
2. Select **External** user type (unless you have Google Workspace)
3. Click **Create**
4. Fill in the required fields:
   - **App name**: `Paxyo SMM`
   - **User support email**: Your email
   - **Developer contact email**: Your email
5. Click **Save and Continue**
6. On the **Scopes** page, click **Add or Remove Scopes**
7. Add these scopes:
   - `openid`
   - `email`
   - `profile`
8. Click **Save and Continue**
9. On the **Test users** page, add your email for testing
10. Click **Save and Continue**

## Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth 2.0 Client ID**
3. Select **Web application** as the application type
4. Name: `Paxyo Web Client`
5. Add **Authorized JavaScript origins**:
   - `https://paxyo.com`
   - `https://www.paxyo.com`
   - `http://localhost` (for testing)
6. Add **Authorized redirect URIs**:
   - `https://paxyo.com/google_auth.php`
   - `https://www.paxyo.com/google_auth.php`
   - `http://localhost/paxyo/google_auth.php` (for testing)
7. Click **Create**
8. Copy the **Client ID** and **Client Secret**

## Step 4: Update Configuration

Open `config_google.php` and update:

```php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'https://paxyo.com/google_auth.php');
```

## Step 5: Run Database Migration

Visit this URL once to add required database columns:

```
https://paxyo.com/migrate_google_auth.php
```

Or run it locally:
```
http://localhost/paxyo/migrate_google_auth.php
```

## Step 6: Test the Integration

1. Go to your login page: `https://paxyo.com/login.php`
2. Click "Continue with Google"
3. Sign in with your Google account
4. You should be redirected to the SMM dashboard

## Files Created

| File | Purpose |
|------|---------|
| `config_google.php` | Google OAuth credentials and settings |
| `google_auth.php` | OAuth flow handler (redirect, callback) |
| `login.php` | Login page with Google Sign-In button |
| `logout.php` | Session cleanup and logout |
| `migrate_google_auth.php` | Database migration script |

## Database Changes

The migration adds these columns to the `auth` table:

| Column | Type | Purpose |
|--------|------|---------|
| `google_id` | VARCHAR(255) | User's Google account ID |
| `email` | VARCHAR(255) | User's email address |
| `auth_provider` | VARCHAR(50) | 'telegram' or 'google' |

## How It Works

### For Web Users (Google Auth):
1. User visits `login.php`lighrne
2. Clicks "Continue with Google"
3. Redirected to Google's OAuth consent screen
4. After approval, redirected back to `google_auth.php`
5. User profile is created/updated in database
6. Session is established
7. Redirected to `smm.php`

### For Telegram Users:
1. User opens Mini App from Telegram
2. `smm.php` detects Telegram environment
3. JavaScript validates `initData` via `telegram_auth.php`
4. Session is established via API call
5. App loads normally

## Security Notes

1. **Never commit credentials**: Add `config_google.php` to `.gitignore`
2. **Use HTTPS**: Google OAuth requires HTTPS in production
3. **Verify redirect URIs**: Only add URIs you control
4. **State tokens**: CSRF protection is built-in via state parameter

## Troubleshooting

### "Invalid state token" error
- Clear browser cookies and try again
- Make sure sessions are enabled on your server

### "Failed to get access token" error
- Check that Client ID and Secret are correct
- Verify redirect URI matches exactly (including trailing slashes)

### Users not seeing login page
- Make sure you're accessing via web browser, not Telegram
- Clear cookies and session data

### "redirect_uri_mismatch" error
- The redirect URI in your code must exactly match one in Google Console
- Check for http vs https, www vs non-www, trailing slashes

## Publishing Your App

When ready for production:

1. Go to OAuth consent screen
2. Click **Publish App**
3. Complete any verification requirements

For apps with 100+ users, you may need to verify your domain and submit for review.

---

## Quick Test Checklist

- [ ] Google Cloud project created
- [ ] OAuth consent screen configured
- [ ] OAuth 2.0 credentials created
- [ ] `config_google.php` updated with credentials
- [ ] Database migration run
- [ ] Login page accessible
- [ ] Google Sign-In working
- [ ] User redirected to dashboard after login
- [ ] Logout working
- [ ] Telegram Mini App still works correctly
