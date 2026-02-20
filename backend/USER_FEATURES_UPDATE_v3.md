# User Features Update 3.0: Universal Service Support

## 1. Universal Service Type Support 🌍
- **Dynamic Order Form**: The order form now automatically adapts to the selected service type.
- **Implemented Types**:
  - **Default**: Link + Quantity
  - **Custom Comments**: Link + Comments (Auto-calculated quantity)
  - **Package**: Link only (Fixed quantity)
  - **Poll**: Link + Quantity + Answer Number
  - **Mentions / Comment Likes**: Link + Quantity + Username (if required)
- **Smart Validation**: Only validates fields required for the specific service type.

## 2. Order History Actions (Previous Update)
- **Refill**: Available for applicable completed orders.
- **Cancel**: Available for applicable pending orders.

## Files Modified:
- `smm.php`: Added dynamic form fields and logic for handling multiple service types.
- `process_order.php`: Updated to forward all necessary parameters (comments, username, answer_number) to the provider API.
