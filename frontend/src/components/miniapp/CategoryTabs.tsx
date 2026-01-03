import { useRef } from 'react';
import { cn } from '@/lib/utils';
import { Category } from '@/types';

interface CategoryTabsProps {
  categories: Category[];
  activeCategory: string | null;
  onCategoryChange: (categoryId: string | null) => void;
}

export function CategoryTabs({
  categories,
  activeCategory,
  onCategoryChange,
}: CategoryTabsProps) {
  const scrollRef = useRef<HTMLDivElement>(null);

  return (
    <div
      ref={scrollRef}
      className="scrollbar-hide flex gap-2 overflow-x-auto px-4 py-2.5"
    >
      <button
        onClick={() => onCategoryChange(null)}
        className={cn(
          'flex-shrink-0 rounded-full px-4 h-9 text-sm font-medium transition-all duration-200 touch-feedback border',
          activeCategory === null
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background text-foreground border-border hover:border-primary/50'
        )}
      >
        Все
      </button>
      {categories.map((category) => (
        <button
          key={category.id}
          onClick={() => onCategoryChange(category.id)}
          className={cn(
            'flex-shrink-0 rounded-full px-4 h-9 text-sm font-medium transition-all duration-200 touch-feedback whitespace-nowrap border',
            activeCategory === category.id
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background text-foreground border-border hover:border-primary/50'
          )}
        >
          {category.name}
        </button>
      ))}
    </div>
  );
}
