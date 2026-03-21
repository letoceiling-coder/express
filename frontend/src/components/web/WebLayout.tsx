import { Link, useLocation, useNavigate } from 'react-router-dom';
import { ShoppingCart, User, Search } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { useSearchStore } from '@/store/searchStore';
import { cn } from '@/lib/utils';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { CartProgressBar } from './CartProgressBar';
import { DeliveryModeToggle } from '@/components/miniapp/DeliveryModeToggle';
import { useOrderModeStore } from '@/store/orderModeStore';
import { Outlet } from 'react-router-dom';

export function WebLayout() {
  const location = useLocation();
  const navigate = useNavigate();
  const totalItems = useCartStore((state) => state.getTotalItems());
  const { query, setQuery } = useSearchStore();
  const { orderMode, setOrderMode } = useOrderModeStore();
  const isSearchPage = location.pathname === '/search';

  // Runtime safety: guard against undefined orderMode (should never happen with store)
  if (orderMode === undefined) {
    console.error('orderMode is undefined in WebLayout');
  }

  return (
    <div className="min-h-screen flex flex-col bg-background w-full">
      {/* MOBILE: MiniAppHeader - FIXED, 1:1 as MiniApp, search IN header */}
      <header className="lg:hidden">
        <MiniAppHeader
          title={isSearchPage ? 'Поиск' : 'Свой Хлеб'}
          showBack={isSearchPage}
          showSearch={location.pathname === '/' || location.pathname === '/search'}
          fixed
        />
      </header>

      {/* DESKTOP: Header with search inside */}
      <header className="hidden lg:block sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="w-full max-w-7xl mx-auto flex h-16 items-center gap-6 px-8">
          <Link to="/" className="shrink-0">
            <span className="text-xl font-bold tracking-tight text-foreground">Свой Хлеб</span>
          </Link>

          <div className="flex flex-1 min-w-0 items-center gap-2 max-w-md">
            <Search className="h-4 w-4 shrink-0 text-muted-foreground" />
            <input
              type="text"
              placeholder="Поиск товаров..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              onFocus={() => location.pathname !== '/search' && navigate('/search')}
              className="flex-1 min-w-0 bg-muted rounded-xl px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
            />
          </div>

          <div className="w-44 shrink-0">
            <DeliveryModeToggle
              value={orderMode ?? 'pickup'}
              onChange={setOrderMode ?? (() => {})}
              className="py-0 px-0"
            />
          </div>

          <nav className="flex items-center gap-6 shrink-0">
            <Link
              to="/"
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Каталог
            </Link>
            <Link
              to="/about"
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              О нас
            </Link>
          </nav>

          <div className="flex items-center gap-3 shrink-0">
            <Link
              to="/cart"
              className={cn(
                'relative flex items-center justify-center rounded-lg p-2',
                'text-muted-foreground hover:text-foreground hover:bg-accent transition-colors'
              )}
              aria-label="Корзина"
            >
              <ShoppingCart className="h-5 w-5" />
              {totalItems > 0 && (
                <span className="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">
                  {totalItems > 99 ? '99+' : totalItems}
                </span>
              )}
            </Link>
            <Link
              to="/orders"
              className="flex items-center justify-center rounded-lg p-2 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
              aria-label="Заказы"
            >
              <User className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </header>

      {/* Main - mobile: pt-14 for fixed header; pb for bottom nav/progress bar */}
      <main className="flex-1 pb-24 lg:pb-14 pt-14 lg:pt-0">
        <div className="w-full lg:max-w-7xl lg:mx-auto px-0 lg:px-8">
          <Outlet />
        </div>
      </main>

      {/* DESKTOP: Footer - hidden on mobile */}
      <footer className="hidden lg:block border-t border-border bg-muted/30 mt-auto">
        <div className="w-full max-w-7xl mx-auto px-8 py-12">
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <h3 className="mb-4 text-sm font-semibold text-foreground">Свой Хлеб</h3>
              <p className="text-sm text-muted-foreground">
                Свежая выпечка и качественные продукты с доставкой и самовывозом.
              </p>
            </div>
            <div>
              <h3 className="mb-4 text-sm font-semibold text-foreground">Навигация</h3>
              <ul className="space-y-2 text-sm text-muted-foreground">
                <li>
                  <Link to="/" className="hover:text-foreground transition-colors">
                    Каталог
                  </Link>
                </li>
                <li>
                  <Link to="/about" className="hover:text-foreground transition-colors">
                    О нас
                  </Link>
                </li>
                <li>
                  <Link to="/orders" className="hover:text-foreground transition-colors">
                    Мои заказы
                  </Link>
                </li>
                <li>
                  <Link to="/legal-documents" className="hover:text-foreground transition-colors">
                    Документы
                  </Link>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="mb-4 text-sm font-semibold text-foreground">Контакты</h3>
              <p className="text-sm text-muted-foreground">
                Заказы принимаем через Telegram и на сайте.
              </p>
            </div>
            <div>
              <h3 className="mb-4 text-sm font-semibold text-foreground">Доставка</h3>
              <p className="text-sm text-muted-foreground">
                Быстрая доставка по городу. Самовывоз из пекарни.
              </p>
            </div>
          </div>
          <div className="mt-6 border-t border-border pt-6 text-center text-xs text-muted-foreground">
            © {new Date().getFullYear()} Свой Хлеб. Все права защищены.
          </div>
        </div>
      </footer>

      {/* Progress bar (when delivery) + BottomNav (mobile only) */}
      <div className="fixed bottom-0 left-0 right-0 z-50">
        <CartProgressBar />
        <div className="lg:hidden">
          <BottomNavigation />
        </div>
      </div>
    </div>
  );
}
