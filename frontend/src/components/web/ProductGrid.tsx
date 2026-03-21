import { Link } from 'react-router-dom';
import { Plus, Minus } from 'lucide-react';
import { Product } from '@/types';
import { useCartStore } from '@/store/cartStore';
import { OptimizedImage } from '@/components/OptimizedImage';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface ProductGridProps {
  products: Product[];
  title?: string;
}

export function ProductGrid({ products, title }: ProductGridProps) {
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
      {title && (
        <h2 className="mb-8 text-2xl font-bold tracking-tight md:text-3xl">
          {title}
        </h2>
      )}
      <div
        className={cn(
          'grid gap-6',
          'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4'
        )}
      >
        {products.map((product) => {
          const cartItem = items.find((i) => i.product.id === product.id);
          const quantity = cartItem?.quantity ?? 0;

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
