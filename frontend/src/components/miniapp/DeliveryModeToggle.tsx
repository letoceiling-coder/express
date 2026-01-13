import { cn } from '@/lib/utils';

interface DeliveryModeToggleProps {
  value: 'pickup' | 'delivery';
  onChange: (value: 'pickup' | 'delivery') => void;
  className?: string;
}

export function DeliveryModeToggle({ value, onChange, className }: DeliveryModeToggleProps) {
  return (
    <div className={cn('flex items-center gap-2 px-4 py-2 bg-background', className)}>
      <div className="flex rounded-full bg-secondary p-1 w-full">
        <button
          type="button"
          onClick={() => onChange('delivery')}
          className={cn(
            'flex-1 rounded-full px-4 py-2 text-sm font-medium transition-all touch-feedback',
            value === 'delivery'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'bg-transparent text-secondary-foreground hover:text-foreground'
          )}
          aria-label="Доставка"
        >
          Доставка
        </button>
        <button
          type="button"
          onClick={() => onChange('pickup')}
          className={cn(
            'flex-1 rounded-full px-4 py-2 text-sm font-medium transition-all touch-feedback',
            value === 'pickup'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'bg-transparent text-secondary-foreground hover:text-foreground'
          )}
          aria-label="Самовывоз"
        >
          Самовывоз
        </button>
      </div>
    </div>
  );
}

