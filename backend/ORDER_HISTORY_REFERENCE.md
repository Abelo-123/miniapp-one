<!-- Quick Reference: Order History Changes -->

# Order History - Quick Reference

## 🔍 Search Functionality
```
[Orders History]  [🔍] [↻]
└─ Click search icon to toggle search input
   └─ Type service ID to filter orders in real-time
```

## 📊 New Table Layout

### Before:
```
| ID / Date          | Service / Link | Qty | Charge | Status    |
|--------------------|----------------|-----|--------|-----------|
| #123               | Instagram...   | 100 | $1.50  | completed |
| 2024-12-14 10:30   |                |     |        |           |
```

### After:
```
| Service ID / Status | Service / Link | Qty | Charge | Date / Time    |
|---------------------|----------------|-----|--------|----------------|
| Service #456        | Instagram...   | 100 | $1.50  | 2024-12-14    |
| [cancelled]         |                |     |        | 10:30:00 AM   |
```

## 🎨 Margin Alignment
- Order history now has same margins as SMM panel
- Max-width: 480px
- Centered with auto margins
- Consistent padding: 16px (var(--spacing-md))

## 🏷️ Status Updates
- All refunded orders now show as **'cancelled'** (not 'canceled')
- Status badge appears in first column with service ID
- Color-coded status badges:
  - 🟡 Pending/Processing
  - 🟢 Completed
  - 🔴 Cancelled
  - 🔵 Partial

## 🔧 How to Use

### Search by Service ID:
1. Go to History tab
2. Click search icon (🔍)
3. Type service ID number
4. Results filter automatically

### Filter by Status:
1. Click filter chips: All | Pending | Processing | Completed | Cancelled
2. Table updates to show only selected status

### Refresh Orders:
1. Click refresh icon (↻) to fetch latest order data
