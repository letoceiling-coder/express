import { cn } from '@/lib/utils';

interface DeliveryProgressBarProps {
  cartTotal: number;
  minDeliveryTotal: number;
  className?: string;
}

export function DeliveryProgressBar({
  cartTotal,
  minDeliveryTotal,
  className,
}: DeliveryProgressBarProps) {
  const progress = Math.min(cartTotal / minDeliveryTotal, 1);
  const remaining = Math.max(0, minDeliveryTotal - cartTotal);
  const isComplete = cartTotal >= minDeliveryTotal;

  // Форматирование чисел с пробелами (2 920 вместо 2920)
  const formatNumber = (num: number): string => {
    return num.toLocaleString('ru-RU', { 
      minimumFractionDigits: 0, 
      maximumFractionDigits: 0,
      useGrouping: true
    });
  };

  return (
    <div
      className={cn(
        'sticky top-[104px] z-30 bg-background border-b border-border pointer-events-none',
        className
      )}
    >
      <div className="mx-4 my-2 bg-card border border-border rounded-lg p-3 pointer-events-none">
        <div className="flex items-center justify-between mb-2">
          <span className="text-sm font-medium text-foreground">
            {isComplete
              ? 'Доставка доступна'
              : `Ещё ${formatNumber(remaining)} ₽ до доставки`}
          </span>
          <span className="text-xs text-muted-foreground">
            {formatNumber(cartTotal)} / {formatNumber(minDeliveryTotal)} ₽
          </span>
        </div>
        
        <div className="w-full h-1.5 bg-secondary rounded-full overflow-hidden">
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
      </div>
    </div>
  );
}

