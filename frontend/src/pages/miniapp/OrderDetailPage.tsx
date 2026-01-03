import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { StatusBadge } from '@/components/ui/status-badge';
import { useOrders } from '@/hooks/useOrders';
import { ORDER_STATUS_LABELS, PAYMENT_STATUS_LABELS, Order } from '@/types';
import { MapPin, Phone, Clock, MessageSquare, CreditCard, Package, Headphones, ShoppingBag, Loader2 } from 'lucide-react';
import { openTelegramLink } from '@/lib/telegram';
import { cn } from '@/lib/utils';

export function OrderDetailPage() {
  const { orderId } = useParams();
  const navigate = useNavigate();
  const { getOrderById } = useOrders();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchOrder = async () => {
      if (!orderId) return;
      setLoading(true);
      const data = await getOrderById(orderId);
      setOrder(data);
      setLoading(false);
    };
    fetchOrder();
  }, [orderId, getOrderById]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Заказ" showBack showCart={false} />
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка заказа...</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (!order) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Заказ" showBack showCart={false} />

        <div className="flex flex-col items-center justify-center px-4 py-16">
          <h2 className="text-xl font-bold text-foreground">Заказ не найден</h2>
          <p className="mt-2 text-center text-muted-foreground">
            Возможно, он был удалён или ссылка устарела
          </p>
          <button
            onClick={() => navigate('/orders')}
            className="mt-6 rounded-xl bg-primary px-8 py-3 font-semibold text-primary-foreground touch-feedback"
          >
            К списку заказов
          </button>
        </div>

        <BottomNavigation />
      </div>
    );
  }

  const formatDate = (date: Date) => {
    const d = new Date(date);
    return d.toLocaleDateString('ru-RU', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const handleContactSupport = () => {
    openTelegramLink('https://t.me/svoihleb_support');
  };

  return (
    <div className="min-h-screen bg-background pb-44">
      <MiniAppHeader title={`Заказ #${order.orderId}`} showBack showCart={false} />

      <div className="px-4 py-4 space-y-4">
        {/* Order ID & Status */}
        <div className="rounded-xl border border-border bg-card p-4">
          <div className="flex items-start justify-between">
            <div>
              <p className="text-sm text-muted-foreground">Номер заказа</p>
              <p className="text-lg font-bold text-foreground">#{order.orderId}</p>
              <p className="mt-1 text-sm text-muted-foreground">
                {formatDate(order.createdAt)}
              </p>
            </div>
            <StatusBadge status={order.status} size="lg" />
          </div>
        </div>

        {/* Current Status */}
        <div className="rounded-xl border border-border bg-card p-4">
          <h3 className="mb-3 font-semibold text-foreground">Статус заказа</h3>
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
              <Clock className="h-6 w-6 text-primary" />
            </div>
            <div>
              <p className="font-medium text-foreground">{ORDER_STATUS_LABELS[order.status]}</p>
              {order.deliveryTime && (
                <p className="text-sm text-muted-foreground">
                  Ожидаемая доставка: {order.deliveryTime}
                </p>
              )}
            </div>
          </div>
        </div>

        {/* Delivery Info */}
        <div className="rounded-xl border border-border bg-card p-4 space-y-3">
          <h3 className="font-semibold text-foreground">Доставка</h3>
          
          <div className="flex items-start gap-3">
            <MapPin className="h-5 w-5 text-muted-foreground flex-shrink-0 mt-0.5" />
            <p className="text-sm text-foreground">{order.deliveryAddress}</p>
          </div>
          
          <div className="flex items-center gap-3">
            <Clock className="h-5 w-5 text-muted-foreground flex-shrink-0" />
            <p className="text-sm text-foreground">{order.deliveryTime}</p>
          </div>
          
          <div className="flex items-center gap-3">
            <Phone className="h-5 w-5 text-muted-foreground flex-shrink-0" />
            <p className="text-sm text-foreground">{order.phone}</p>
          </div>
          
          {order.comment && (
            <div className="flex items-start gap-3">
              <MessageSquare className="h-5 w-5 text-muted-foreground flex-shrink-0 mt-0.5" />
              <p className="text-sm text-foreground">{order.comment}</p>
            </div>
          )}
        </div>

        {/* Order Items */}
        <div className="rounded-xl border border-border bg-card overflow-hidden">
          <div className="flex items-center gap-2 p-4 border-b border-border">
            <Package className="h-5 w-5 text-muted-foreground" />
            <h3 className="font-semibold text-foreground">Состав заказа</h3>
          </div>
          
          {order.items.map((item, index) => (
            <div
              key={item.id}
              className={cn(
                'flex items-center gap-3 p-4',
                index !== order.items.length - 1 && 'border-b border-border'
              )}
            >
              {item.productImage ? (
                <div className="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-muted">
                  <img
                    src={item.productImage}
                    alt={item.productName}
                    className="h-full w-full object-cover"
                  />
                </div>
              ) : (
                <div className="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-muted flex items-center justify-center">
                  <ShoppingBag className="h-6 w-6 text-muted-foreground" />
                </div>
              )}
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-foreground line-clamp-1">
                  {item.productName || 'Товар'}
                </p>
                <p className="text-sm text-muted-foreground">
                  {item.quantity} × {item.unitPrice.toLocaleString('ru-RU')} ₽
                </p>
              </div>
              <span className="font-semibold text-foreground">
                {item.total.toLocaleString('ru-RU')} ₽
              </span>
            </div>
          ))}
        </div>

        {/* Payment Info */}
        <div className="rounded-xl border border-border bg-card p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <CreditCard className="h-5 w-5 text-muted-foreground" />
              <span className="text-sm text-muted-foreground">Статус оплаты</span>
            </div>
            <span className={`text-sm font-medium ${
              order.paymentStatus === 'succeeded' ? 'text-primary' : 
              order.paymentStatus === 'failed' ? 'text-destructive' : 
              'text-muted-foreground'
            }`}>
              {PAYMENT_STATUS_LABELS[order.paymentStatus]}
            </span>
          </div>
          <div className="mt-3 pt-3 border-t border-border flex items-center justify-between">
            <span className="text-lg font-semibold text-foreground">Итого</span>
            <span className="text-xl font-bold text-primary">
              {order.totalAmount.toLocaleString('ru-RU')} ₽
            </span>
          </div>
        </div>
      </div>

      {/* Bottom Actions */}
      <div className="fixed bottom-14 left-0 right-0 z-40 border-t border-border bg-background/95 backdrop-blur-sm p-4 safe-area-bottom">
        <div className="flex gap-3">
          <button
            onClick={handleContactSupport}
            className="flex-1 flex items-center justify-center gap-2 rounded-xl border border-border bg-background py-3 font-semibold text-foreground touch-feedback"
          >
            <Headphones className="h-5 w-5" />
            Поддержка
          </button>
          <button
            onClick={() => navigate('/')}
            className="flex-1 flex items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-primary-foreground touch-feedback"
          >
            <ShoppingBag className="h-5 w-5" />
            В каталог
          </button>
        </div>
      </div>

      <BottomNavigation />
    </div>
  );
}
