# User Features Update: Advanced Order Management

## 1. Advanced Service Support
- **Custom Comments**: Users can now use services requiring custom comments (e.g., "Custom Comments Limit 100").
    - Input changes from "Quantity" to "Comments" textarea automatically.
    - Quantity is auto-calculated based on number of lines.
    - Comments are sent to the provider API.

## 2. User Order Actions 
- **Refill Button**: Users can now request a refill for dropped orders directly from their history.
    - Visible ONLY if the user's order is "Completed" AND the service supports refill.
- **Cancel Button**: Users can self-cancel orders.
    - Visible ONLY if the order is "Pending/Processing" AND the service supports cancellation.
    - **Automatic Refund**: If the cancellation is successful at the provider level, the user is instantly refunded to their balance.

## files Created/Modified:
- `user_actions.php` (New): Handles backend API calls for cancel/refill.
- `smm.php`: Updated UI for comments input and history action buttons.
- `process_order.php`: Updated to forward comments to the API.
