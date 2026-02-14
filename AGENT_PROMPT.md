# AGENT PROMPT — Paxyo SMM Telegram Mini App

> **Read this entire file before touching any code.** It is the single source of truth for how this codebase works, why decisions were made, and how to maintain it. Ignorance of this document is the #1 cause of regressions.

---

## 1. YOUR ROLE

You are an expert Telegram Mini App developer maintaining a **production** React + Vite application deployed to GitHub Pages. Your decisions have real consequences — users interact with this app inside Telegram on mobile devices with constrained CPU, memory, and network.

**Your operating principles:**

1. **Think before you code.** Understand the request fully. Identify which files are affected. Consider side effects.
2. **Preserve what works.** This app has been hardened through multiple iterations. Don't remove patterns without understanding why they exist.
3. **Performance is a feature.** Every millisecond matters in a Telegram Mini App. The init sequence, lazy loading, and chunk splitting are intentional — don't regress them.
4. **Verify your changes.** Run `npx tsc --noEmit` after every change. If the build breaks, you broke it.

---

## 2. TECHNOLOGY STACK

| Layer | Technology | Version | Notes |
|-------|-----------|---------|-------|
| **Framework** | React | ^18.2.0 | Using `StrictMode` in production |
| **Bundler** | Vite | ^6.2.4 | With SWC (not Babel) via `@vitejs/plugin-react-swc` |
| **Language** | TypeScript | ^5.8.2 | Strict mode, `noEmit` for type-checking only |
| **Telegram SDK** | `@telegram-apps/sdk-react` | ^3.3.1 | **Signal-based API** — see Section 5 |
| **Telegram UI** | `@telegram-apps/telegram-ui` | ^2.1.8 | Native TUI components |
| **Routing** | `react-router-dom` | ^6.23.0 | Defined but app uses tab-based navigation instead |
| **Deployment** | GitHub Pages | — | Base path: `/miniapp-one` |

### Dependencies You Should Know About

- **`eruda`** — Mobile debug inspector. Only loaded in debug mode via dynamic `import()`. Never blocks production.
- **`lottie-react`** — Referenced in an orphan file (`src/components/stc.tsx`). Not actively used. Tree-shaken in production.
- **`@tonconnect/ui-react`** — Referenced in an orphan page (`src/pages/TONConnectPage/`). Not in active routes. Tree-shaken.

---

## 3. PROJECT STRUCTURE

```
d:\next\apps\mini-app\
├── index.html              ← Critical: has inline CSS, font preloads, DNS prefetch
├── vite.config.ts          ← Build config with manual chunk splitting
├── tsconfig.json           ← Strict TS config, path alias @/* → src/*
├── package.json            ← Scripts: dev, build, deploy, lint
├── AGENT_PROMPT.md         ← THIS FILE (you're reading it)
├── CHEATSHEET.md           ← Quick-reference patterns and templates
│
├── src/
│   ├── index.tsx           ← Entry point — boots SDK, renders React (SEE SECTION 4)
│   ├── index.css           ← Global styles — reset, scroll wrapper, scrollbar
│   ├── init.ts             ← Telegram SDK initialization (SEE SECTION 4)
│   ├── mockEnv.ts          ← Mock Telegram env for browser dev (dev-only)
│   ├── api.ts              ← SMM panel API client (fetch-based)
│   ├── constants.ts        ← Platform defs, status colors, formatETB
│   ├── types.ts            ← Global TypeScript interfaces
│   │
│   ├── context/
│   │   └── AppContext.tsx   ← Global state: user, services, orders, tabs, toasts
│   │
│   ├── helpers/
│   │   ├── telegram.ts     ← SDK wrappers (haptics, cloud storage, popups, etc.)
│   │   └── publicUrl.ts    ← Asset URL helper
│   │
│   ├── components/
│   │   ├── App.tsx          ← Main layout: tab router + lazy pages + Suspense
│   │   ├── Root.tsx         ← ErrorBoundary wrapper
│   │   ├── BottomNav/       ← Tab bar using TUI Tabbar component
│   │   ├── LoadingOverlay/  ← Full-screen loading spinner
│   │   ├── Toast/           ← Toast notification system
│   │   ├── PlatformGrid/   ← Social platform selector grid
│   │   ├── CategoryModal/  ← Category picker modal
│   │   ├── ServiceModal/   ← Service picker modal
│   │   ├── SearchModal/    ← Service search modal
│   │   └── OrderForm/      ← Order placement form
│   │
│   ├── pages/
│   │   ├── OrderPage/      ← Default tab: platform → category → service → order
│   │   ├── HistoryPage/    ← Order history with filters
│   │   ├── DepositPage/    ← Balance & deposit via Chapa
│   │   ├── TicketPage/     ← Support tickets & notifications (was MorePage)
│   │   └── TodoPage/       ← Collaborative habit tracker (separate feature)
│   │       ├── TodoPage.tsx ← Main component with memoized HabitRow
│   │       ├── TodoPage.css ← Grid-based habit tracker styles
│   │       ├── api.ts       ← Backend API (paxyo.com/backend/todos.php)
│   │       ├── types.ts     ← Todo, HabitMetadata, Member interfaces
│   │       └── index.ts     ← Re-export
│   │
│   ├── navigation/
│   │   └── routes.tsx       ← Route definitions (currently only TodoPage)
│   │
│   ├── css/
│   │   ├── bem.ts           ← BEM class utility
│   │   └── classnames.ts    ← Class concatenation utility
│   │
│   └── mocks/
│       └── data.ts          ← Mock data for services, orders, deposits, alerts
│
├── .agent/workflows/
│   └── telegram-miniapp-guide.md  ← Deep reasoning guide (800+ lines)
│
└── dist/                    ← Production build output (committed for GH Pages)
```

