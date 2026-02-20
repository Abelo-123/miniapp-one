# Admin Feature Update: Service Visibility Control

## New Feature: Hide/Show Services
Admins can now explicitly hide specific services from the user-facing SMM panel without deleting them or removing them from the provider.

### How it works:
1.  **Admin Panel (`/admin`)**:
    - Go to the **Services** tab.
    - Search for a service.
    - Click the **"Hide"** (or "Unhide") button next to any service.
    - Hidden services are marked with a `HIDDEN` badge.

2.  **User Panel (`/smm.php`)**:
    - The system automatically loads the list of hidden services.
    - Any service in the hidden list is **filtered out** from the dropdown menus and search results.
    - This happens on the client-side, ensuring users strictly cannot select them in the UI.

### Technical Implementation:
- **Storage**: `hidden_services.json` in the root directory (JSON array of IDs).
- **API**: `admin/api_general.php` handles `hide_service` and `unhide_service` actions.
- **Frontend**: `smm.php` initializes a `Set` of hidden IDs and filters the service list during the `loadServices()` phase.

### Verification:
- [x] Admin API correctly writes to JSON.
- [x] Admin UI correctly toggles status and updates visual state.
- [x] User Frontend correctly reads JSON and filters array.
