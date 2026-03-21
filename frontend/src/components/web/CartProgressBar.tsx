import { useState, useEffect } from 'react';
import { useCartStore } from '@/store/cartStore';
import { useOrderModeStore } from '@/store/orderModeStore';
import { deliverySettingsAPI } from '@/api';

export function CartProgressBar() {
  const totalAmount = useCartStore((state) => state.getTotalAmount());
  const orderMode = useOrderModeStore((state) => state.orderMode);
  const [freeDeliveryThreshold, setFreeDeliveryThreshold] = useState<number | null>(null);

  useEffect(() => {
    deliverySettingsAPI.getSettings().then((settings) => {
      const threshold =
        settings?.free_delivery_threshold ??
        settings?.free_delivery_threshold_rub ??
        settings?.freeDeliveryThreshold;
      const value = threshold != null ? Number(threshold) : null;
      setFreeDeliveryThreshold(value && value > 0 ? value : null);
    }).catch(() => setFreeDeliveryThreshold(null));
  }, []);

  const threshold = freeDeliveryThreshold ?? 10000;
  const progress = Math.min(totalAmount / threshold, 1);
  const remaining = Math.max(0, Math.ceil(threshold - totalAmount));
  const isComplete = progress >= 1;

  let message: string;
  if (isComplete) {
    message = 'У вас бесплатная доставка 🎉';
  } else if (totalAmount === 0) {
    message = `Добавьте товаров на ${threshold.toLocaleString('ru-RU')} ₽ для бесплатной доставки`;
  } else {
    message = `До бесплатной доставки осталось ${remaining.toLocaleString('ru-RU')} ₽`;
  }

  if (orderMode !== 'delivery') return null;

  return (
    <div className="w-full bg-background px-4 py-2 border-t border-border lg:border-t-0 lg:border-b lg:px-8">
      <div className="max-w-7xl mx-auto">
        <p className="text-xs font-medium text-foreground mb-2">
          {message}
        </p>
        <div className="w-full h-2 bg-muted rounded-xl overflow-hidden shadow-inner">
          <div
            className="h-full bg-primary rounded-xl transition-all duration-300 ease-out"
            style={{ width: `${progress * 100}%` }}
            role="progressbar"
            aria-valuenow={progress * 100}
            aria-valuemin={0}
            aria-valuemax={100}
          />
        </div>
      </div>
    </div>
  );
}
