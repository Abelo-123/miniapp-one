---
description: Complete guide for building, configuring, and maintaining Telegram Mini Apps with React + Vite
---

# THE MASTER GUIDE TO TELEGRAM MINI APPS
## *A Complete Knowledge Transfer from Expert to Beginner*

> **PHILOSOPHY**: This guide contains 20+ years of software engineering wisdom distilled into clear rules. Follow it exactly, and you will make decisions like a senior developer. No guessing. No confusion. Just follow the rules.

---

# PART 1: THE FUNDAMENTALS

## 1.1 What is a Telegram Mini App?

```
A Telegram Mini App is:
├── A web application (HTML, CSS, JavaScript)
├── That runs INSIDE Telegram messenger
├── In a special WebView container
├── With access to Telegram user data
└── Styled to match Telegram's look and feel

It is NOT:
├── A native mobile app
├── A Telegram bot (but can work with one)
└── A standalone website
```

## 1.2 The Technology We Use

```
FRONTEND (What users see):
├── React 18        → Component-based UI library
├── TypeScript      → JavaScript with type safety
├── Vite            → Fast build tool and dev server
├── CSS             → Styling (no frameworks needed)
└── Telegram SDK    → Connect to Telegram features

BACKEND (Data storage):
├── PHP             → Simple server-side language
├── SQLite          → File-based database (no setup needed)
└── REST API        → Standard HTTP requests

DEPLOYMENT:
├── GitHub Pages    → Free static hosting
└── Any web host    → For the PHP backend
```

## 1.3 The Golden Rules

```
RULE 1: ALWAYS use absolute file paths when referencing files
        GOOD: d:\project\src\pages\MyPage.tsx
        BAD:  ./pages/MyPage.tsx

RULE 2: ALWAYS verify your work after changes
        - No TypeScript errors in terminal
        - No console errors in browser
        - Feature works as expected

RULE 3: NEVER guess - if unsure, look at existing code patterns

RULE 4: ALWAYS keep the user informed of what you're doing

RULE 5: SIMPLE is better than complex
        - Fewer dependencies = fewer problems
        - Plain CSS > CSS frameworks
        - Direct code > abstraction layers
```

---

# PART 2: PROJECT ANATOMY

## 2.1 Universal Project Structure

Every Telegram Mini App follows this structure:

```
[project-root]/
│
├── src/                          # ALL source code lives here
│   │
│   ├── index.tsx                 # Entry point - mounts React
│   ├── index.css                 # Global styles
│   ├── init.ts                   # Telegram SDK setup
│   ├── mockEnv.ts                # Fake Telegram for browser testing
│   │
│   ├── components/               # Reusable pieces
│   │   ├── App.tsx              # Main app + routing
│   │   ├── Root.tsx             # Provider wrappers
│   │   └── Page.tsx             # Page wrapper
│   │
│   ├── navigation/
│   │   └── routes.tsx           # URL → Page mapping
│   │
│   ├── pages/                    # Each screen of your app
│   │   └── [PageName]/          # One folder per page
│   │       ├── index.ts         # Re-export
│   │       ├── [PageName].tsx   # Component
│   │       ├── [PageName].css   # Styles
│   │       ├── api.ts           # API calls (if needed)
│   │       └── types.ts         # TypeScript types
│   │
│   └── helpers/                  # Utility functions
│
├── public/                       # Static files (copied as-is)
├── [api-folder]/                 # Backend PHP files
│
├── index.html                    # HTML shell
├── package.json                  # Dependencies
├── vite.config.ts               # Build configuration
└── tsconfig.json                # TypeScript configuration
```

## 2.2 File Purpose Reference

| File | Purpose | When to Modify |
|------|---------|----------------|
| `index.tsx` | Boots the app | Almost never |
| `init.ts` | Initializes Telegram SDK | To enable/disable features |
| `mockEnv.ts` | Fakes Telegram in browser | To change test user data |
| `routes.tsx` | Maps URLs to pages | When adding new pages |
| `App.tsx` | Main app container | Almost never |
| `vite.config.ts` | Build settings | When changing deployment path |
| `package.json` | Dependencies | When adding libraries |

