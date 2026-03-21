import { ChevronLeft, Sun, Moon, Search } from 'lucide-react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useTheme } from '@/hooks/useTheme';
import { cn } from '@/lib/utils';
import { useSearchStore } from '@/store/searchStore';

interface MiniAppHeaderProps {
  title?: string;
  showBack?: boolean;
  showSearch?: boolean;
  showThemeToggle?: boolean;
  fixed?: boolean;
  className?: string;
}

export function MiniAppHeader({
  title = "Свой Хлеб",
  showBack = false,
  showSearch = true,
  showThemeToggle = true,
  fixed = false,
  className
}: MiniAppHeaderProps) {
  const navigate = useNavigate();
  const { theme, toggleTheme } = useTheme();
  const { query, setQuery } = useSearchStore();

  return (
    <header className={cn(
      "z-50 flex h-14 items-center justify-between gap-2 border-b border-border bg-background px-4 safe-area-top",
      fixed ? "fixed top-0 left-0 right-0" : "sticky top-0",
      className
    )}>
      <div className="flex items-center shrink-0">
        {showBack ? (
          <button
            onClick={() => navigate(-1)}
            className="flex h-10 w-10 items-center justify-center rounded-lg touch-feedback text-foreground -ml-2"
            aria-label="Назад"
          >
            <ChevronLeft className="h-6 w-6" />
          </button>
        ) : showThemeToggle && (
          <button
            onClick={toggleTheme}
            className="flex h-10 w-10 items-center justify-center rounded-lg touch-feedback text-muted-foreground hover:text-foreground -ml-2"
            aria-label={theme === 'dark' ? 'Светлая тема' : 'Тёмная тема'}
          >
            {theme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
          </button>
        )}
      </div>

      {showSearch ? (
        <div className="flex flex-1 min-w-0 items-center gap-2">
          <Search className="h-4 w-4 shrink-0 text-muted-foreground" />
          <input
            type="text"
            placeholder="Поиск товаров..."
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onFocus={handleSearchFocus}
            className="flex-1 min-w-0 bg-muted rounded-xl px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
      ) : (
        <h1 className="flex-1 text-lg font-semibold text-foreground truncate text-center">{title}</h1>
      )}
    </header>
  );
}
