import { Link } from 'react-router-dom';
import { Plus, Minus, LayoutGrid, List } from 'lucide-react';
import { Product } from '@/types';
import { useCartStore } from '@/store/cartStore';
import { OptimizedImage } from '@/components/OptimizedImage';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { useState } from 'react';

interface ProductGridProps {
  products: Product[];
  title?: string;
}

export function ProductGrid({ products, title }: ProductGridProps) {
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const items = useCartStore((state) => state.items);
  const addItem = useCartStore((state) => state.addItem);
  const updateQuantity = useCartStore((state) => state.updateQuantity);

  const handleAddToCart = (e: React.MouseEvent, product: Product) => {
    e.preventDefault();
    e.stopPropagation();
    addItem(product);
    toast.success('Добавлено в корзину', {
      description: product.name,
      duration: 2000,
    });
  };

  const handleIncrement = (e: React.MouseEvent, product: Product) => {
    e.preventDefault();
    e.stopPropagation();
    addItem(product);
  };

  const handleDecrement = (e: React.MouseEvent, product: Product) => {
    e.preventDefault();
    e.stopPropagation();
    const cartItem = items.find((i) => i.product.id === product.id);
    const qty = cartItem?.quantity ?? 0;
    updateQuantity(product.id, qty - 1);
  };

  if (products.length === 0) {
    return (
      <div className="py-12 text-center text-muted-foreground">
        <p>Нет товаров в этой категории</p>
      </div>
    );
  }

  return (
    <section className="py-12">
      <div className="mb-6 flex items-center justify-between gap-4">
        {title ? (
          <h2 className="text-2xl font-bold tracking-tight md:text-3xl">
            {title}
          </h2>
        ) : (
          <div />
        )}
        <div className="inline-flex items-center rounded-lg border border-border bg-card p-1">
          <button
            type="button"
            onClick={() => setViewMode('grid')}
            className={cn(
              'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors',
              viewMode === 'grid'
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:bg-muted'
            )}
            aria-label="Режим сетки"
            title="Сетка"
          >
            <LayoutGrid className="h-4 w-4" />
            <span className="hidden sm:inline">Сетка</span>
          </button>
          <button
            type="button"
            onClick={() => setViewMode('list')}
            className={cn(
              'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors',
              viewMode === 'list'
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:bg-muted'
            )}
            aria-label="Режим списка"
            title="Список"
          >
            <List className="h-4 w-4" />
            <span className="hidden sm:inline">Список</span>
          </button>
        </div>
      </div>
      <div
        className={cn(
          viewMode === 'grid'
            ? 'grid gap-6 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4'
            : 'space-y-3'
        )}
      >
        {products.map((product) => {
          const cartItem = items.find((i) => i.product.id === product.id);
          const quantity = cartItem?.quantity ?? 0;

          if (viewMode === 'list') {
            return (
              <Link
                key={product.id}
                to={`/product/${product.id}`}
                className="group flex items-center gap-4 overflow-hidden rounded-xl border border-border bg-card p-3 transition-all hover:border-primary/30 hover:shadow-md"
              >
                <div className="h-20 w-20 shrink-0 overflow-hidden rounded-lg bg-muted">
                  <OptimizedImage
                    src={product.imageUrl || '/placeholder-image.jpg'}
                    webpSrc={product.webpUrl}
                    variants={product.imageVariants}
                    alt={product.name}
                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    size="thumbnail"
                    loading="lazy"
                  />
                </div>
                <div className="min-w-0 flex-1">
                  <h3 className="line-clamp-1 font-semibold text-foreground group-hover:text-primary transition-colors">
                    {product.name}
                  </h3>
                  <p className="mt-1 line-clamp-1 text-sm text-muted-foreground">
                    {product.description}
                  </p>
                  <div className="mt-2 flex items-center gap-2">
                    <span className="text-base font-bold text-primary">
                      {product.price.toLocaleString('ru-RU')} ₽
                    </span>
                    {product.isWeightProduct && (
                      <span className="text-xs text-muted-foreground">/ ед.</span>
                    )}
                  </div>
                </div>
                <div
                  className="shrink-0"
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                  }}
                >
                  {quantity > 0 ? (
                    <div className="flex items-center gap-1">
                      <Button
                        size="icon"
                        variant="outline"
                        className="h-8 w-8"
                        onClick={(e) => handleDecrement(e, product)}
                        aria-label="Уменьшить"
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="w-7 text-center text-sm font-semibold tabular-nums">
                        {quantity}
                      </span>
                      <Button
                        size="icon"
                        variant="outline"
                        className="h-8 w-8"
                        onClick={(e) => handleIncrement(e, product)}
                        aria-label="Увеличить"
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                  ) : (
                    <Button
                      size="sm"
                      onClick={(e) => handleAddToCart(e, product)}
                      className="h-8 px-3"
                      aria-label={`Добавить ${product.name} в корзину`}
                    >
                      <Plus className="mr-1 h-4 w-4" />
                      Добавить
                    </Button>
                  )}
                </div>
              </Link>
            );
          }

          return (
            <Link
              key={product.id}
              to={`/product/${product.id}`}
              className="group flex flex-col overflow-hidden rounded-xl border border-border bg-card transition-all hover:border-primary/30 hover:shadow-lg"
            >
              <div className="relative aspect-square overflow-hidden bg-muted">
                <OptimizedImage
                  src={product.imageUrl || '/placeholder-image.jpg'}
                  webpSrc={product.webpUrl}
                  variants={product.imageVariants}
                  alt={product.name}
                  className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                  size="medium"
                  loading="lazy"
                />
              </div>
              <div className="flex flex-1 flex-col p-4">
                <h3 className="line-clamp-2 font-semibold text-foreground group-hover:text-primary transition-colors">
                  {product.name}
                </h3>
                <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                  {product.description}
                </p>
                <div className="mt-auto flex items-center justify-between gap-3 pt-4">
                  <span className="text-lg font-bold text-primary">
                    {product.price.toLocaleString('ru-RU')} ₽
                    {product.isWeightProduct && (
                      <span className="text-sm font-normal text-muted-foreground"> / ед.</span>
                    )}
                  </span>
                  {quantity > 0 ? (
                    <div
                      className="flex shrink-0 items-center gap-1"
                      onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                      }}
                    >
                      <Button
                        size="icon"
                        variant="outline"
                        className="h-8 w-8"
                        onClick={(e) => handleDecrement(e, product)}
                        aria-label="Уменьшить"
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="w-8 text-center text-sm font-semibold tabular-nums">
                        {quantity}
                      </span>
                      <Button
                        size="icon"
                        variant="outline"
                        className="h-8 w-8"
                        onClick={(e) => handleIncrement(e, product)}
                        aria-label="Увеличить"
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                  ) : (
                    <Button
                      size="sm"
                      onClick={(e) => handleAddToCart(e, product)}
                      className="shrink-0"
                      aria-label={`Добавить ${product.name} в корзину`}
                    >
                      <Plus className="h-4 w-4" />
                      Добавить
                    </Button>
                  )}
                </div>
              </div>
            </Link>
          );
        })}
      </div>
    </section>
  );
}