---

## 4. BOOT SEQUENCE — CRITICAL PATH

Understanding the boot sequence is essential. **Do not change the order without understanding why it exists.**

### 4.1 The Loading Timeline

```
User taps bot → Telegram loads index.html
    │
    ├── 1. INLINE CSS renders instantly (dark bg + spinner)
    │      └── #root:empty::after creates a CSS-only loading indicator
    │
    ├── 2. Google Fonts loads NON-BLOCKING (media="print" trick)
    │      └── Does NOT block first paint
    │
    ├── 3. JS modules load in PARALLEL (modulepreload hints)
    │      ├── index.js (entry — tiny)
    │      ├── react-vendor.js (React core)
    │      ├── tma-sdk.js (Telegram SDK)
    │      └── tma-ui.js (Telegram UI components)
    │
    ├── 4. index.tsx executes:
    │      ├── mockEnv.ts loads ONLY in dev mode (dynamic import)
    │      ├── init() starts (Phase 1 is synchronous):
    │      │    ├── initSDK() ← required for everything
    │      │    ├── restoreInitData() ← user info
    │      │    ├── miniApp.mountSync() ← theme params
    │      │    ├── bindThemeParamsCssVars() ← CSS variables
    │      │    └── signalReady() ← tells Telegram to hide its spinner
    │      │
    │      ├── React renders IMMEDIATELY (does NOT wait for Phase 2)
    │      │
    │      └── init() Phase 2 runs in background (Promise.allSettled):
    │           ├── mountViewport() + expandViewport()
    │           ├── setHeaderColor() + setBackgroundColor()
    │           └── mountSwipeBehavior() + disableVerticalSwipes()
    │
    ├── 5. App.tsx renders:
    │      ├── AppProvider wraps everything (context)
    │      ├── BottomNav renders (always visible)
    │      ├── Suspense boundary wraps tab content
    │      └── Active tab lazy-loads its chunk
    │
    └── 6. Active page renders (e.g., OrderPage)
           └── Page-specific API calls begin
```

### 4.2 Why This Order Matters

| Decision | Reason |
|----------|--------|
| Inline CSS in index.html | Eliminates white flash — user sees dark bg + spinner in <50ms |
| Font via `media="print"` trick | Google Fonts CSS is render-blocking by default. This defers it. |
| `signalReady()` before viewport | Telegram hides its loading spinner ASAP, user sees our UI faster |
| `React.render()` before `init()` completes | Phase 2 (viewport, colors) is cosmetic — doesn't block UI |
| Pages are `React.lazy()` | Only the active tab's JS loads. Other tabs load on-demand. |
| `mockEnv.ts` via dynamic import | Completely tree-shaken in production build |

### 4.3 Files Involved in Boot

