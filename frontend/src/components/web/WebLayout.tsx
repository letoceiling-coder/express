import { Link, useLocation, useNavigate, Outlet } from 'react-router-dom';
import { CartProgressBar } from './CartProgressBar';
import { BottomNav } from './BottomNav';

export function WebLayout() {
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
    <div className="min-h-screen flex flex-col bg-background max-w-[480px] mx-auto">
      {/* Header */}
      <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="flex h-14 items-center justify-between px-4">
          {/* Logo */}
          <Link to="/" className="flex items-center gap-2">
            <span className="text-lg font-bold tracking-tight text-foreground">
              Свой Хлеб
            </span>
          </Link>

          <div className="flex items-center gap-1">
            <button
              type="button"
              onClick={() => scrollToSection('categories')}
              className="rounded-lg p-2 text-sm text-muted-foreground hover:text-foreground hover:bg-accent"
            >
              Категории
            </button>
          </div>
        </div>
      </header>

      {/* Main */}
      <main className="flex-1 pb-24">
        <Outlet />
      </main>

      {/* Bottom: Progress bar + Nav (fixed above bottom) */}
      <div className="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[480px] z-50">
        <CartProgressBar />
        <BottomNav />
      </div>

      {/* Footer - compact on mobile */}
      <footer className="border-t border-border bg-muted/30 mt-auto">
        <div className="px-4 py-6">
          <div className="grid gap-4 sm:grid-cols-2">
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
          <div className="mt-6 border-t border-border pt-6 text-center text-xs text-muted-foreground">
            © {new Date().getFullYear()} Свой Хлеб. Все права защищены.
          </div>
        </div>
      </footer>
    </div>
  );
}
