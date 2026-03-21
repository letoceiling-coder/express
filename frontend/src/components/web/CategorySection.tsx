import { Category } from '@/types';
import { cn } from '@/lib/utils';

interface CategorySectionProps {
  categories: Category[];
  activeCategoryId: string | null;
  onCategoryChange: (id: string | null) => void;
}

/** Categories - overflow-x-auto flex-nowrap BOTH mobile and desktop. NO flex-wrap. */
export function CategorySection({
  categories,
  activeCategoryId,
  onCategoryChange,
}: CategorySectionProps) {
  return (
    <section id="categories" className="border-b border-border bg-background py-4">
      <div className="px-4 lg:px-0">
        <h2 className="mb-3 text-lg font-bold tracking-tight hidden lg:block">
          Категории
        </h2>
        <div className="overflow-x-auto scrollbar-hide">
          <div className="flex gap-2 w-max px-4 lg:px-0 flex-nowrap shrink-0">
            <button
              onClick={() => onCategoryChange(null)}
              className={cn(
                'shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                activeCategoryId === null
                  ? 'bg-primary text-primary-foreground'
                  : 'bg-muted text-muted-foreground hover:bg-muted/80'
              )}
            >
              Все
            </button>
            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => onCategoryChange(cat.id)}
                className={cn(
                  'shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors whitespace-nowrap',
                  activeCategoryId === cat.id
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground hover:bg-muted/80'
                )}
              >
                {cat.name}
              </button>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
