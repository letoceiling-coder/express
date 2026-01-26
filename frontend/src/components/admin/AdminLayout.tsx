import { useState } from 'react';
import { NavLink, Outlet } from 'react-router-dom';
import {
  LayoutDashboard,
  ClipboardList,
  Package,
  Tags,
  LogOut,
  Menu,
  X,
  Sun,
  Moon,
  CreditCard,
  Truck,
  Info,
  FileText,
  Bell,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useTheme } from '@/hooks/useTheme';
import { Button } from '@/components/ui/button';

const navItems = [
  { path: '/admin', icon: LayoutDashboard, label: 'Главная', end: true },
  { path: '/admin/orders', icon: ClipboardList, label: 'Заказы' },
  { path: '/admin/products', icon: Package, label: 'Товары' },
  { path: '/admin/categories', icon: Tags, label: 'Категории' },
  { path: '/admin/about', icon: Info, label: 'О нас' },
  { path: '/admin/notifications', icon: Bell, label: 'Уведомления' },
  { path: '/admin/settings/payments/yookassa', icon: CreditCard, label: 'ЮKassa' },
  { path: '/admin/settings/delivery', icon: Truck, label: 'Доставка' },
];

export function AdminLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { theme, toggleTheme } = useTheme();

  return (
    <div className="flex min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
      {/* Mobile Header */}
      <header className="fixed top-0 left-0 right-0 z-50 flex h-14 items-center justify-between border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 lg:hidden">
        <button
          onClick={() => setSidebarOpen(true)}
          className="rounded-lg p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
        >
          <Menu className="h-6 w-6" />
        </button>
        <h1 className="text-lg font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
          Свой Хлеб
        </h1>
        <Button
          variant="ghost"
          size="icon"
          onClick={toggleTheme}
          className="text-slate-600 dark:text-slate-300"
        >
          {theme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
        </Button>
      </header>

      {/* Mobile Sidebar Overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-50 bg-black/50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          'fixed left-0 top-0 z-50 h-screen w-64 border-r border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm transition-transform duration-300 lg:translate-x-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        )}
      >
        <div className="flex h-16 items-center justify-between border-b border-slate-100 dark:border-slate-800 px-6">
          <h1 className="text-xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
            Свой Хлеб
          </h1>
          <button
            onClick={() => setSidebarOpen(false)}
            className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <nav className="space-y-1 p-4">
          {navItems.map((item) => {
            const Icon = item.icon;
            return (
              <NavLink
                key={item.path}
                to={item.path}
                end={item.end}
                onClick={() => setSidebarOpen(false)}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200',
                    isActive
                      ? 'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-md shadow-emerald-200 dark:shadow-emerald-900/30'
                      : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'
                  )
                }
              >
                <Icon className="h-5 w-5" />
                {item.label}
              </NavLink>
            );
          })}
        </nav>

        {/* Theme Toggle - Desktop */}
        <div className="hidden lg:block px-4 py-2">
          <Button
            variant="outline"
            onClick={toggleTheme}
            className="w-full justify-start gap-3 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300"
          >
            {theme === 'dark' ? (
              <>
                <Sun className="h-5 w-5" />
                Светлая тема
              </>
            ) : (
              <>
                <Moon className="h-5 w-5" />
                Тёмная тема
              </>
            )}
          </Button>
        </div>

        <div className="absolute bottom-0 left-0 right-0 border-t border-slate-100 dark:border-slate-800 p-4">
          <NavLink
            to="/"
            className="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 transition-all duration-200 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200"
          >
            <LogOut className="h-5 w-5" />
            Выйти в Mini App
          </NavLink>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 pt-14 lg:pt-0 lg:ml-64">
        <Outlet />
      </main>
    </div>
  );
}