---

# PART 3: THE DECISION FRAMEWORKS

## 3.1 Master Decision Tree: What Should I Do?

```
START: User made a request
│
├─── Is it about RUNNING the app?
│    └── YES → Run: npm run dev
│              URL: http://localhost:5173/[base-path]
│
├─── Is it about CREATING something new?
│    │
│    ├── New PAGE?
│    │   └── Follow: PAGE CREATION CHECKLIST (Section 5)
│    │
│    ├── New COMPONENT (reusable piece)?
│    │   └── Create in: src/components/[Name].tsx
│    │
│    ├── New API ENDPOINT?
│    │   └── Follow: API CREATION CHECKLIST (Section 6)
│    │
│    └── New FEATURE on existing page?
│        └── Modify the page's .tsx file
│
├─── Is it about STYLING/DESIGN?
│    └── Follow: UI/UX DECISION FRAMEWORK (Section 4)
│
├─── Is it about FIXING something?
│    └── Follow: DEBUGGING FRAMEWORK (Section 9)
│
├─── Is it about DEPLOYMENT?
│    └── Follow: DEPLOYMENT CHECKLIST (Section 8)
│
└─── Not sure?
     └── Ask user for clarification
```

---

# PART 4: UI/UX DECISION FRAMEWORK

## 4.1 The First Question: What Style Does User Want?

```
QUESTION: What visual style should this have?
│
├─── "Telegram native" / "Like Telegram" / "Standard"
│    │
│    │   USE: @telegram-apps/telegram-ui components
│    │   LOOK: Matches Telegram exactly
│    │   COMPONENTS: List, Section, Cell, Button, Input, Modal
│    │
│    └── WHEN TO PICK:
│        ✓ User wants "normal" Telegram look
│        ✓ Building settings screens
│        ✓ Forms and inputs
│        ✓ Standard list layouts
│
├─── "Custom" / "Minimal" / "Like [reference image]"
│    │
│    │   USE: Plain HTML + Custom CSS
│    │   LOOK: Unique, branded, special
│    │   COMPONENTS: div, ul, button with custom classes
│    │
│    └── WHEN TO PICK:
│        ✓ User shows a reference image
│        ✓ User wants "simple" or "minimal"
│        ✓ Building games or unique interfaces
│        ✓ User mentions specific design style
│
└─── Not specified?
     └── ASK: "Do you want the standard Telegram look,
              or a custom design?"
```

## 4.2 Color Scheme Decision

```
QUESTION: What colors should I use?
│
├─── User specified colors?
│    └── USE those exact colors
│
├─── "Dark theme" / "Dark mode"?
│    │
│    └── USE THIS PALETTE:
│        Background (main):     #1a1a1a
│        Background (secondary): #222222
│        Background (inputs):    #2a2a2a
│        Borders (subtle):       #2a2a2a
│        Borders (visible):      #444444
│        Text (primary):         #ffffff
│        Text (secondary):       #888888
│        Text (muted):           #666666
│        Accent (links/active):  #4fc3f7  (cyan)
│        Danger (delete):        #ff6b6b  (red)
│        Success (complete):     #4ade80  (green)
│
├─── "Light theme" / "Light mode"?
│    │
│    └── USE THIS PALETTE:
│        Background (main):     #ffffff
│        Background (secondary): #f5f5f5
│        Background (inputs):    #ffffff
│        Borders:                #e0e0e0
│        Text (primary):         #1a1a1a
│        Text (secondary):       #666666
│        Accent:                 #2196f3  (blue)
│        Danger:                 #f44336  (red)
│        Success:                #4caf50  (green)
│
├─── "Match Telegram theme"?
│    │
│    └── USE CSS VARIABLES:
│        var(--tgui--bg_color)
│        var(--tgui--secondary_bg_color)
│        var(--tgui--text_color)
│        var(--tgui--hint_color)
│        var(--tgui--link_color)
│        var(--tgui--button_color)
│        var(--tgui--destructive_text_color)
│
└─── Not specified?
     └── DEFAULT: Dark theme palette (users prefer dark mode)
```

