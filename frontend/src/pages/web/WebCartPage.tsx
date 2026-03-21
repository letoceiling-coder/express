import { useNavigate } from 'react-router-dom';
import { ShoppingBag } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { Button } from '@/components/ui/button';
import { OptimizedImage } from '@/components/OptimizedImage';
import { Plus, Minus } from 'lucide-react';

export function WebCartPage() {
  const navigate = useNavigate();
  const { items, getTotalAmount, getTotalItems, addItem, updateQuantity, removeItem } =
    useCartStore();
  const totalAmount = getTotalAmount();
  const totalItems = getTotalItems();

  if (items.length === 0) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
          <div className="flex h-24 w-24 items-center justify-center rounded-full bg-muted">
            <ShoppingBag className="h-12 w-12 text-muted-foreground" />
          </div>
          <h2 className="mt-6 text-xl font-bold">Корзина пуста</h2>
          <p className="mt-2 text-muted-foreground">
            Добавьте товары из каталога
          </p>
          <Button className="mt-6" onClick={() => navigate('/')}>
            В каталог
          </Button>
        </div>
    );
  }

  return (
      <div className="container mx-auto px-4 py-8 lg:px-8">
        <h1 className="mb-8 text-2xl font-bold md:text-3xl">Корзина</h1>

        <div className="grid gap-8 lg:grid-cols-3">
          <div className="lg:col-span-2">
            <div className="space-y-4">
              {items.map(({ product, quantity }) => (
                <div
                  key={product.id}
                  className="flex gap-4 rounded-xl border border-border bg-card p-4"
                >
                  <div className="h-24 w-24 shrink-0 overflow-hidden rounded-lg bg-muted">
                    <OptimizedImage
                      src={product.imageUrl || '/placeholder-image.jpg'}
                      webpSrc={product.webpUrl}
                      alt={product.name}
                      className="h-full w-full object-cover"
                      size="thumbnail"
                    />
                  </div>
                  <div className="min-w-0 flex-1">
                    <h3 className="font-semibold">{product.name}</h3>
                    <p className="text-sm text-muted-foreground">
                      {product.price.toLocaleString('ru-RU')} ₽
                      {product.isWeightProduct && ' / ед.'}
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                      <div className="flex items-center rounded border border-border">
                        <button
                          onClick={() => updateQuantity(product.id, quantity - 1)}
                          className="flex h-8 w-8 items-center justify-center hover:bg-muted"
                        >
                          <Minus className="h-4 w-4" />
                        </button>
                        <span className="w-8 text-center text-sm">{quantity}</span>
                        <button
                          onClick={() => addItem(product)}
                          className="flex h-8 w-8 items-center justify-center hover:bg-muted"
                        >
                          <Plus className="h-4 w-4" />
                        </button>
                      </div>
                      <button
                        onClick={() => removeItem(product.id)}
                        className="text-sm text-muted-foreground underline hover:text-destructive"
                      >
                        Удалить
                      </button>
                    </div>
                  </div>
                  <div className="text-right font-semibold">
                    {(product.price * quantity).toLocaleString('ru-RU')} ₽
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div>
            <div className="sticky top-24 rounded-xl border border-border bg-card p-6">
              <h3 className="font-semibold">Итого</h3>
              <p className="mt-2 text-2xl font-bold">
                {totalAmount.toLocaleString('ru-RU')} ₽
              </p>
              <p className="text-sm text-muted-foreground">
                {totalItems} {totalItems === 1 ? 'товар' : 'товара'}
              </p>
              <Button
                className="mt-6 w-full"
                size="lg"
                onClick={() => navigate('/checkout')}
              >
                Оформить заказ
              </Button>
            </div>
          </div>
        </div>
      </div>
  );
}
