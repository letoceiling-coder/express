import { Order, isOrderUnpaid, canCancelOrder } from '@/types';
import { StatusBadge } from '@/components/ui/status-badge';
import { ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useNavigate } from 'react-router-dom';
import { paymentAPI } from '@/api';
import { toast } from 'sonner';
import { getTelegramUser } from '@/lib/telegram';

interface OrderCardProps {
  order: Order;
  onClick?: () => void;
  onCancel?: () => void;
  onPayment?: () => void;
}

export function OrderCard({ order, onClick, onCancel, onPayment }: OrderCardProps) {
  const navigate = useNavigate();
  const isUnpaid = isOrderUnpaid(order);
  const canCancel = canCancelOrder(order);

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(date));
  };

  const getItemsText = (count: number) => {
    if (count === 1) return '1 товар';
    if (count >= 2 && count <= 4) return `${count} товара`;
    return `${count} товаров`;
  };

  const handlePayClick = async (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onPayment) {
      onPayment();
      return;
    }

    // Переходим на страницу заказа с параметром action=pay
    navigate(`/orders/${order.orderId}?action=pay`);
  };

  const handleCancelClick = async (e: React.MouseEvent) => {
    e.stopPropagation();
    if (onCancel) {
      onCancel();
      return;
    }
    
    // Пока просто переходим на страницу заказа, где будет кнопка отмены
    navigate(`/orders/${order.orderId}?action=cancel`);
  };

  return (
    <div
      className={`rounded-xl border border-border bg-card p-4 transition-all animate-fade-in ${
        isUnpaid ? 'opacity-90' : ''
      } ${onClick ? 'cursor-pointer hover:shadow-md touch-feedback' : ''}`}
      onClick={onClick}
    >
      <div className="flex items-start justify-between">
        <div>
          <h3 className="text-base font-semibold text-foreground">{order.orderId}</h3>
          <p className="mt-0.5 text-sm text-muted-foreground">
            {formatDate(order.createdAt)}
          </p>
        </div>
        {isUnpaid ? (
          <span className="inline-flex items-center rounded-full bg-destructive/10 px-2.5 py-0.5 text-xs font-medium text-destructive">
            Ожидает оплаты
          </span>
        ) : (
          <StatusBadge status={order.status} size="sm" />
        )}
      </div>

      <div className="mt-3 flex items-center justify-between border-t border-border pt-3">
        <span className="text-sm text-muted-foreground">
          {getItemsText(order.items.length)}
        </span>
        <div className="flex items-center gap-1">
          <span className="text-base font-bold text-foreground">
            {order.totalAmount.toLocaleString('ru-RU')} ₽
          </span>
          {onClick && <ChevronRight className="h-5 w-5 text-muted-foreground" />}
        </div>
      </div>

      {/* CTA Buttons for Unpaid Orders */}
      {isUnpaid && canCancel && (
        <div className="mt-3 flex gap-2 border-t border-border pt-3">
          <Button
            onClick={handlePayClick}
            className="flex-1 bg-primary text-primary-foreground hover:opacity-90"
            size="sm"
          >
            Оплатить
          </Button>
          <Button
            onClick={handleCancelClick}
            variant="outline"
            className="flex-1"
            size="sm"
          >
            Отменить
          </Button>
        </div>
      )}
    </div>
  );
}
