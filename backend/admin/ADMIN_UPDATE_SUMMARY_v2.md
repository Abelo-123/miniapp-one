# Admin Features Update 2.0

## 1. Real-time Dashboard Updates ⚡
- **Auto-Sync**: The admin dashboard now automatically refreshes data every 10 seconds.
- **No Refresh Button Needed**: Orders list and Dashboard statistics stay up-to-date without manual intervention.
- **Quiet Mode**: Updates happen silently in the background without UI flickering or loading spinners.

## 2. Advanced Service Management 🛠️
- **New Filters**: added dedicated filter buttons in the Services tab:
  - **All**: View all services.
  - **Recommended**: View only services marked as "Top".
  - **Hidden**: View only services hidden from users.
- **Easy Management**: Quickly toggle "Hide/Unhide" or "Add/Remove Rec" directly from these filtered views.

## 3. Alert Management 🔔
- **Edit Alerts**: Admins can now edit sent messages in the history.
- **Delete Alerts**: Remove outdated or incorrect alerts.
- **Improved UI**: Action buttons added directly to the alert history list.

## Files Modified:
- `admin/index.php`: Added polling logic, service filters, and alert management UI.
- `admin/api_general.php`: Confirmed backend support for alert management.
