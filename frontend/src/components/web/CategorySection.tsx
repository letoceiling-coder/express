import { Category } from '@/types';
import { cn } from '@/lib/utils';

interface CategorySectionProps {
  categories: Category[];
  activeCategoryId: string | null;
  onCategoryChange: (id: string | null) => void;
}

export function CategorySection({
  categories,
  activeCategoryId,
  onCategoryChange,
}: CategorySectionProps) {
  return (
    <section id="categories" className="border-b border-border bg-background py-6">
      <div className="container mx-auto px-4 lg:px-8">
        <h2 className="mb-4 text-xl font-bold tracking-tight md:text-2xl">
          Категории
        </h2>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => onCategoryChange(null)}
            className={cn(
              'rounded-full px-4 py-2 text-sm font-medium transition-colors',
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
                'rounded-full px-4 py-2 text-sm font-medium transition-colors',
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
    </section>
  );
}