| File | Role | Safe to modify? |
|------|------|-----------------|
| `index.html` | Inline CSS, font preloads, DNS prefetch | ⚠️ Careful — affects first paint |
| `src/index.tsx` | Entry point, SDK boot, render trigger | ⚠️ Careful — affects boot order |
| `src/init.ts` | SDK initialization (2-phase) | ⚠️ Careful — Phase 1 vs Phase 2 matters |
| `src/mockEnv.ts` | Dev-only mock environment | ✅ Safe — dev only |
| `src/components/Root.tsx` | ErrorBoundary wrapper | ✅ Safe |
| `src/components/App.tsx` | Tab router with lazy loading | ⚠️ Careful — lazy imports are intentional |

---

## 5. TELEGRAM SDK — HOW TO USE IT

### 5.1 SDK Architecture (v3.x — Signal-Based)

The `@telegram-apps/sdk-react` v3.x uses a **signal-based** architecture. This is NOT the older hook-based API. Key differences:

```typescript
// ❌ OLD API (v1/v2) — DO NOT USE
import { useBackButton } from '@telegram-apps/sdk-react';
const bb = useBackButton();
bb.show();

// ✅ CURRENT API (v3.x) — USE THIS
import { showBackButton, hideBackButton, onBackButtonClick } from '@telegram-apps/sdk-react';
showBackButton();
const off = onBackButtonClick(() => { /* handler */ });
// Later: off() to unsubscribe
```

### 5.2 Signal State Access

```typescript
// Reading signal state (e.g., initData)
import { useSignal, initData } from '@telegram-apps/sdk-react';

// Inside a component:
const initDataState = useSignal(initData.state);
const userId = initDataState?.user?.id;
```

### 5.3 Available SDK Features (Already Wired Up)

All SDK features are wrapped in `src/helpers/telegram.ts`. **Always use the helpers — never call the SDK directly from page components.**

| Helper | What it does | Usage |
|--------|-------------|-------|
| `hapticSelection()` | Light tap feedback | On button presses, tab switches |
| `hapticImpact(style)` | Strong tap feedback | On destructive actions, confirmations |
| `hapticNotification(type)` | Success/warning/error feedback | After API operations |
| `cloudSet(key, value)` | Save to Telegram Cloud Storage | Persist user preferences |
| `cloudGet(key)` | Read from Telegram Cloud Storage | Restore preferences on boot |
| `showConfirm(msg)` | Native Telegram confirm dialog | Delete confirmations |
| `showPopup(opts)` | Native Telegram popup | Custom dialogs with buttons |
| `showAlert(msg)` | Native Telegram alert | Simple notifications |
| `expandViewport()` | Expand mini app to full height | Called during init |
| `signalReady()` | Tell Telegram app is ready | Called during init |
| `setHeaderColor(hex)` | Set Telegram header bar color | Called during init |
| `setBackgroundColor(hex)` | Set Telegram bg color | Called during init |
| `openLink(url)` | Open URL in Telegram browser | External links |
| `openTelegramLink(url)` | Open t.me link natively | Telegram-specific links |
| `configureMainButton(cfg)` | Set up Telegram Main Button | For primary actions |
| `isTelegramEnv()` | Check if running in Telegram | Feature detection |

### 5.4 SDK Mount Pattern

Components must be mounted before use. The init sequence handles this:

```typescript
// These are mounted in init.ts — don't re-mount
mountBackButton.ifAvailable();           // Back button
mountClosingBehavior.ifAvailable();      // Close confirmation
mountSettingsButton.ifAvailable();       // Settings gear icon
mountSwipeBehavior();                    // Then disableVerticalSwipes()
miniApp.mountSync();                     // Theme params, CSS vars

// Viewport is async — mounted in Phase 2
await mountViewport();
bindViewportCssVars();
expandViewport();
```

### 5.5 Common SDK Pitfalls

| Mistake | What happens | Fix |
|---------|-------------|-----|
| Calling `mount()` on already-mounted component | Silent error or crash | Use `.ifAvailable()` or check `.isAvailable()` first |
| Using `isMounted` property | Doesn't exist in v3 | Use `.isAvailable()` instead |
| Importing non-existent exports | Build error | Check `@telegram-apps/sdk-react` v3 API docs |
| Not cleaning up `onClick` handlers | Memory leak | Store the `off` function, call it in useEffect cleanup |
| Accessing `initData` before `restoreInitData()` | Returns undefined | `restoreInitData()` is called in init.ts — wait for it |

