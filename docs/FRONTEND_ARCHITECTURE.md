# FRONTEND ARCHITECTURE

**Date:** 2026-03-21  
**Source:** Verified from `frontend/src` structure and App.tsx

---

## Entry Point

| File | Purpose |
|------|---------|
| `frontend/src/main.tsx` | React entry: `createRoot`, renders `<App />`, calls `initTelegramWebApp()` |
| `frontend/src/App.tsx` | Router and layout selection |

---

## Routing

- **Library:** React Router DOM (`BrowserRouter`, `Routes`, `Route`)
- **Logic:** `isTelegramWebApp()` from `@/lib/telegram` decides Web vs MiniApp
  - **Telegram:** `window.location.hash` contains `tgWebAppData` AND `window.Telegram?.WebApp` exists
  - **Web:** Otherwise

### Routes by Context

**When NOT in Telegram (Web):**
- Layout: `WebLayout` (outlet)
- Routes: `/`, `/product/:productId`, `/cart`, `/orders`, `/orders/:orderId`, `/about`, `/search`, `/checkout`, `/legal-documents`
- Redirect: `/order-success/:orderId` → `/`

**When in Telegram (MiniApp):**
- No shared layout wrapper; each page may use `MiniAppLayout` internally
- Routes: `/`, `/search`, `/product/:productId`, `/cart`, `/checkout`, `/order-success/:orderId`, `/orders`, `/orders/:orderId`, `/about`, `/legal-documents`, `/call`

**Admin (shared):**
- Layout: `AdminLayout`
- Path: `/admin/*`

---

## Layouts

| Layout | File | Used Where |
|--------|------|------------|
| **WebLayout** | `frontend/src/components/web/WebLayout.tsx` | Web only (when !inTelegram) |
| **MiniAppLayout** | `frontend/src/components/miniapp/MiniAppLayout.tsx` | Wraps individual MiniApp pages (e.g. SearchPage, etc.) |
| **AdminLayout** | `frontend/src/components/admin/AdminLayout.tsx` | Admin panel |

**WebLayout structure:**
- Mobile (`lg:hidden`): `MiniAppHeader` (fixed), main, `CartProgressBar`, `BottomNavigation`
- Desktop (`lg:block`): Custom header (search, DeliveryModeToggle, nav), main, footer
- Uses: `MiniAppHeader`, `BottomNavigation`, `CartProgressBar`, `DeliveryModeToggle`, `Outlet`

---

## Pages

| Page | File | Context |
|------|------|---------|
| HomePage | `pages/web/HomePage.tsx` | Web |
| WebProductDetailPage | `pages/web/WebProductDetailPage.tsx` | Web |
| WebCartPage | `pages/web/WebCartPage.tsx` | Web |
| WebOrdersPage | `pages/web/WebOrdersPage.tsx` | Web |
| WebOrderDetailPage | `pages/web/WebOrderDetailPage.tsx` | Web |
| WebAboutPage | `pages/web/WebAboutPage.tsx` | Web |
| WebSearchPage | `pages/web/WebSearchPage.tsx` | Web |
| WebCheckoutPage | `pages/web/WebCheckoutPage.tsx` | Web |
| WebLegalDocumentsPage | `pages/web/WebLegalDocumentsPage.tsx` | Web |
| CatalogPage | `pages/miniapp/CatalogPage.tsx` | MiniApp |
| SearchPage | `pages/miniapp/SearchPage.tsx` | MiniApp |
| ProductDetailPage | `pages/miniapp/ProductDetailPage.tsx` | MiniApp |
| CartPage | `pages/miniapp/CartPage.tsx` | MiniApp |
| CheckoutPage | `pages/miniapp/CheckoutPage.tsx` | MiniApp |
| OrderSuccessPage | `pages/miniapp/OrderSuccessPage.tsx` | MiniApp |
| OrdersPage | `pages/miniapp/OrdersPage.tsx` | MiniApp |
| OrderDetailPage | `pages/miniapp/OrderDetailPage.tsx` | MiniApp |
| AboutPage | `pages/miniapp/AboutPage.tsx` | MiniApp |
| LegalDocumentsPage | `pages/miniapp/LegalDocumentsPage.tsx` | MiniApp |
| CallPage | `pages/miniapp/CallPage.tsx` | MiniApp |
| NotFound | `pages/NotFound.tsx` | Fallback |

---

## Components (Actually Used)

| Component | File | Used In |
|-----------|------|---------|
| **MiniAppHeader** | `components/miniapp/MiniAppHeader.tsx` | WebLayout (mobile), MiniAppLayout |
| **BottomNavigation** | `components/miniapp/BottomNavigation.tsx` | WebLayout (mobile), MiniAppLayout |
| **CartProgressBar** | `components/web/CartProgressBar.tsx` | WebLayout |
| **DeliveryModeToggle** | `components/miniapp/DeliveryModeToggle.tsx` | WebLayout (desktop), MiniApp pages |
| **CategorySection** | `components/web/CategorySection.tsx` | HomePage (assumed) |
| **SearchInput** | `components/web/SearchInput.tsx` | Possibly in search flow |
| **ProductGrid** | `components/web/ProductGrid.tsx` | Catalog / Home |
| **AuthModal** | `components/web/AuthModal.tsx` | Web checkout flow |
| **HeroSlider** | `components/web/HeroSlider.tsx` | HomePage |
| **Benefits** | `components/web/Benefits.tsx` | HomePage |
| **ProductCard** | `components/miniapp/ProductCard.tsx` | Catalog, search |
| **CategoryTabs** | `components/miniapp/CategoryTabs.tsx` | Catalog |

---

## STEP 4 — MiniApp vs Web Split

### Shared Components
- `MiniAppHeader` — used in **both** Web (mobile) and MiniApp
- `BottomNavigation` — used in **both** Web (mobile) and MiniApp
- `DeliveryModeToggle` — used in Web (desktop) and MiniApp
- `ProductCard`, `CategoryTabs` — MiniApp-specific but similar concepts may exist for Web

### Separate Components
- **Web:** `WebLayout`, `CartProgressBar`, `CategorySection`, `SearchInput`, `ProductGrid`, `AuthModal`, `HeroSlider`, `Benefits`
- **MiniApp:** `MiniAppLayout`, `CartItem`, `OrderCard`, `DeliveryProgressIndicator`

### Which UI Is Active

| Context | Desktop | Mobile |
|---------|---------|--------|
| **Web** (browser, no tgWebAppData) | WebLayout with custom header, search, toggle | WebLayout with MiniAppHeader + BottomNav (MiniApp-style) |
| **MiniApp** (Telegram) | N/A (typically mobile) | MiniApp pages with MiniAppLayout |

### Separation or Chaos
- **Clear separation:** Web and MiniApp have separate page sets
- **Shared UI on Web mobile:** Web mobile reuses MiniAppHeader and BottomNav for consistency
- **State:** `searchStore`, `cartStore`, `orderModeStore`, `authStore` — shared across Web and MiniApp when same session
