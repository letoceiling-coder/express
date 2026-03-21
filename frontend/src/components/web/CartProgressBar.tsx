import { useState, useEffect } from 'react';
import { useCartStore } from '@/store/cartStore';
import { deliverySettingsAPI } from '@/api';

export function CartProgressBar() {
  console.log('CartProgressBar mounted');
  const totalAmount = useCartStore((state) => state.getTotalAmount());
  const [freeDeliveryThreshold, setFreeDeliveryThreshold] = useState<number | null>(null);

  useEffect(() => {
    deliverySettingsAPI.getSettings().then((settings) => {
      console.log('[CartProgressBar] API settings:', settings);
      const threshold =
        settings?.free_delivery_threshold ??
        settings?.free_delivery_threshold_rub ??
        settings?.freeDeliveryThreshold;
      const value = threshold != null ? Number(threshold) : null;
      setFreeDeliveryThreshold(value && value > 0 ? value : null);
    }).catch((err) => {
      console.error('[CartProgressBar] API error:', err);
      setFreeDeliveryThreshold(null);
    });
  }, []);

  // DEBUG: force render with fallback threshold
  const debugThreshold = freeDeliveryThreshold ?? 10000;

  const progress = Math.min(totalAmount / debugThreshold, 1);
  const remaining = Math.max(0, Math.ceil(debugThreshold - totalAmount));
  const isComplete = progress >= 1;

  let message: string;
  if (isComplete) {
    message = 'У вас бесплатная доставка 🎉';
  } else if (totalAmount === 0) {
    message = `Добавьте товаров на ${debugThreshold.toLocaleString('ru-RU')} ₽ для бесплатной доставки`;
  } else {
    message = `До бесплатной доставки осталось ${remaining.toLocaleString('ru-RU')} ₽`;
  }

  return (
    <div className="fixed top-[64px] left-0 right-0 z-50 border-2 border-red-500 border-b border-border bg-white dark:bg-card shadow-md px-4 py-3">
      <p className="text-sm font-medium text-foreground mb-2">
        {message}
      </p>
      <div className="w-full bg-gray-200 dark:bg-muted rounded-full h-4 overflow-hidden">
        <div
          className="bg-green-500 h-4 rounded-full transition-all duration-300"
          style={{ width: `${progress * 100}%` }}
          role="progressbar"
          aria-valuenow={progress * 100}
          aria-valuemin={0}
          aria-valuemax={100}
        />
      </div>
    </div>
  );
}