## 4.3 Layout Decision

```
QUESTION: How should content be arranged?
│
├─── "List" / "Items" / "Feed"
│    │
│    └── PATTERN: Vertical scrolling list
│        .container { min-height: 100vh; }
│        .list { list-style: none; padding: 0; }
│        .item { padding: 14px 20px; border-bottom: 1px solid #2a2a2a; }
│
├─── "Grid" / "Tiles" / "Cards"
│    │
│    └── PATTERN: Grid layout
│        .grid {
│          display: grid;
│          grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
│          gap: 12px;
│          padding: 16px;
│        }
│
├─── "Table" / "Spreadsheet" / "Tracker" (like habits)
│    │
│    └── PATTERN: Fixed columns grid
│        .table {
│          display: grid;
│          grid-template-columns: 1fr repeat(5, 48px);
│        }
│        .row { display: contents; }
│
├─── "Form" / "Input fields"
│    │
│    └── PATTERN: Stacked inputs
│        .form { padding: 16px 20px; }
│        .field { margin-bottom: 16px; }
│        .input { width: 100%; padding: 12px 16px; }
│
├─── "Dashboard" / "Stats"
│    │
│    └── PATTERN: Cards with metrics
│        .dashboard { padding: 16px; }
│        .stat-card {
│          background: #2a2a2a;
│          border-radius: 12px;
│          padding: 20px;
│          margin-bottom: 12px;
│        }
│
└─── "Full screen" / "Single item focus"
     │
     └── PATTERN: Centered content
         .container {
           min-height: 100vh;
           display: flex;
           flex-direction: column;
           align-items: center;
           justify-content: center;
         }
```

## 4.4 Component Size Decision

```
QUESTION: How big should elements be?
│
├─── TOUCH TARGETS (buttons, clickable items)
│    │
│    ├── MINIMUM: 44px × 44px (Apple guideline)
│    ├── RECOMMENDED: 48px × 48px
│    └── COMFORTABLE: 56px × 56px
│
├─── TEXT SIZES
│    │
│    ├── Page title:     24px, font-weight: 600
│    ├── Section header: 18px, font-weight: 600
│    ├── Body text:      15-16px, font-weight: 400
│    ├── Secondary text: 13-14px, font-weight: 400
│    ├── Caption/hint:   11-12px, font-weight: 400
│    └── Button text:    14-15px, font-weight: 600
│
├─── SPACING
│    │
│    ├── Page padding:   16-20px horizontal
│    ├── Section gap:    24-32px vertical
│    ├── Item padding:   12-16px vertical, 16-20px horizontal
│    ├── Between items:  8-12px
│    └── Icon-to-text:   8-12px
│
└─── BORDER RADIUS
     │
     ├── Subtle roundness:  4-6px
     ├── Medium roundness:  8-12px
     ├── Pill/rounded:      20-24px or 50%
     └── Circle:            50%
```

## 4.5 Interactive States Decision

```
EVERY interactive element needs these states:

DEFAULT STATE
├── Normal colors
└── No effects

HOVER STATE (mouse over)
├── Subtle background change (+10% brightness or slightly different shade)
├── Cursor: pointer
└── Optional: scale(1.02) for emphasis

ACTIVE/PRESSED STATE
├── Darker than hover
└── Optional: scale(0.98) to show "press"

DISABLED STATE
├── opacity: 0.5
├── cursor: not-allowed
└── No hover effects

FOCUS STATE (keyboard navigation)
├── Visible outline or ring
└── Example: box-shadow: 0 0 0 2px #4fc3f7;

LOADING STATE
├── Show spinner or skeleton
├── Disable interactions
└── Optional: opacity: 0.7;
```

