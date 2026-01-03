import { useNavigate, useParams } from 'react-router-dom';
import { CheckCircle2 } from 'lucide-react';

export function OrderSuccessPage() {
  const navigate = useNavigate();
  const { orderId } = useParams();

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4 safe-area-top safe-area-bottom">
      <div className="flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 animate-scale-in">
        <CheckCircle2 className="h-12 w-12 text-primary" />
      </div>

      <h1 className="mt-6 text-2xl font-bold text-foreground animate-fade-in">Заказ оформлен!</h1>

      <p className="mt-2 text-center text-muted-foreground animate-fade-in">
        Номер вашего заказа
      </p>
      <p className="mt-1 text-xl font-bold text-foreground animate-fade-in">{orderId}</p>

      <p className="mt-6 text-center text-muted-foreground animate-fade-in">
        Мы свяжемся с вами для подтверждения заказа и уточнения деталей доставки.
      </p>

      <div className="mt-8 flex w-full max-w-sm flex-col gap-3 animate-fade-in">
        <button
          onClick={() => navigate('/orders')}
          className="w-full rounded-xl bg-primary py-4 font-semibold text-primary-foreground touch-feedback"
        >
          Мои заказы
        </button>
        <button
          onClick={() => navigate('/')}
          className="w-full rounded-xl border border-border bg-background py-4 font-semibold text-foreground touch-feedback"
        >
          Вернуться в каталог
        </button>
      </div>
    </div>
  );
}
