# 🧙‍♂️ THE MAGIC SHEET
## *Everything You Need to Know on One Page*

---

## 🎯 WHAT TO DO FIRST

```
1. Read this sheet
2. If you need details → Read .agent/workflows/telegram-miniapp-guide.md
3. Follow the decision trees - they tell you exactly what to do
```

---

## 📁 PROJECT STRUCTURE (Universal)

```
src/
├── index.tsx          ← Entry (don't touch)
├── init.ts            ← SDK setup
├── mockEnv.ts         ← Fake Telegram for testing
├── navigation/
│   └── routes.tsx     ← ADD NEW PAGES HERE
├── components/        ← Reusable pieces
└── pages/             ← Each screen of the app
    └── [PageName]/
        ├── index.ts        ← export { PageName }
        ├── [PageName].tsx  ← Component
        ├── [PageName].css  ← Styles
        ├── api.ts          ← API calls (optional)
        └── types.ts        ← TypeScript types
```

---

## ⚡ COMMANDS

```bash
npm run dev          # Start dev server
npm run build        # Build for production
npm run deploy       # Deploy to GitHub Pages
```

---

## 🎨 STYLE DECISION

```
What style?
│
├── "Like Telegram" → Use @telegram-apps/telegram-ui
│
└── "Custom/Minimal" → Use plain HTML + CSS
    │
    └── Dark theme colors:
        Background:  #1a1a1a
        Secondary:   #222222
        Input bg:    #2a2a2a
        Border:      #444444
        Text:        #ffffff
        Hint:        #888888
        Accent:      #4fc3f7
        Danger:      #ff6b6b
```

---

## 👤 GET TELEGRAM USER

```typescript
import { useSignal, initData } from '@telegram-apps/sdk-react';

const state = useSignal(initData.state);
const userId = state?.user?.id;
const firstName = state?.user?.firstName;
```

---

## ➕ CREATE NEW PAGE

```
1. Create folder: src/pages/[PageName]/
2. Create files:
   - index.ts     → export { PageName } from './PageName';
   - types.ts     → Your interfaces
   - PageName.tsx → Your component
   - PageName.css → Your styles
3. Add to routes.tsx:
   - Import: import { PageName } from '@/pages/PageName';
   - Route: { path: '/page-name', Component: PageName }
```

---

## 🔄 API PATTERN

```typescript
// Optimistic update pattern:
const handleAction = async () => {
    const backup = data;           // 1. Save backup
    setData(newData);              // 2. Update UI immediately
    try {
        await apiCall();           // 3. Call API
    } catch (e) {
        setData(backup);           // 4. Revert on error
    }
};
```

---

## 🐛 COMMON FIXES

| Error | Fix |
|-------|-----|
| `Cannot find '@/...'` | Check tsconfig.json paths |
| Blank page in Telegram | Fix `base` in vite.config.ts |
| CORS error | Add CORS headers to PHP |
| initData undefined | mockEnv.ts should handle it |

---

## ✅ BEFORE YOU'RE DONE

```
□ No TypeScript errors
□ No console errors
□ Feature works
□ Existing features still work
□ Looks good on mobile
```

---

## 🎯 SIZING REFERENCE

```
Touch targets:  minimum 44px × 44px
Page title:     24px, bold
Body text:      15-16px
Secondary:      13-14px
Page padding:   16-20px horizontal
Item padding:   14px vertical
Border radius:  8px (medium), 50% (circle)
Transitions:    0.2s ease
```

---

## 📐 LAYOUT PATTERNS

```css
/* List */
.list { list-style: none; padding: 0; }
.item { padding: 14px 20px; border-bottom: 1px solid #2a2a2a; }

/* Grid */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
}

/* Fixed columns (tracker style) */
.fixed-grid {
  display: grid;
  grid-template-columns: 1fr repeat(5, 48px);
}
```

---

## 🔑 GOLDEN RULES

```
1. ALWAYS use absolute file paths
2. ALWAYS verify after changes
3. NEVER guess - look at existing code
4. SIMPLE is better than complex
5. Follow the decision frameworks - they have the answers
```

---

*When in doubt, read the full guide:*
*`.agent/workflows/telegram-miniapp-guide.md`*
