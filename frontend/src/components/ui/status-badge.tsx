import { OrderStatus } from '@/types';
import { cn } from '@/lib/utils';

export const ORDER_STATUS_CLASSES: Record<OrderStatus, string> = {
  new: 'status-new',
  accepted: 'status-accepted',
  preparing: 'status-preparing',
  ready_for_delivery: 'status-ready_for_delivery',
  in_transit: 'status-in_transit',
  delivered: 'status-delivered',
  cancelled: 'status-cancelled',
};

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  new: 'Новый',
  accepted: 'Принят',
  preparing: 'Готовится',
  ready_for_delivery: 'Готов к отправке',
  in_transit: 'В пути',
  delivered: 'Доставлен',
  cancelled: 'Отменён',
};

interface StatusBadgeProps {
  status: OrderStatus;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export function StatusBadge({ status, size = 'sm', className }: StatusBadgeProps) {
  const sizeClasses = {
    sm: 'px-3 py-1 text-xs',
    md: 'px-4 py-1.5 text-sm',
    lg: 'px-6 py-3 text-base',
  };

  return (
    <span
      className={cn(
        'inline-flex items-center justify-center rounded-full font-semibold',
        ORDER_STATUS_CLASSES[status],
        sizeClasses[size],
        className
      )}
    >
      {ORDER_STATUS_LABELS[status]}
    </span>
  );
}
