import { useNavigate, useLocation } from 'react-router-dom';
import { Home, ShoppingCart, ClipboardList } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useCartStore } from '@/store/cartStore';

const navItems = [
  { path: '/', icon: Home, label: 'Каталог' },
  { path: '/cart', icon: ShoppingCart, label: 'Корзина', showBadge: true },
  { path: '/orders', icon: ClipboardList, label: 'Заказы' },
];

export function BottomNavigation() {
  const navigate = useNavigate();
  const location = useLocation();
  const totalItems = useCartStore((state) => state.getTotalItems());

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-background safe-area-bottom">
      <div className="flex h-14 items-center justify-around">
        {navItems.map((item) => {
          const isActive = location.pathname === item.path || 
            (item.path === '/orders' && location.pathname.startsWith('/orders'));
          const Icon = item.icon;
          const showBadge = item.showBadge && totalItems > 0;

          return (
            <button
              key={item.path}
              onClick={() => navigate(item.path)}
              className={cn(
                'flex flex-1 flex-col items-center justify-center gap-0.5 h-full touch-feedback',
                isActive ? 'text-primary' : 'text-muted-foreground'
              )}
              aria-label={item.label}
            >
              <div className="relative">
                <Icon className="h-6 w-6" strokeWidth={isActive ? 2.5 : 2} />
                {showBadge && (
                  <span className="absolute -right-2.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground">
                    {totalItems > 99 ? '99+' : totalItems}
                  </span>
                )}
              </div>
              <span className={cn("text-[11px]", isActive && "font-semibold")}>{item.label}</span>
            </button>
          );
        })}
      </div>
    </nav>
  );
}
