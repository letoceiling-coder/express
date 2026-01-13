import { useNavigate } from 'react-router-dom';
import { cn } from '@/lib/utils';
import { ShoppingCart } from 'lucide-react';

interface DeliveryProgressBarProps {
  cartTotal: number;
  minDeliveryTotal: number;
  onNavigateToCart?: () => void;
  className?: string;
}

export function DeliveryProgressBar({
  cartTotal,
  minDeliveryTotal,
  onNavigateToCart,
  className,
}: DeliveryProgressBarProps) {
  const navigate = useNavigate();
  
  const progress = Math.min(cartTotal / minDeliveryTotal, 1);
  const remaining = Math.max(0, minDeliveryTotal - cartTotal);
  const isComplete = cartTotal >= minDeliveryTotal;

  const handleClick = () => {
    if (onNavigateToCart) {
      onNavigateToCart();
    } else {
      navigate('/cart');
    }
  };

  return (
    <button
      onClick={handleClick}
      className={cn(
        'fixed bottom-14 left-0 right-0 z-40 border-t border-border bg-card p-4 safe-area-bottom',
        'flex flex-col gap-2 touch-feedback hover:bg-muted/50 transition-colors',
        className
      )}
      aria-label="Перейти в корзину"
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <ShoppingCart className="h-4 w-4 text-muted-foreground" />
          <span className="text-sm font-medium text-foreground">
            {isComplete
              ? 'Доставка доступна'
              : `До доставки ещё ${remaining.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} ₽`}
          </span>
        </div>
        {!isComplete && (
          <span className="text-xs text-muted-foreground">
            {cartTotal.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} / {minDeliveryTotal.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 0 })} ₽
          </span>
        )}
      </div>
      
      <div className="w-full h-2 bg-secondary rounded-full overflow-hidden">
        <div
          className={cn(
            'h-full rounded-full transition-all duration-300 ease-out',
            isComplete ? 'bg-success' : 'bg-primary'
          )}
          style={{ width: `${progress * 100}%` }}
          aria-valuenow={progress * 100}
          aria-valuemin={0}
          aria-valuemax={100}
          role="progressbar"
        />
      </div>
    </button>
  );
}

