import { Link } from 'react-router-dom';
import { Plus } from 'lucide-react';
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
  const addItem = useCartStore((state) => state.addItem);

  const handleAddToCart = (e: React.MouseEvent, product: Product) => {
    e.preventDefault();
    e.stopPropagation();
    addItem(product);
    toast.success('Добавлено в корзину', {
      description: product.name,
      duration: 2000,
    });
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
        {products.map((product) => (
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
                <Button
                  size="sm"
                  onClick={(e) => handleAddToCart(e, product)}
                  className="shrink-0"
                  aria-label={`Добавить ${product.name} в корзину`}
                >
                  <Plus className="h-4 w-4" />
                  В корзину
                </Button>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </section>
  );
}
