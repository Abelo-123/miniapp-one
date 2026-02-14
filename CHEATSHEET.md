# 🧠 THE INTELLIGENT CHEATSHEET
## *Quick Reference with Embedded Reasoning*

> **USAGE**: Scan this first. Think, then act. When deeper reasoning needed → Read full guide.
> **PREREQUISITE**: You should have already read `AGENT_PROMPT.md`. This file is a companion.

---

## 🎯 THE MASTER ALGORITHM

```
BEFORE ANY ACTION, ASK:
┌─────────────────────────────────────────────────────────────┐
│ 1. WHAT does user want? (not just what they said)           │
│ 2. WHAT files need to change?                               │
│ 3. Is this a boot-path file? (index.html, init.ts, App.tsx) │
│ 4. WHAT could go wrong?                                     │
│ 5. HOW will I verify success? (npx tsc --noEmit)            │
└─────────────────────────────────────────────────────────────┘
Then execute. Then verify.
```

---

## 📁 FILE DECISION MATRIX

```
User wants to...              →  Modify...
────────────────────────────────────────────────
Add new page/tab              →  pages/[New]/*, App.tsx, BottomNav, types.ts
Change page logic             →  [Page].tsx
Change page style             →  [Page].css  
Add data type                 →  types.ts
Add/change API                →  api.ts
Global styles                 →  index.css
Build/deploy config           →  vite.config.ts
Mock user for testing         →  mockEnv.ts
SDK initialization            →  init.ts (⚠️ read AGENT_PROMPT §4 first)
SDK feature wrappers          →  helpers/telegram.ts
First-paint / loading         →  index.html (⚠️ read AGENT_PROMPT §4.2 first)
Global app state              →  context/AppContext.tsx
```

---

## ⚡ TELEGRAM SDK v3.x — QUICK REFERENCE

### Signal-Based API (NOT hooks)

```typescript
// ─── Back Button ──────────────────────────────────
import { showBackButton, hideBackButton, onBackButtonClick } from '@telegram-apps/sdk-react';

showBackButton();
const off = onBackButtonClick(() => { /* handle */ });
// Cleanup: off()
hideBackButton();

// ─── Init Data (User Info) ────────────────────────
import { useSignal, initData } from '@telegram-apps/sdk-react';

const data = useSignal(initData.state);
const userId = data?.user?.id;
const userName = data?.user?.firstName;

// ─── Settings Button ─────────────────────────────
import { onSettingsButtonClick } from '@telegram-apps/sdk-react';

const off = onSettingsButtonClick(() => { /* handle */ });

// ─── Viewport ──────────────────────────────────────
import { mountViewport, bindViewportCssVars } from '@telegram-apps/sdk-react';

if (mountViewport.isAvailable()) {
  await mountViewport();
  bindViewportCssVars();
}

// ─── Swipe Behavior ───────────────────────────────
import { mountSwipeBehavior, disableVerticalSwipes } from '@telegram-apps/sdk-react';

if (mountSwipeBehavior.isAvailable()) {
  mountSwipeBehavior();
  disableVerticalSwipes();
}
```

### SDK Helpers (use these in components!)

```typescript
import {
  hapticSelection,        // Light tap — button press, tab switch
  hapticImpact,           // Heavy tap — delete, confirm: hapticImpact('heavy')
  hapticNotification,     // Result — hapticNotification('success' | 'error')
  showConfirm,            // Native confirm: await showConfirm('Delete?')
  showPopup,              // Custom popup with buttons
  showAlert,              // Simple alert
  cloudSet,               // Persist to Telegram Cloud Storage
  cloudGet,               // Read from Cloud Storage
  openLink,               // Open URL in Telegram browser
  isTelegramEnv,          // Check if inside Telegram
} from '../../helpers/telegram';
```

### SDK Rules

```
✅ DO: Use helpers/telegram.ts wrappers
✅ DO: Wrap SDK calls in try/catch
✅ DO: Check .isAvailable() before mounting
✅ DO: Clean up onXxxClick() in useEffect returns

❌ DON'T: Import old hook API (useBackButton, etc.)
❌ DON'T: Call SDK functions directly from page components
❌ DON'T: Mount components that are already mounted
❌ DON'T: Forget to call the cleanup function from onXxxClick()
```

---

## 🚀 PERFORMANCE RULES

### What's Already Optimized (DON'T break these)

```
1. index.html has inline CSS spinner (instant dark bg)
2. Google Fonts loads non-blocking (media="print" trick)
3. init() has 2 phases — Phase 1 is sync, Phase 2 is deferred
4. React renders BEFORE init() Phase 2 completes
5. Pages are React.lazy() loaded (code-split per tab)
6. 4 separate vendor chunks (react, tma-sdk, tma-ui, router)
7. mockEnv.ts only imports in dev mode (tree-shaken in prod)
8. eruda only loads in debug mode (dynamic import)
```

### What NOT to Do

```
❌ Add await before root.render() — delays first paint
❌ Add @import url() in CSS — render-blocking
❌ Import pages eagerly in App.tsx — breaks code splitting
❌ Merge vendor chunks — hurts cache efficiency
❌ Use JSON.stringify in hot paths — expensive on mobile
❌ Add heavy deps without checking bundle impact
```

### Patterns to Follow

