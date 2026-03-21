import { WebLayout } from '@/components/web/WebLayout';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';

export function WebCheckoutPage() {
  return (
    <WebLayout>
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <h1 className="text-2xl font-bold md:text-3xl">Оформление заказа</h1>
        <p className="mt-4 max-w-md text-center text-muted-foreground">
          Для оформления заказа откройте приложение в Telegram
        </p>
        <Button asChild className="mt-6" variant="outline">
          <Link to="/cart">Вернуться в корзину</Link>
        </Button>
        <Button asChild className="mt-4">
          <Link to="/">В каталог</Link>
        </Button>
      </div>
    </WebLayout>
  );
}