---

## 6. APP ARCHITECTURE

### 6.1 Tab-Based Navigation

The app uses a **tab-based** navigation model (not routes). The `BottomNav` component switches between tabs by setting `activeTab` in the AppContext.

```
┌─────────────────────────────────────────────┐
│                  App.tsx                      │
│  ┌─────────────────────────────────────────┐ │
│  │         <Suspense fallback>             │ │
│  │  ┌────────────────────────────────────┐ │ │
│  │  │ activeTab === 'order'   → OrderPage│ │ │
│  │  │ activeTab === 'history' → HistoryP │ │ │
│  │  │ activeTab === 'deposit' → DepositP │ │ │
│  │  │ activeTab === 'ticket'  → TicketP  │ │ │
│  │  └────────────────────────────────────┘ │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │            <BottomNav />                │ │
│  └─────────────────────────────────────────┘ │
│  <ToastContainer />                          │
│  <LoadingOverlay />                          │
└─────────────────────────────────────────────┘
```

### 6.2 Lazy Loading (Code Splitting)

Pages are loaded with `React.lazy()` in `App.tsx`:

```typescript
const OrderPage = lazy(() => import('../pages/OrderPage/OrderPage').then(m => ({ default: m.OrderPage })));
```

**Why the `.then(m => ...)` wrapper?** Because the pages use **named exports** (`export function OrderPage`), not default exports. `React.lazy()` requires a default export, so we adapt.

**Production build produces separate chunks per page:**
- `OrderPage-*.js` (~10 KB gzip)
- `HistoryPage-*.js` (~1.3 KB gzip)
- `DepositPage-*.js` (~1.1 KB gzip)
- `TicketPage-*.js` (~1.2 KB gzip)

### 6.3 Global State (AppContext)

`src/context/AppContext.tsx` holds all shared state:

| State | Type | Purpose |
|-------|------|---------|
| `user` | `UserProfile \| null` | Current user (from Telegram initData) |
| `services` | `Service[]` | SMM services catalog |
| `recommendedIds` | `number[]` | Featured service IDs |
| `selectedPlatform` | `SocialPlatform \| null` | Currently selected platform |
| `selectedCategory` | `string \| null` | Currently selected category |
| `selectedService` | `Service \| null` | Currently selected service |
| `orders` | `Order[]` | User's order history |
| `deposits` | `Deposit[]` | User's deposit history |
| `alerts` | `Alert[]` | Notifications |
| `chatMessages` | `ChatMessage[]` | Support chat messages |
| `activeTab` | `TabId` | Current tab ('order' \| 'history' \| 'deposit' \| 'ticket') |
| `toasts` | `ToastMessage[]` | Active toast notifications |
| `isLoading` | `boolean` | Global loading state |

**Important behaviors:**
- Selecting a platform resets category and service (via `useEffect`)
- Selecting a category resets service
- Tab changes persist to Telegram Cloud Storage (`cloudSet('last_tab', tab)`)
- Tab state restores from Cloud Storage on mount

### 6.4 TodoPage (Habit Tracker) — Separate Feature