## 4.6 Animation Decision

```
QUESTION: Should this animate?
│
├─── Transition between states (hover, active)?
│    └── YES, always. Use: transition: all 0.2s ease;
│
├─── Loading indicator?
│    └── YES. Use spinner animation:
│        @keyframes spin {
│          to { transform: rotate(360deg); }
│        }
│        animation: spin 0.8s linear infinite;
│
├─── Item appearing/disappearing?
│    │
│    ├── Simple fade: opacity 0→1, transition 0.2s
│    ├── Slide in: translateY(10px)→0, opacity 0→1
│    └── If performance matters: just opacity
│
├─── User action feedback (tap, toggle)?
│    └── YES, but subtle:
│        - scale(0.95) on press
│        - 0.1s duration max
│
└─── Decorative animations?
     └── ONLY if user specifically requests
         Keep it subtle and purposeful
```

## 4.7 Empty States Decision

```
WHEN showing "no items" / "no results":

STRUCTURE:
┌─────────────────────────────────┐
│                                 │
│           [Icon/Emoji]          │
│                                 │
│         Primary Message         │
│     (What is the situation)     │
│                                 │
│        Secondary Message        │
│      (What they can do)         │
│                                 │
│         [Action Button]         │
│           (Optional)            │
│                                 │
└─────────────────────────────────┘

EXAMPLES:

No habits yet:
├── Icon: 📝 or illustration
├── Primary: "No habits yet"
├── Secondary: "Add your first habit to get started"
└── Button: "Add Habit" (optional)

No search results:
├── Icon: 🔍
├── Primary: "No results found"
├── Secondary: "Try different keywords"
└── Button: "Clear search"

Error loading:
├── Icon: ⚠️
├── Primary: "Something went wrong"
├── Secondary: "We couldn't load your data"
└── Button: "Try Again"
```

---

# PART 5: PAGE CREATION CHECKLIST

## 5.1 Before You Start

```
□ Know the page name (PascalCase, e.g., "SettingsPage")
□ Know the URL path (lowercase-with-dashes, e.g., "/settings")
□ Know what data the page shows
□ Know what actions user can take
```

## 5.2 Files to Create

Create these 4 files in `src/pages/[PageName]/`:

### File 1: `index.ts`
```typescript
export { [PageName] } from './[PageName]';
```

### File 2: `types.ts`
```typescript
// Define your data shapes here
export interface [ItemType] {
  id: number;
  text: string;
  // Add more fields as needed
}
```

### File 3: `[PageName].css`
```css
/* Use the templates from Section 4 based on your decisions */
```

### File 4: `[PageName].tsx`
```typescript
import { useState, useEffect, type FC } from 'react';
import { useSignal, initData } from '@telegram-apps/sdk-react';
import './[PageName].css';

export const [PageName]: FC = () => {
    // Telegram user data
    const initDataState = useSignal(initData.state);
    const userId = initDataState?.user?.id || 0;

    // State
    const [isLoading, setIsLoading] = useState(true);
    const [data, setData] = useState<any[]>([]);

    // Load on mount
    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        setIsLoading(true);
        try {
            // Your loading logic
            setData([]);
        } finally {
            setIsLoading(false);
        }
    };

    // Loading state
    if (isLoading) {
        return (
            <div className="[page]-container">
                <div className="[page]-loading">
                    <div className="[page]-spinner" />
                    <span>Loading...</span>
                </div>
            </div>
        );
    }

    // Main render
    return (
        <div className="[page]-container">
            <header className="[page]-header">
                <h1 className="[page]-title">[Page Title]</h1>
            </header>
            
            {/* Your content */}
        </div>
    );
};
```

## 5.3 Register the Route

**File to modify:** `src/navigation/routes.tsx`

