import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { ShoppingBag } from 'lucide-react';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { CartItem } from '@/components/miniapp/CartItem';
import { useCartStore } from '@/store/cartStore';
import { deliverySettingsAPI } from '@/api';
import { showTelegramConfirm, hapticFeedback } from '@/lib/telegram';
import { cn } from '@/lib/utils';

export function CartPage() {
  const navigate = useNavigate();
  const { items, getTotalAmount, clearCart } = useCartStore();
  const totalAmount = getTotalAmount();

  // Получение orderMode из localStorage
  const [orderMode] = useState<'pickup' | 'delivery'>(() => {
    const saved = localStorage.getItem('orderMode');
    return saved === 'delivery' ? 'delivery' : 'pickup';
  });

  // Загрузка настроек доставки
  const [minDeliveryOrderTotal, setMinDeliveryOrderTotal] = useState<number>(3000);
  useEffect(() => {
    const loadSettings = async () => {
      try {
        const settings = await deliverySettingsAPI.getSettings();
        if (settings?.min_delivery_order_total_rub !== undefined) {
          setMinDeliveryOrderTotal(settings.min_delivery_order_total_rub);
        }
      } catch (error) {
        console.error('Error loading delivery settings:', error);
      }
    };
    loadSettings();
  }, []);

  // Проверка блокировки доставки
  const isDeliveryBlocked = orderMode === 'delivery' && totalAmount < minDeliveryOrderTotal;

  const handleClearCart = () => {
    showTelegramConfirm('Очистить корзину?', (confirmed) => {
      if (confirmed) {
        hapticFeedback('warning');
        clearCart();
      }
    });
  };

  if (items.length === 0) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Корзина" showCart={false} />

        <div className="flex flex-col items-center justify-center px-4 py-16">
          <div className="flex h-24 w-24 items-center justify-center rounded-full bg-secondary">
            <ShoppingBag className="h-12 w-12 text-muted-foreground" />
          </div>
          <h2 className="mt-6 text-xl font-bold text-foreground">Корзина пуста</h2>
          <p className="mt-2 text-center text-muted-foreground">
            Добавьте товары из каталога, чтобы оформить заказ
          </p>
          <button
            onClick={() => navigate('/')}
            className="mt-6 h-11 rounded-lg bg-primary px-8 font-semibold text-primary-foreground touch-feedback"
          >
            Перейти в каталог
          </button>
        </div>

        <BottomNavigation />
      </div>
    );
  }

  const getItemsText = (count: number) => {
    if (count === 1) return '1 товар';
    if (count >= 2 && count <= 4) return `${count} товара`;
    return `${count} товаров`;
  };

  return (
    <div className="flex flex-col h-screen bg-background overflow-hidden">
      <MiniAppHeader title="Корзина" showCart={false} />

      <div className="flex-1 overflow-y-auto px-4 min-h-0 pb-56">
        <div className="flex items-center justify-between py-3 sticky top-0 bg-background z-10">
          <span className="text-muted-foreground">
            {getItemsText(items.length)}
          </span>
          <button
            onClick={handleClearCart}
            className="text-sm font-medium text-destructive touch-feedback"
          >
            Очистить
          </button>
        </div>

        <div className="space-y-3 pb-4">
          {items.map((item) => (
            <CartItem key={item.product.id} item={item} />
          ))}
        </div>
      </div>

      {/* Bottom Summary - fixed above navigation */}
      <div className="fixed bottom-14 left-0 right-0 z-40 border-t border-border bg-background p-4 safe-area-bottom">
        {/* Summary card */}
        <div className="mb-3 rounded-lg bg-secondary p-3">
          <div className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">Товары ({items.length})</span>
            <span className="text-foreground">{totalAmount.toLocaleString('ru-RU')} ₽</span>
          </div>
          <div className="border-t border-border mt-2 pt-2 flex items-center justify-between">
            <span className="font-semibold text-foreground">Итого</span>
            <span className="text-lg font-bold text-primary">
              {totalAmount.toLocaleString('ru-RU')} ₽
            </span>
          </div>
        </div>
        
        {isDeliveryBlocked && (
          <p className="text-sm text-destructive mb-2 text-center">
            Минимум {minDeliveryOrderTotal.toLocaleString('ru-RU')} ₽ для доставки
          </p>
        )}
        <button
          onClick={() => navigate('/checkout', { state: { orderMode } })}
          disabled={isDeliveryBlocked}
          className={cn(
            'w-full h-11 rounded-lg bg-primary text-base font-semibold text-primary-foreground touch-feedback hover:opacity-90 transition-opacity',
            isDeliveryBlocked && 'opacity-50 cursor-not-allowed'
          )}
        >
          Оформить заказ
        </button>
      </div>

      <BottomNavigation />
    </div>
  );
}