```typescript
// 1. OPTIMISTIC UPDATES (instant feedback)
const handle = async (id, value) => {
    const backup = state;              // Save for rollback
    setState(update(state, id, value)); // Update immediately
    try {
        await api.update(id, value);   // Sync with server
    } catch {
        setState(backup);              // Rollback on failure
    }
};

// 2. MEMOIZE EXPENSIVE COMPUTATIONS
const filtered = useMemo(() => 
    items.filter(predicate), 
    [items, predicate]
);

// 3. MEMOIZE WITH CALLBACKS (for memo'd children)
const handler = useCallback(async (id) => {
    // ... handler logic
}, [dependencies]);

// 4. SMART SHALLOW COMPARISON (avoid JSON.stringify)
setData(prev => {
    if (prev.length === newData.length &&
        prev.every((item, i) => item.id === newData[i].id)) {
        // Only deep-compare when structure matches
        if (JSON.stringify(prev) === JSON.stringify(newData)) return prev;
    }
    return newData;
});
```

---

## 🔄 STATE LOCATION DECISION

```
Used by ONE component    →  useState in that component
Used by SIBLINGS         →  useState in parent
Used EVERYWHERE          →  React Context (AppContext.tsx)
Needs PERSISTENCE        →  Telegram Cloud Storage (cloudSet/cloudGet)
From SERVER              →  fetch + useState
URL-based                →  route params (if using routes)
```

---

## 🎨 UI DECISION TREE

```
                    What style?
                        │
          ┌─────────────┴─────────────┐
          ▼                           ▼
   "Like Telegram"              "Custom design"
          │                           │
          ▼                           ▼
   Use telegram-ui            Use HTML + CSS
   components                 with reference
   (AppRoot, Tabbar,          (manual styling,
    Section, Cell,             custom components)
    Input, Modal, etc.)
```

---

## 📋 CREATE NEW PAGE (6 Steps)

```
1. Create folder: src/pages/[Name]/
2. Create files:
   └── index.ts      → export { Name } from './Name';
   └── types.ts      → interfaces
   └── [Name].tsx    → component (named export!)
   └── [Name].css    → styles
   
3. Add lazy import in App.tsx:
   const Name = lazy(() => import('../pages/Name/Name')
     .then(m => ({ default: m.Name })));

4. Add rendering in App.tsx:
   {activeTab === 'name' && <Name />}

5. Add tab to BottomNav/BottomNav.tsx:
   { id: 'name', label: 'Name', icon: '🎯' }

6. Add type to src/types.ts:
   type TabId = 'order' | 'history' | 'deposit' | 'more' | 'name';

7. Verify: npx tsc --noEmit && npm run build
```

---

## 🐛 DEBUGGING ALGORITHM

```
1. OBSERVE   → What exactly is the error?
2. REPRODUCE → Can you make it happen again?
3. ISOLATE   → What's the smallest code that breaks?
4. HYPOTHESIZE → What could cause this? (list 3)
5. TEST      → Try most likely fix first
6. VERIFY    → Does it work? No side effects?
```

### Common Bugs → Fixes

| Bug | Fix |
|-----|-----|
| Component not updating | Don't mutate state, create new object/array |
| Stale value in callback | Use `setState(prev => ...)` |
| Infinite loop | Check effect dependencies |
| Memory leak warning | Cleanup async in effect; cleanup SDK `off()` |
| "undefined" error | Use optional chaining `obj?.prop` |
| SDK function not found | Check v3 API — old hooks don't exist |
| Build fails after SDK update | Run `npx tsc --noEmit`, check init.ts imports |
| White flash on load | Check index.html inline CSS |
| Scroll stuck in Telegram | Check .scroll-wrapper CSS and touchstart fix |

---

## 📐 SIZING REFERENCE

```
Touch targets     →  min 44px × 44px
Page title        →  24px, bold
Body text         →  14-15px
Secondary text    →  12-13px
Page padding      →  16-20px
Item padding      →  12-14px
Border radius     →  6-8px (subtle), 50% (circle)
Transitions       →  0.15-0.2s ease
```

---

## 🎨 DARK THEME PALETTE

```css
--bg-primary:     #1a1a1a;
--bg-secondary:   #222222;
--bg-input:       #2a2a2a;
--border:         #333333;
--border-input:   #444444;
--text-primary:   #ffffff;
--text-secondary: #888888;
--text-muted:     #666666;
--accent:         #4fc3f7;
--danger:         #ff6b6b;
--success:        #4ade80;
```

---

## ✅ COMPLETION CHECKLIST

```
□ npx tsc --noEmit passes
□ npm run build succeeds
□ Feature works as requested
□ Handles loading, empty, error states
□ No console errors or warnings
□ Works on mobile (320px+)
□ Existing features still work
□ SDK calls wrapped in try/catch
□ Lazy loading preserved
□ Boot sequence not regressed
```

---

## 🚀 COMMANDS

```bash
npm run dev       # Start dev server
npm run build     # Production build (TS check + Vite)
npm run deploy    # Build + deploy to GitHub Pages
npm run lint      # Check code quality
```

---

## 📖 NEED MORE DEPTH?

- **Architecture & boot sequence:** `AGENT_PROMPT.md` (Sections 4-6)
- **SDK deep-dive:** `AGENT_PROMPT.md` (Section 5)
- **Performance details:** `AGENT_PROMPT.md` (Section 8)
- **Reasoning frameworks:** `.agent/workflows/telegram-miniapp-guide.md`
- **Telegram SDK docs:** https://docs.telegram-mini-apps.com/
- **Telegram UI docs:** https://telegram-mini-apps-ui.vercel.app/

---

*Cheatsheet v5.0 | Updated for SDK v3.x + Lazy Loading | 2026-02-14*
