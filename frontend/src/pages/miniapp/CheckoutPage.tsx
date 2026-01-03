import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { useCartStore } from '@/store/cartStore';
import { useOrders } from '@/hooks/useOrders';
import { toast } from 'sonner';
import { Loader2, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { getTelegramUser, hapticFeedback } from '@/lib/telegram';

const timeSlots = [
  '10:00-11:00',
  '11:00-12:00',
  '12:00-13:00',
  '13:00-14:00',
  '14:00-15:00',
  '15:00-16:00',
  '16:00-17:00',
  '17:00-18:00',
  '18:00-19:00',
  '19:00-20:00',
  '20:00-21:00',
];

type Step = 1 | 2 | 3;

export function CheckoutPage() {
  const navigate = useNavigate();
  const { items, getTotalAmount, clearCart } = useCartStore();
  const { createOrder } = useOrders();
  const totalAmount = getTotalAmount();

  const [step, setStep] = useState<Step>(1);
  const [isLoading, setIsLoading] = useState(false);
  const [formData, setFormData] = useState({
    phone: '',
    name: '',
    address: '',
    deliveryTime: '',
    comment: '',
  });

  // Pre-fill from Telegram user
  useEffect(() => {
    const user = getTelegramUser();
    if (user) {
      setFormData(prev => ({
        ...prev,
        name: user.first_name + (user.last_name ? ` ${user.last_name}` : ''),
      }));
    }
  }, []);

  if (items.length === 0) {
    navigate('/cart');
    return null;
  }

  const validateStep = (currentStep: Step): boolean => {
    if (currentStep === 1) {
      if (!formData.phone.trim()) {
        toast.error('Введите номер телефона');
        return false;
      }
    }
    if (currentStep === 2) {
      if (!formData.address.trim()) {
        toast.error('Введите адрес доставки');
        return false;
      }
      if (!formData.deliveryTime) {
        toast.error('Выберите время доставки');
        return false;
      }
    }
    return true;
  };

  const handleNext = () => {
    if (validateStep(step)) {
      setStep((prev) => Math.min(prev + 1, 3) as Step);
    }
  };

  const handleBack = () => {
    if (step === 1) {
      navigate('/cart');
    } else {
      setStep((prev) => (prev - 1) as Step);
    }
  };

  const handleSubmit = async () => {
    setIsLoading(true);
    hapticFeedback('medium');
    
    try {
      // Create order with real data
      const order = await createOrder({
        phone: formData.phone,
        deliveryAddress: formData.address,
        deliveryTime: formData.deliveryTime,
        comment: formData.comment || undefined,
        items: items.map(item => ({
          productId: item.product.id,
          productName: item.product.name,
          productImage: item.product.imageUrl,
          quantity: item.quantity,
          unitPrice: item.product.price,
        })),
        totalAmount,
      });

      clearCart();
      hapticFeedback('success');
      toast.success('Заказ успешно оформлен!');
      navigate(`/order-success/${order.orderId}`);
    } catch (err) {
      hapticFeedback('error');
      toast.error('Ошибка при оформлении заказа');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background pb-32">
      <MiniAppHeader title="Оформление заказа" showBack showCart={false} />

      {/* Progress Steps */}
      <div className="px-4 py-4">
        <div className="flex items-center justify-center gap-2">
          {[1, 2, 3].map((s) => (
            <div key={s} className="flex items-center gap-2">
              <div
                className={cn(
                  'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition-colors',
                  s < step
                    ? 'bg-primary text-primary-foreground'
                    : s === step
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-secondary text-muted-foreground'
                )}
              >
                {s < step ? <Check className="h-4 w-4" /> : s}
              </div>
              {s < 3 && (
                <div
                  className={cn(
                    'h-0.5 w-8 transition-colors',
                    s < step ? 'bg-primary' : 'bg-secondary'
                  )}
                />
              )}
            </div>
          ))}
        </div>
        <p className="mt-2 text-center text-sm text-muted-foreground">
          Шаг {step} из 3
        </p>
      </div>

      <div className="px-4 py-2">
        {/* Step 1: Contact */}
        {step === 1 && (
          <div className="space-y-4 animate-fade-in">
            <h2 className="text-xl font-semibold text-foreground">Контактные данные</h2>
            
            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Телефон *
              </label>
              <input
                type="tel"
                placeholder="+7 (___) ___-__-__"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>

            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Имя (опционально)
              </label>
              <input
                type="text"
                placeholder="Как к вам обращаться"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
          </div>
        )}

        {/* Step 2: Delivery */}
        {step === 2 && (
          <div className="space-y-4 animate-fade-in">
            <h2 className="text-xl font-semibold text-foreground">Адрес и время доставки</h2>

            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Адрес доставки *
              </label>
              <input
                type="text"
                placeholder="г. Екатеринбург, ул., д., кв."
                value={formData.address}
                onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>

            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Время доставки *
              </label>
              <select
                value={formData.deliveryTime}
                onChange={(e) => setFormData({ ...formData, deliveryTime: e.target.value })}
                className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
                <option value="">Выберите время</option>
                {timeSlots.map((slot) => (
                  <option key={slot} value={slot}>{slot}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Комментарий к заказу
              </label>
              <textarea
                placeholder="Дополнительные пожелания"
                value={formData.comment}
                onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
                rows={3}
                className="w-full rounded-lg border border-border bg-background px-4 py-3 text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"
              />
            </div>
          </div>
        )}

        {/* Step 3: Confirmation */}
        {step === 3 && (
          <div className="space-y-4 animate-fade-in">
            <h2 className="text-xl font-semibold text-foreground">Подтверждение заказа</h2>

            {/* Order Items */}
            <div className="rounded-lg border border-border bg-card p-4">
              <h3 className="mb-3 font-semibold text-foreground">Ваш заказ</h3>
              <div className="space-y-2">
                {items.map((item) => (
                  <div key={item.product.id} className="flex justify-between text-sm">
                    <span className="text-muted-foreground">
                      {item.product.name} × {item.quantity}
                    </span>
                    <span className="font-medium text-foreground">
                      {(item.product.price * item.quantity).toLocaleString('ru-RU')} ₽
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Delivery Details */}
            <div className="rounded-lg border border-border bg-card p-4">
              <h3 className="mb-3 font-semibold text-foreground">Детали доставки</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Адрес</span>
                  <span className="text-foreground text-right max-w-[200px]">{formData.address}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Время</span>
                  <span className="text-foreground">{formData.deliveryTime}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Телефон</span>
                  <span className="text-foreground">{formData.phone}</span>
                </div>
                {formData.comment && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Комментарий</span>
                    <span className="text-foreground text-right max-w-[200px]">{formData.comment}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Total */}
            <div className="rounded-lg bg-secondary p-4">
              <div className="flex items-center justify-between">
                <span className="text-lg font-semibold text-foreground">Итого</span>
                <span className="text-xl font-bold text-primary">
                  {totalAmount.toLocaleString('ru-RU')} ₽
                </span>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Bottom Actions */}
      <div className="fixed bottom-0 left-0 right-0 z-40 border-t border-border bg-background p-4 safe-area-bottom">
        <div className="flex gap-3">
          <button
            onClick={handleBack}
            className="flex-1 h-11 rounded-lg border border-border bg-background font-semibold text-foreground touch-feedback"
          >
            {step === 1 ? 'Отмена' : 'Назад'}
          </button>
          
          {step < 3 ? (
            <button
              onClick={handleNext}
              className="flex-1 h-11 rounded-lg bg-primary font-semibold text-primary-foreground touch-feedback"
            >
              Далее
            </button>
          ) : (
            <button
              onClick={handleSubmit}
              disabled={isLoading}
              className="flex-1 h-11 rounded-lg bg-primary font-semibold text-primary-foreground touch-feedback disabled:opacity-50 flex items-center justify-center gap-2"
            >
              {isLoading ? (
                <>
                  <Loader2 className="h-5 w-5 animate-spin" />
                  Обработка...
                </>
              ) : (
                'Оплатить через ЮKassa'
              )}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