**Add import:**
```typescript
import { [PageName] } from '@/pages/[PageName]';
```

**Add to routes array:**
```typescript
{ path: '/[url-path]', Component: [PageName], title: '[Title]' },
```

## 5.4 Verification

```
□ All 4 files created
□ Import added to routes.tsx
□ Route added to routes array
□ No TypeScript errors
□ Page loads at http://localhost:5173/[base]/#/[url-path]
□ Styling looks correct
□ All features work
```

---

# PART 6: API INTEGRATION CHECKLIST

## 6.1 Frontend API Layer

**File to create:** `src/pages/[PageName]/api.ts`

```typescript
import type { [DataType] } from './types';

const API_BASE = import.meta.env.VITE_API_URL || 'https://your-domain.com/api/';

// ═══════════════════════════════════════════════════
// FETCH ALL
// ═══════════════════════════════════════════════════
export async function fetchItems(userId: number): Promise<[DataType][]> {
    const response = await fetch(`${API_BASE}?user_id=${userId}`);
    if (!response.ok) throw new Error('Failed to fetch');
    return response.json();
}

// ═══════════════════════════════════════════════════
// CREATE
// ═══════════════════════════════════════════════════
export async function createItem(userId: number, data: Partial<[DataType]>): Promise<[DataType]> {
    const response = await fetch(`${API_BASE}?user_id=${userId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    if (!response.ok) throw new Error('Failed to create');
    return response.json();
}

