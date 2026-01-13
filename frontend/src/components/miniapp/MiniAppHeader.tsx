import { ChevronLeft, Sun, Moon, Search } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useTheme } from '@/hooks/useTheme';
import { cn } from '@/lib/utils';

interface MiniAppHeaderProps {
  title?: string;
  showBack?: boolean;
  showSearch?: boolean;
  showThemeToggle?: boolean;
  className?: string;
}

export function MiniAppHeader({ 
  title = "Свой Хлеб", 
  showBack = false, 
  showSearch = true,
  showThemeToggle = true,
  className 
}: MiniAppHeaderProps) {
  const navigate = useNavigate();
  const { theme, toggleTheme } = useTheme();

  return (
    <header className={cn(
      "sticky top-0 z-50 flex h-14 items-center justify-between border-b border-border bg-background px-4 safe-area-top",
      className
    )}>
      <div className="flex items-center gap-1 min-w-[80px]">
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

      <h1 className="text-lg font-semibold text-foreground">{title}</h1>

      <div className="flex items-center gap-1 min-w-[80px] justify-end">
        {showSearch && (
          <button
            onClick={() => navigate('/search')}
            className="flex h-10 w-10 items-center justify-center rounded-lg touch-feedback -mr-2"
            aria-label="Поиск"
          >
            <Search className="h-6 w-6 text-foreground" />
          </button>
        )}
      </div>
    </header>
  );
}
