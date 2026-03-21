import { ReactNode } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { ShoppingCart, User } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { cn } from '@/lib/utils';

interface WebLayoutProps {
  children: ReactNode;
}

export function WebLayout({ children }: WebLayoutProps) {
  const totalItems = useCartStore((state) => state.getTotalItems());
  const location = useLocation();
  const navigate = useNavigate();

  const scrollToSection = (sectionId: string) => {
    const el = document.getElementById(sectionId);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth' });
    } else if (location.pathname !== '/') {
      navigate(`/#${sectionId}`, { replace: true });
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-background">
      {/* Header */}
      <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container mx-auto flex h-16 items-center justify-between px-4 lg:px-8">
          {/* Logo */}
          <Link to="/" className="flex items-center gap-2">
            <span className="text-xl font-bold tracking-tight text-foreground">
              Свой Хлеб
            </span>
          </Link>

          {/* Nav */}
          <nav className="hidden md:flex items-center gap-6">
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

          {/* Actions */}
          <div className="flex items-center gap-3">
            <Link
              to="/cart"
              className={cn(
                "relative flex items-center justify-center rounded-lg p-2",
                "text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
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
              aria-label="Профиль / Заказы"
            >
              <User className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </header>

      {/* Main */}
      <main className="flex-1">{children}</main>

      {/* Footer */}
      <footer className="border-t border-border bg-muted/30">
        <div className="container mx-auto px-4 py-12 lg:px-8">
          <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div>
              <h3 className="mb-4 text-sm font-semibold text-foreground">Свой Хлеб</h3>
              <p className="text-sm text-muted-foreground">
                Свежая выпечка и качественные продукты с доставкой и самовывозом.
              </p>
            </div>
            <div>
              <h3 className="mb-4 text-sm font-semibold text-foreground">Навигация</h3>
              <ul className="space-y-2 text-sm text-muted-foreground">
                <li><Link to="/" className="hover:text-foreground transition-colors">Каталог</Link></li>
                <li><Link to="/about" className="hover:text-foreground transition-colors">О нас</Link></li>
                <li><Link to="/orders" className="hover:text-foreground transition-colors">Мои заказы</Link></li>
                <li><Link to="/legal-documents" className="hover:text-foreground transition-colors">Документы</Link></li>
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
          <div className="mt-8 border-t border-border pt-8 text-center text-sm text-muted-foreground">
            © {new Date().getFullYear()} Свой Хлеб. Все права защищены.
          </div>
        </div>
      </footer>
    </div>
  );
}
