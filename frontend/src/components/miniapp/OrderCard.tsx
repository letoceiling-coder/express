import { Order } from '@/types';
import { StatusBadge } from '@/components/ui/status-badge';
import { ChevronRight } from 'lucide-react';

interface OrderCardProps {
  order: Order;
  onClick?: () => void;
}

export function OrderCard({ order, onClick }: OrderCardProps) {
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

  return (
    <div
      className="cursor-pointer rounded-xl border border-border bg-card p-4 transition-all touch-feedback animate-fade-in hover:shadow-md"
      onClick={onClick}
    >
      <div className="flex items-start justify-between">
        <div>
          <h3 className="text-base font-semibold text-foreground">{order.id}</h3>
          <p className="mt-0.5 text-sm text-muted-foreground">
            {formatDate(order.createdAt)}
          </p>
        </div>
        <StatusBadge status={order.status} size="sm" />
      </div>

      <div className="mt-3 flex items-center justify-between border-t border-border pt-3">
        <span className="text-sm text-muted-foreground">
          {getItemsText(order.items.length)}
        </span>
        <div className="flex items-center gap-1">
          <span className="text-base font-bold text-foreground">
            {order.totalAmount.toLocaleString('ru-RU')} ₽
          </span>
          <ChevronRight className="h-5 w-5 text-muted-foreground" />
        </div>
      </div>
    </div>
  );
}
