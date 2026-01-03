import { Plus, Minus } from 'lucide-react';
import { Product } from '@/types';
import { useCartStore } from '@/store/cartStore';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface ProductCardProps {
  product: Product;
  onClick?: () => void;
  variant?: 'grid' | 'list';
}

export function ProductCard({ product, onClick, variant = 'grid' }: ProductCardProps) {
  const { items, addItem, updateQuantity } = useCartStore();
  const cartItem = items.find((item) => item.product.id === product.id);
  const quantity = cartItem?.quantity || 0;

  const handleAddToCart = (e: React.MouseEvent) => {
    e.stopPropagation();
    addItem(product);
    toast.success('Добавлено в корзину', {
      description: product.name,
      duration: 2000,
    });
  };

  const handleIncrement = (e: React.MouseEvent) => {
    e.stopPropagation();
    addItem(product);
  };

  const handleDecrement = (e: React.MouseEvent) => {
    e.stopPropagation();
    updateQuantity(product.id, quantity - 1);
  };

  if (variant === 'list') {
    return (
      <div
        className="flex cursor-pointer gap-3 border-b border-border py-3 transition-colors touch-feedback animate-fade-in"
        onClick={onClick}
      >
        {/* Product Image */}
        <div className="h-[88px] w-[88px] flex-shrink-0 overflow-hidden rounded-xl bg-muted">
          <img
            src={product.imageUrl}
            alt={product.name}
            className="h-full w-full object-cover"
            loading="lazy"
          />
        </div>

        {/* Product Info */}
        <div className="flex flex-1 flex-col justify-between min-w-0">
          <div>
            <h3 className="line-clamp-2 text-sm font-semibold leading-tight text-foreground">
              {product.name}
            </h3>
            <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
              {product.description}
            </p>
          </div>
          <div className="flex items-center">
            <span className="text-base font-bold text-primary">
              {product.price.toLocaleString('ru-RU')} ₽
            </span>
            {product.isWeightProduct && (
              <span className="ml-1 text-xs text-muted-foreground">/ед.</span>
            )}
          </div>
        </div>

        {/* Add to Cart Button */}
        <div className="flex flex-shrink-0 items-center">
          {quantity > 0 ? (
            <div className="flex items-center gap-1">
              <button
                onClick={handleDecrement}
                className="flex h-9 w-9 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
                aria-label="Уменьшить"
              >
                <Minus className="h-4 w-4" />
              </button>
              <span className="w-8 text-center text-sm font-semibold">{quantity}</span>
              <button
                onClick={handleIncrement}
                className="flex h-9 w-9 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
                aria-label="Увеличить"
              >
                <Plus className="h-4 w-4" />
              </button>
            </div>
          ) : (
            <button
              onClick={handleAddToCart}
              className="flex h-9 w-9 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback hover:bg-primary hover:text-primary-foreground transition-colors"
              aria-label="Добавить в корзину"
            >
              <Plus className="h-5 w-5" />
            </button>
          )}
        </div>
      </div>
    );
  }

  // Grid variant (2-column card)
  return (
    <div
      className="flex flex-col cursor-pointer rounded-xl bg-card border border-border card-shadow overflow-hidden touch-feedback animate-fade-in"
      onClick={onClick}
    >
      {/* Product Image - Square 1:1 */}
      <div className="relative aspect-square w-full bg-muted">
        <img
          src={product.imageUrl}
          alt={product.name}
          className="h-full w-full object-cover"
          loading="lazy"
        />
      </div>

      {/* Product Info */}
      <div className="flex flex-1 flex-col p-3">
        <h3 className="line-clamp-2 text-sm font-semibold leading-tight text-foreground min-h-[2.5rem]">
          {product.name}
        </h3>
        <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
          {product.description}
        </p>
        
        <div className="mt-auto pt-3 flex items-center justify-between">
          <span className="text-base font-bold text-primary">
            {product.price.toLocaleString('ru-RU')} ₽
          </span>
          
          {quantity > 0 ? (
            <div className="flex items-center gap-0.5">
              <button
                onClick={handleDecrement}
                className="flex h-9 w-9 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
                aria-label="Уменьшить"
              >
                <Minus className="h-4 w-4" />
              </button>
              <span className="w-6 text-center text-sm font-semibold">{quantity}</span>
              <button
                onClick={handleIncrement}
                className="flex h-9 w-9 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
                aria-label="Увеличить"
              >
                <Plus className="h-4 w-4" />
              </button>
            </div>
          ) : (
            <button
              onClick={handleAddToCart}
              className="flex h-9 items-center justify-center gap-1 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground touch-feedback hover:opacity-90 transition-opacity"
              aria-label="Добавить в корзину"
            >
              <Plus className="h-4 w-4" />
              <span>Добавить</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
