import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';

export function WebOrdersPage() {
  return (
    <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <h1 className="text-2xl font-bold md:text-3xl">Мои заказы</h1>
        <p className="mt-4 max-w-md text-center text-muted-foreground">
          Для просмотра и управления заказами откройте приложение в Telegram
        </p>
        <Button asChild className="mt-6">
          <Link to="/">В каталог</Link>
        </Button>
      </div>
  );
}
