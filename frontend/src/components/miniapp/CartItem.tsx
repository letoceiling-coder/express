import { Minus, Plus, Trash2 } from 'lucide-react';
import { CartItem as CartItemType } from '@/types';
import { useCartStore } from '@/store/cartStore';

interface CartItemProps {
  item: CartItemType;
}

export function CartItem({ item }: CartItemProps) {
  const { updateQuantity, removeItem } = useCartStore();

  return (
    <div className="flex gap-3 rounded-lg border border-border bg-card p-3 card-shadow animate-fade-in">
      {/* Product Image */}
      <div className="h-[60px] w-[60px] flex-shrink-0 overflow-hidden rounded-lg bg-muted">
        <img
          src={item.product.imageUrl}
          alt={item.product.name}
          className="h-full w-full object-cover"
        />
      </div>

      {/* Product Info */}
      <div className="flex flex-1 flex-col justify-between min-w-0">
        <div className="flex items-start justify-between gap-2">
          <h3 className="line-clamp-2 text-sm font-semibold leading-tight text-foreground">
            {item.product.name}
          </h3>
          <button
            onClick={() => removeItem(item.product.id)}
            className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-muted-foreground hover:bg-destructive/10 hover:text-destructive touch-feedback"
            aria-label="Удалить"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>

        <div className="flex items-center justify-between mt-2">
          {/* Quantity Controls */}
          <div className="flex items-center gap-1">
            <button
              onClick={() => updateQuantity(item.product.id, item.quantity - 1)}
              className="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
              aria-label="Уменьшить"
            >
              <Minus className="h-3.5 w-3.5" />
            </button>
            <span className="w-8 text-center text-sm font-medium">
              {item.quantity}
            </span>
            <button
              onClick={() => updateQuantity(item.product.id, item.quantity + 1)}
              className="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
              aria-label="Увеличить"
            >
              <Plus className="h-3.5 w-3.5" />
            </button>
          </div>

          {/* Total Price */}
          <span className="text-base font-bold text-primary">
            {(item.product.price * item.quantity).toLocaleString('ru-RU')} ₽
          </span>
        </div>
      </div>
    </div>
  );
}