The `TodoPage` is a standalone collaborative habit tracker that talks to its own backend (`paxyo.com/backend/todos.php`). It has its own:
- **API layer** (`pages/TodoPage/api.ts`) — CRUD with custom text encoding for habit metadata
- **Types** (`pages/TodoPage/types.ts`) — Todo, HabitMetadata, Member
- **Polling** — Refreshes data every 12 seconds (silent, won't show loading)
- **Optimistic updates** — Adds/toggles appear instantly, rolls back on API error
- **Multi-user** — Each user gets a deterministic color from `MEMBER_COLORS` (hash-based)

**Data encoding:** Habit metadata is stored as `text ||| JSON` inside the todo text field (because the backend only has a text column).

---

## 7. BUILD & DEPLOYMENT

### 7.1 Vite Build Configuration

Key config in `vite.config.ts`:

```typescript
build: {
  target: 'esnext',          // Modern JS, no polyfills
  cssCodeSplit: true,         // Lazy pages get own CSS chunks
  minify: 'esbuild',         // Fast minification

  rollupOptions: {
    output: {
      manualChunks(id) {
        // 4 vendor chunks for optimal caching:
        // react-vendor (~46KB gzip) — rarely changes
        // tma-sdk (~19KB gzip) — Telegram SDK
        // tma-ui (~29KB gzip) — Telegram UI components
        // router (tiny) — React Router
      }
    }
  }
}
```

**Why granular chunks?** When you update the Telegram SDK, only `tma-sdk` cache invalidates. React stays cached. And vice versa.

### 7.2 Commands

```bash
npm run dev          # Start Vite dev server (http://localhost:5173)
npm run dev:https    # Start with HTTPS (needed for Telegram testing on device)
npm run build        # TypeScript check + Vite production build
npm run deploy       # Build + deploy to GitHub Pages
npm run lint         # ESLint check
npm run lint:fix     # ESLint auto-fix
```

### 7.3 Deployment Flow

```
npm run deploy
  └── npm run predeploy (automatic)
        └── npm run build
              ├── tsc --noEmit (type check)
              └── vite build (bundles to dist/)
  └── gh-pages -d dist
        └── Pushes dist/ to gh-pages branch
        └── GitHub Pages serves from https://Abelo-123.github.io/miniapp-one/
```

**Base path:** All assets are served under `/miniapp-one/`. This is set via `base: '/miniapp-one'` in vite.config.ts.

---

## 8. PERFORMANCE PRINCIPLES

These are the performance decisions already in place. **Do not regress them.**

### 8.1 Loading Performance

| Optimization | Where | What it does |
|-------------|-------|-------------|
| Inline critical CSS | `index.html` | Dark background + spinner renders in <50ms |
| Non-blocking font | `index.html` | `media="print" onload` trick — font doesn't block paint |
| DNS prefetch | `index.html` | `<link rel="preconnect" href="https://paxyo.com">` |
| Module preload | Vite (automatic) | Vendor chunks preload in parallel |
| Two-phase init | `init.ts` | React renders before viewport/colors finish |
| Lazy page loading | `App.tsx` | Only active tab's JS loads |
| CSS code splitting | `vite.config.ts` | Each lazy page gets its own CSS chunk |
| 4 vendor chunks | `vite.config.ts` | Granular caching — update one lib, others stay cached |

### 8.2 Runtime Performance

| Optimization | Where | What it does |
|-------------|-------|-------------|
| `React.memo()` on HabitRow | `TodoPage.tsx` | Prevents re-render when other rows change |
| `useMemo` on filtered lists | Multiple pages | Prevents recomputation on unrelated state changes |
| `useCallback` on handlers | `TodoPage.tsx` | Stable references for `memo` to work |
| Optimistic updates | `TodoPage.tsx` | UI updates instantly, API syncs in background |
| Smart poll comparison | `TodoPage.tsx` | Cheap length+ID check before expensive JSON compare |
| `{passive: true}` on touch | `App.tsx` | Scroll handler doesn't block compositor |

### 8.3 What NOT to Do

- **Don't add synchronous `await` before `root.render()`** — this delays first paint
- **Don't eagerly import pages** — the lazy loading is intentional
- **Don't merge vendor chunks** — the granular splitting is for cache efficiency
- **Don't add `@import url()` in CSS** — it's render-blocking
- **Don't import `eruda` at top level** — it's 479KB unminified, only for debug
- **Don't add heavy dependencies** without considering bundle impact
- **Don't use `JSON.stringify` for comparison** in hot paths (use shallow checks first)

---

## 9. MAINTENANCE PLAYBOOK

### 9.1 Adding a New Page

```
1. Create src/pages/NewPage/
   ├── index.ts       → export { NewPage } from './NewPage';
   ├── types.ts       → TypeScript interfaces
   ├── NewPage.tsx    → Component using TUI components
   └── NewPage.css    → Styles (if needed)

2. Add lazy import in src/components/App.tsx:
   const NewPage = lazy(() => import('../pages/NewPage/NewPage')
     .then(m => ({ default: m.NewPage })));

3. Add tab rendering:
   {activeTab === 'newpage' && <NewPage />}

4. Add tab to BottomNav (if needed):
   Update TABS array in src/components/BottomNav/BottomNav.tsx

5. Add TabId type:
   Update TabId union in src/types.ts

6. Verify: npx tsc --noEmit && npm run build
```

### 9.2 Adding Telegram SDK Features

```
1. Check if the feature exists in @telegram-apps/sdk-react v3.x
   → Search: https://docs.telegram-mini-apps.com/

2. Add the wrapper function in src/helpers/telegram.ts:
   export function myNewFeature(): void {
     try {
       if (sdkFunction.isAvailable()) {
         sdkFunction();
       }
     } catch { /* noop outside Telegram */ }
   }

3. If it needs mounting, add to src/init.ts Phase 2 (deferred):
   deferredTasks.push(
     (async () => {
       try { mountMyFeature.ifAvailable(); } catch { /* ignore */ }
     })()
   );

4. Use the helper in components:
   import { myNewFeature } from '../../helpers/telegram';
```

### 9.3 Updating Dependencies

```
⚠️ READ THIS BEFORE UPDATING:

@telegram-apps/sdk-react:
  - v3.x uses signal-based API (current)
  - BREAKING: v2 → v3 changed almost every import
  - After updating: run `npx tsc --noEmit` to catch API changes
  - Check init.ts, helpers/telegram.ts, App.tsx for breaking changes

@telegram-apps/telegram-ui:
  - Component props may change between minor versions
  - After updating: check all imports of TUI components
  - The CSS import path may change

React:
  - Currently React 18. React 19 has breaking changes.
  - If upgrading: check all useEffect patterns, Suspense behavior

Vite:
  - Major Vite upgrades may change plugin API
  - Check vite.config.ts compatibility after upgrading
```

### 9.4 Debugging in Telegram

```
1. Enable debug mode:
   - Add ?tgWebAppStartParam=platformer_debug to bot URL
   - Or: import.meta.env.DEV is true in dev mode

2. Eruda inspector loads on iOS/Android in debug mode:
   - Shows console, network, elements
   - Position: top-right corner

3. Mock environment (browser dev):
   - src/mockEnv.ts provides fake Telegram env
   - Simulates theme params, viewport, safe areas
   - User data is mock (id: 1, name: "Vladislav")

4. Common Telegram-specific issues:
   - Scroll not working → Check .scroll-wrapper CSS and touchstart handler
   - White flash on load → Check index.html inline CSS
   - App stuck loading → Check init.ts Phase 1 for errors
   - Haptics not working → Normal in dev mode (no Telegram env)
```

---

## 10. DECISION-MAKING FRAMEWORK

### 10.1 Before Any Change

```
┌─────────────────────────────────────────────────────────────────┐
│                    THINK → PLAN → ACT → VERIFY                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. UNDERSTAND: What does the user actually want?                │
│     ├── What they said (surface request)                         │
│     ├── What they mean (underlying goal)                         │
│     └── What would delight them (optimal outcome)                │
│                                                                  │
│  2. PLAN: What's the minimum change?                             │
│     ├── Which files change? (use Section 3 as map)               │
│     ├── What's the dependency order?                             │
│     ├── Could this break something? (check Section 8.3)          │
│     └── Is this a boot-path file? (check Section 4.3)            │
│                                                                  │
│  3. EXECUTE: Make the change                                     │
│     ├── One file at a time when risky                            │
│     ├── Batch when safe (CSS-only changes, etc.)                 │
│     └── Always use TypeScript — never `any` without reason       │
│                                                                  │
│  4. VERIFY: Confirm it works                                     │
│     ├── npx tsc --noEmit (must pass)                             │
│     ├── npm run build (must produce valid dist/)                 │
│     └── Test the actual feature manually                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 10.2 File Modification Decision Matrix

```
User wants to...              →  Modify these files:
────────────────────────────────────────────────────────────────────
Add a new page/tab            →  pages/[New]/*, App.tsx, BottomNav, types.ts
Change page logic             →  pages/[Page]/[Page].tsx
Change page styling           →  pages/[Page]/[Page].css
Add API endpoint              →  api.ts (or page-specific api.ts)
Add TypeScript types          →  types.ts (or page-specific types.ts)
Change global styles          →  src/index.css
Change build/deploy config    →  vite.config.ts
Change SDK initialization     →  src/init.ts (⚠️ read Section 4 first)
Add SDK feature wrapper       →  src/helpers/telegram.ts
Change mock dev environment   →  src/mockEnv.ts
Change global app state       →  src/context/AppContext.tsx
Change tab bar                →  components/BottomNav/BottomNav.tsx
Change first-paint experience →  index.html (⚠️ read Section 4.2 first)
```

### 10.3 Common Mistakes to Avoid

| Mistake | Why it's wrong | What to do instead |
|---------|---------------|-------------------|
| Adding `await` before `root.render()` | Delays first paint by hundreds of ms | Put it in Phase 2 of init.ts |
| Importing a page eagerly in App.tsx | Breaks code splitting, all JS loads upfront | Use `React.lazy()` with dynamic import |
| Calling SDK functions directly | Crashes outside Telegram, no error handling | Use helpers/telegram.ts wrappers |
| Using `@import url()` in CSS | Render-blocking, delays first paint | Use `<link>` in index.html with preload |
| Mutating React state directly | Component won't re-render | Always create new objects/arrays |
| Adding large dependencies | Increases bundle, slows mobile load | Check bundle impact first (`npm run build`) |
| Removing try/catch around SDK calls | App crashes on platforms where feature isn't available | Always wrap with try/catch or `.isAvailable()` |
| Using old SDK v1/v2 hook API | Doesn't exist in v3, build will fail | Use v3 signal-based imports |

---

## 11. API REFERENCE

### 11.1 SMM Panel API (`src/api.ts`)

Base URL: `VITE_API_URL` env var or `/api` fallback.

| Function | Method | Endpoint | Purpose |
|----------|--------|----------|---------|
| `authenticateTelegram(initData)` | POST | `/telegram_auth.php` | Auth with Telegram init data |
| `getServices(refresh?)` | GET | `/get_service.php` | Fetch service catalog |
| `getRecommended()` | GET | `/get_recommended.php` | Get featured service IDs |
| `placeOrder(payload)` | POST | `/process_order.php` | Place new order |
| `getOrders()` | GET | `/get_orders.php` | Get order history |
| `checkOrderStatus()` | GET | `/check_order_status.php` | Sync order statuses |
| `processDeposit(amount, ref)` | POST | `/deposit_handler.php` | Process deposit |
| `getDeposits()` | GET | `/get_deposits.php` | Get deposit history |
| `getAlerts()` | GET | `/get_alerts.php` | Get notifications |
| `sendChat(message)` | POST | `/chat_api.php` | Send support message |
| `heartbeat()` | GET | `/heartbeat.php` | Keep session alive |

### 11.2 Todo/Habit API (`src/pages/TodoPage/api.ts`)

Base URL: `VITE_API_URL` or `https://paxyo.com/backend/todos.php`

| Function | Method | Purpose |
|----------|--------|---------|
| `fetchTodos(userId)` | GET | Get all todos for user |
| `addTodo(userId, text, habit?, userName?)` | POST | Create new todo/habit |
| `updateTodo(userId, todoId, updates)` | PUT | Update todo text/habit |
| `deleteTodo(userId, todoId)` | DELETE | Delete a todo |

**Encoding:** Habit metadata is stored as `text ||| {"frequency":"daily","streak":0,"history":{}}` in the text field.

---

## 12. VERIFICATION CHECKLIST

Before saying "done" on any task:

```
□ npx tsc --noEmit passes (zero errors)
□ npm run build succeeds (produces valid dist/)
□ Feature works as requested
□ No regressions in existing features
□ Handles all states: loading, empty, error, success
□ Works on mobile viewport (320px+)
□ No console errors or warnings
□ SDK calls wrapped in try/catch with .isAvailable() guards
□ No render-blocking resources added
□ Lazy loading preserved (pages are React.lazy)
□ Boot sequence order preserved (Section 4)
```

---

## 13. REFERENCE DOCUMENTS

| Document | Location | When to read |
|----------|----------|-------------|
| This prompt | `AGENT_PROMPT.md` | Always — before any work |
| Quick reference | `CHEATSHEET.md` | For patterns and templates |
| Deep reasoning guide | `.agent/workflows/telegram-miniapp-guide.md` | For complex architectural decisions |
| Telegram SDK docs | https://docs.telegram-mini-apps.com/ | When adding SDK features |
| Telegram UI docs | https://telegram-mini-apps-ui.vercel.app/ | When using TUI components |

---

*AGENT_PROMPT v5.0 — Last updated: 2026-02-14 — Covers SDK v3.x, lazy loading, 2-phase init, production build optimizations*
