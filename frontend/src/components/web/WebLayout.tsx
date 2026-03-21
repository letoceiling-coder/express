import { Link, useLocation, useNavigate } from 'react-router-dom';
import { ShoppingCart, User } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { cn } from '@/lib/utils';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { CartProgressBar } from './CartProgressBar';
import { Outlet } from 'react-router-dom';

export function WebLayout() {
  const location = useLocation();
  const navigate = useNavigate();
  const totalItems = useCartStore((state) => state.getTotalItems());
  const isSearchPage = location.pathname === '/search';

  const scrollToSection = (sectionId: string) => {
    const el = document.getElementById(sectionId);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    } else if (location.pathname !== '/') {
      navigate(`/#${sectionId}`, { replace: true });
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-background w-full">
      {/* MOBILE: MiniAppHeader - 1:1 as MiniApp */}
      <header className="lg:hidden">
        <MiniAppHeader
          title={isSearchPage ? 'Поиск' : 'Свой Хлеб'}
          showBack={isSearchPage}
          showSearch={!isSearchPage}
        />
      </header>

      {/* DESKTOP: Original header - NOT touching */}
      <header className="hidden lg:block sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="w-full max-w-7xl mx-auto flex h-16 items-center justify-between px-8">
          <Link to="/" className="flex items-center gap-2">
            <span className="text-xl font-bold tracking-tight text-foreground">Свой Хлеб</span>
          </Link>

          <nav className="flex items-center gap-6">
            <Link
              to="/"
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Каталог
            </Link>
            <button
              type="button"
              onClick={() => scrollToSection('categories')}
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Категории
            </button>
            <button
              type="button"
              onClick={() => scrollToSection('benefits')}
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              Преимущества
            </button>
            <Link
              to="/about"
              className="text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
            >
              О нас
            </Link>
          </nav>

          <div className="flex items-center gap-3">
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

      {/* Main - mobile: no padding for full bleed, desktop: max-w container */}
      <main className="flex-1 pb-24 lg:pb-0">
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

      {/* MOBILE: Progress bar + BottomNav - EXACT as MiniApp */}
      <div className="lg:hidden fixed bottom-0 left-0 right-0 z-50">
        <CartProgressBar />
        <BottomNavigation />
      </div>
    </div>
  );
}