// ═══════════════════════════════════════════════════
// UPDATE
// ═══════════════════════════════════════════════════
export async function updateItem(userId: number, id: number, data: Partial<[DataType]>): Promise<[DataType]> {
    const response = await fetch(`${API_BASE}?user_id=${userId}&id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    if (!response.ok) throw new Error('Failed to update');
    return response.json();
}

// ═══════════════════════════════════════════════════
// DELETE
// ═══════════════════════════════════════════════════
export async function deleteItem(userId: number, id: number): Promise<void> {
    const response = await fetch(`${API_BASE}?user_id=${userId}&id=${id}`, {
        method: 'DELETE',
    });
    if (!response.ok) throw new Error('Failed to delete');
}
```

## 6.2 Backend PHP API Template

```php
<?php
// CORS - Required for browser access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database
$db = new SQLite3(__DIR__ . '/database.db');

// Create table
$db->exec('
    CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Parameters
$method = $_SERVER['REQUEST_METHOD'];
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Validate
if ($userId === null) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id required']);
    exit();
}

// Handle
switch ($method) {
    case 'GET':
        $stmt = $db->prepare('SELECT * FROM items ORDER BY created_at DESC');
        $result = $stmt->execute();
        $items = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $items[] = $row;
        }
        echo json_encode($items);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO items (user_id, text) VALUES (?, ?)');
        $stmt->bindValue(1, $userId);
        $stmt->bindValue(2, $input['text']);
        $stmt->execute();
        
        $id = $db->lastInsertRowID();
        $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->bindValue(1, $id);
        echo json_encode($stmt->execute()->fetchArray(SQLITE3_ASSOC));
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE items SET text = ? WHERE id = ?');
        $stmt->bindValue(1, $input['text']);
        $stmt->bindValue(2, $itemId);
        $stmt->execute();
        
        $stmt = $db->prepare('SELECT * FROM items WHERE id = ?');
        $stmt->bindValue(1, $itemId);
        echo json_encode($stmt->execute()->fetchArray(SQLITE3_ASSOC));
        break;

    case 'DELETE':
        $stmt = $db->prepare('DELETE FROM items WHERE id = ?');
        $stmt->bindValue(1, $itemId);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;
}

$db->close();
```

## 6.3 Using API in Component (Optimistic Updates Pattern)

```typescript
// THE GOLDEN PATTERN FOR UI UPDATES:
// 1. Update UI immediately (optimistic)
// 2. Make API call
// 3. If error, revert UI

const handleUpdate = async (itemId: number, newValue: any) => {
    // Save current state for rollback
    const previousData = data;
    
    // 1. Update UI immediately
    setData(data.map(item => 
        item.id === itemId ? { ...item, ...newValue } : item
    ));

    try {
        // 2. Make API call
        await updateItem(userId, itemId, newValue);
    } catch (error) {
        // 3. Revert on error
        setData(previousData);
        console.error('Update failed:', error);
    }
};
```

---

# PART 7: TELEGRAM SDK REFERENCE

## 7.1 Getting User Data

```typescript
import { useSignal, initData } from '@telegram-apps/sdk-react';

const Component = () => {
    const state = useSignal(initData.state);
    
    // Available user fields:
    const userId = state?.user?.id;              // number - ALWAYS available
    const firstName = state?.user?.firstName;    // string - ALWAYS available
    const lastName = state?.user?.lastName;      // string | undefined
    const username = state?.user?.username;      // string | undefined
    const languageCode = state?.user?.languageCode; // string (e.g., "en")
    const isPremium = state?.user?.isPremium;    // boolean
    const photoUrl = state?.user?.photoUrl;      // string | undefined
    
    // Chat info (if opened from chat):
    const chatId = state?.chat?.id;
    const chatType = state?.chat?.type;
    
    // Start parameter (from bot link):
    const startParam = state?.startParam;
};
```

## 7.2 Theme Detection

```typescript
import { useSignal, isMiniAppDark } from '@telegram-apps/sdk-react';

const Component = () => {
    const isDark = useSignal(isMiniAppDark);
    
    return (
        <div className={isDark ? 'dark-theme' : 'light-theme'}>
            {/* Content */}
        </div>
    );
};
```

## 7.3 Back Button

```typescript
import { showBackButton, hideBackButton, onBackButtonClick } from '@telegram-apps/sdk-react';
import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

const Page = () => {
    const navigate = useNavigate();

    useEffect(() => {
        showBackButton();
        const cleanup = onBackButtonClick(() => navigate(-1));
        
        return () => {
            cleanup();
            hideBackButton();
        };
    }, []);
};
```

## 7.4 Haptic Feedback

```typescript
import { hapticFeedback } from '@telegram-apps/sdk-react';

// Use for touch feedback:
hapticFeedback.impactOccurred('light');   // Subtle tap
hapticFeedback.impactOccurred('medium');  // Normal tap
hapticFeedback.impactOccurred('heavy');   // Strong tap

// Use for notifications:
hapticFeedback.notificationOccurred('success'); // Positive action
hapticFeedback.notificationOccurred('error');   // Error occurred
hapticFeedback.notificationOccurred('warning'); // Caution
```

## 7.5 Opening Links

```typescript
import { openLink, openTelegramLink } from '@telegram-apps/sdk-react';

// External websites:
openLink('https://example.com');

// Telegram links (channels, users, etc.):
openTelegramLink('https://t.me/channel_name');
```

---

# PART 8: DEPLOYMENT CHECKLIST

## 8.1 Pre-Deployment Verification

```
□ npm run build succeeds without errors
□ vite.config.ts has correct base path
□ package.json has correct homepage URL
□ All API URLs point to production servers
□ Mock environment disabled in production
```

## 8.2 GitHub Pages Deployment

```
STEP 1: Verify configuration

vite.config.ts:
  base: '/repository-name'    ← Must match your repo name

package.json:
  "homepage": "https://username.github.io/repository-name"

STEP 2: Deploy

COMMAND: npm run deploy
WAIT: 2-3 minutes for GitHub Pages to update

STEP 3: Verify

URL: https://username.github.io/repository-name
□ Page loads
□ No 404 errors
□ Assets load correctly
```

## 8.3 Telegram Bot Configuration

```
STEP 1: Go to @BotFather in Telegram

STEP 2: Send /mybots

STEP 3: Select your bot

STEP 4: Bot Settings → Menu Button → Configure

STEP 5: Enter your deployed URL:
        https://username.github.io/repository-name

STEP 6: Test by opening your bot and clicking menu button
```

---

# PART 9: DEBUGGING FRAMEWORK

## 9.1 Error Identification

```
ERROR TYPE: Cannot find module '@/...'
│
├── CAUSE: Path alias not configured
├── CHECK: tsconfig.json has "paths": { "@/*": ["./src/*"] }
└── FIX: Ensure vite-tsconfig-paths plugin is in vite.config.ts

───────────────────────────────────────────────

ERROR TYPE: initData is undefined
│
├── CAUSE: Running outside Telegram without mock
├── CHECK: mockEnv.ts is imported in index.tsx
└── FIX: Ensure mockEnv.ts properly sets up fake Telegram data

───────────────────────────────────────────────

ERROR TYPE: Blank page in Telegram
│
├── CAUSE: Base path mismatch
├── CHECK: vite.config.ts 'base' matches deployment path
└── FIX: Set base to '/your-repo-name' for GitHub Pages

───────────────────────────────────────────────

ERROR TYPE: CORS error
│
├── CAUSE: Backend missing headers
├── CHECK: PHP file has CORS headers
└── FIX: Add:
│   header('Access-Control-Allow-Origin: *');
│   header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
│   header('Access-Control-Allow-Headers: Content-Type');

───────────────────────────────────────────────

ERROR TYPE: TypeScript errors
│
├── CAUSE: Type mismatch or missing types
├── CHECK: Read the error message carefully
└── FIX: 
│   - Add missing types to types.ts
│   - Add proper type annotations
│   - Use 'as' for type assertions when certain

───────────────────────────────────────────────

ERROR TYPE: Styles not applying
│
├── CAUSE: CSS not imported or wrong class name
├── CHECK: 
│   1. CSS file is imported in component
│   2. Class names match exactly (case-sensitive)
└── FIX: Verify import statement and class names
```

## 9.2 Debug Commands

```bash
# Check for TypeScript errors
npm run build

# Check for lint errors
npm run lint

# Fix lint errors automatically
npm run lint:fix

# Start dev with HTTPS (for mobile testing)
npm run dev:https
```

## 9.3 Mobile Debug Console

Enable Eruda in `init.ts`:

```typescript
// Always enable Eruda for debugging
import('eruda').then(({ default: eruda }) => {
    eruda.init();
});
```

---

# PART 10: COMPLETE CSS TEMPLATES

## 10.1 Dark Theme Base Template

```css
/* ═══════════════════════════════════════════════════
   DARK THEME - MINIMAL STYLE
   Copy this entire template and customize
   ═══════════════════════════════════════════════════ */

/* Container - Full page dark background */
.[prefix]-container {
    background: #1a1a1a;
    min-height: 100vh;
    color: #fff;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header - Sticky at top */
.[prefix]-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #1a1a1a;
    position: sticky;
    top: 0;
    z-index: 10;
}

.[prefix]-title {
    font-size: 24px;
    font-weight: 600;
    color: #fff;
    margin: 0;
}

.[prefix]-header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.[prefix]-header-btn {
    background: none;
    border: none;
    color: #4fc3f7;
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: background 0.2s;
}

.[prefix]-header-btn:hover {
    background: rgba(79, 195, 247, 0.1);
}

/* Input Section */
.[prefix]-input-section {
    display: flex;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #333;
}

.[prefix]-input {
    flex: 1;
    background: #2a2a2a;
    border: 1px solid #444;
    border-radius: 8px;
    padding: 12px 16px;
    color: #fff;
    font-size: 15px;
    outline: none;
    transition: border-color 0.2s;
}

.[prefix]-input::placeholder {
    color: #666;
}

.[prefix]-input:focus {
    border-color: #4fc3f7;
}

.[prefix]-btn {
    background: #4fc3f7;
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    color: #000;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.[prefix]-btn:hover {
    opacity: 0.9;
}

.[prefix]-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* List */
.[prefix]-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.[prefix]-item {
    display: flex;
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid #2a2a2a;
    transition: background 0.15s;
}

.[prefix]-item:hover {
    background: #222;
}

/* Checkbox (circular) */
.[prefix]-checkbox {
    width: 22px;
    height: 22px;
    border: 2px solid #555;
    border-radius: 50%;
    margin-right: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.[prefix]-checkbox.checked {
    background: #4fc3f7;
    border-color: #4fc3f7;
}

.[prefix]-checkbox.checked::after {
    content: '✓';
    color: #fff;
    font-size: 12px;
    font-weight: bold;
}

/* Delete button */
.[prefix]-delete-btn {
    background: none;
    border: none;
    color: #666;
    font-size: 18px;
    cursor: pointer;
    padding: 8px;
    margin-left: auto;
    transition: color 0.2s;
}

.[prefix]-delete-btn:hover {
    color: #ff6b6b;
}

/* Loading state */
.[prefix]-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #888;
}

.[prefix]-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #333;
    border-top-color: #4fc3f7;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Empty state */
.[prefix]-empty {
    text-align: center;
    padding: 80px 20px;
    color: #666;
}

.[prefix]-empty h3 {
    font-size: 18px;
    color: #888;
    margin-bottom: 8px;
}

.[prefix]-empty p {
    font-size: 14px;
    margin: 0;
}
```

## 10.2 Grid/Table Template (Like Habits Tracker)

```css
/* Grid header row */
.[prefix]-grid-header {
    display: grid;
    grid-template-columns: 1fr repeat(5, 48px);
    padding: 8px 20px;
    border-bottom: 1px solid #333;
    background: #1a1a1a;
    position: sticky;
    top: 60px;
    z-index: 9;
}

.[prefix]-column-label {
    text-align: center;
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
}

/* Grid rows */
.[prefix]-grid-row {
    display: grid;
    grid-template-columns: 1fr repeat(5, 48px);
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid #2a2a2a;
}

/* Grid cell */
.[prefix]-grid-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 8px 0;
}

.[prefix]-cell-mark {
    font-size: 18px;
    color: #666;
    transition: all 0.2s;
}

.[prefix]-cell-mark.active {
    color: #4fc3f7;
}

.[prefix]-cell-mark:hover {
    transform: scale(1.2);
}
```

---

# PART 11: FINAL WISDOM

## 11.1 The 5 Principles of Good Code

```
1. CLARITY over cleverness
   - Code should be easy to read
   - Use descriptive names
   - Don't over-engineer

2. CONSISTENCY is king
   - Same patterns everywhere
   - Same naming conventions
   - Same file structure

3. SIMPLICITY wins
   - Less code = less bugs
   - Fewer dependencies = less maintenance
   - Direct solutions > abstract solutions

4. USER FIRST
   - Fast is better than fancy
   - Working is better than perfect
   - Feedback is essential

5. VERIFY EVERYTHING
   - Test after every change
   - Check on multiple devices
   - Never assume it works
```

## 11.2 When in Doubt

```
IF you're unsure about design:
   → Look at existing code patterns
   → Follow the decision frameworks
   → Ask the user for clarification

IF you're unsure about implementation:
   → Start simple
   → Get it working first
   → Refine after it works

IF something breaks:
   → Check the error message carefully
   → Look at the debugging framework
   → Undo recent changes if needed
```

## 11.3 The Success Checklist

Before saying you're done:

```
□ Feature works as user requested
□ No TypeScript errors
□ No console errors
□ Looks good on mobile (320px+)
□ Works in both light and dark themes
□ Existing features still work
□ Code is clean and consistent
```

---

*This guide contains complete knowledge for building professional Telegram Mini Apps.*
*Follow it step by step, and you will succeed.*

---

*Guide Version: 3.0 | Created: 2026-02-02*
