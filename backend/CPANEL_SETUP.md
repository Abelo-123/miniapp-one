# cPanel Cron Setup Guide (Simpler Method)

Since setting up 6 separate cron jobs can be tedious or confusing in some cPanel interfaces, here is a **Simpler "One-File" Method**.

## 1. Upload the Loop Script
Ensure you have uploaded the file **`cron_10s_loop.php`** to your `public_html/paxyo` folder (or where your site is).
*This file was validly created in your project.*

## 2. Set Up ONE Cron Job
1. Log in to **cPanel** and go to **Cron Jobs**.
2. **Standard Setup**:
   - **Common Settings**: Select `Once Per Minute (* * * * *)`.
   - **Command**: enter the command below:
     ```bash
     /usr/local/bin/php /home/YOUR_USERNAME/public_html/paxyo/cron_10s_loop.php
     ```
     *(Replace `YOUR_USERNAME` and path accordingly. If your site is in the root, remove `/paxyo`)*

3. Click **Add New Cron Job**.

## 3. Done!
You now have a 10-second update cycle running from a single cron entry.

---

### Troubleshooting
- **Verify Path**: Use cPanel File Manager to copy the exact path to `cron_10s_loop.php`.
- **PHP Path**: If `/usr/local/bin/php` doesn't work, try simply `php` or look for the "General Example" usually shown at the top of the Cron Jobs page in cPanel.
- **Permissions**: Ensure `cron_10s_loop.php` has permissions `644` or `755`.
