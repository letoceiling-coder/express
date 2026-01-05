import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { useCartStore } from '@/store/cartStore';
import { useOrders } from '@/hooks/useOrders';
import { toast } from 'sonner';
import { Loader2, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { getTelegramUser, hapticFeedback } from '@/lib/telegram';
import { ordersAPI } from '@/api';

// Функция форматирования телефона с маской
const formatPhone = (value: string): string => {
  // Удаляем все нецифровые символы
  const digits = value.replace(/\D/g, '');
  
  // Если начинается с 8, заменяем на 7
  const normalizedDigits = digits.startsWith('8') ? '7' + digits.slice(1) : digits;
  
  // Ограничиваем до 11 цифр (7 + 10)
  const limited = normalizedDigits.slice(0, 11);
  
  // Форматируем: +7 (XXX) XXX-XX-XX
  if (limited.length === 0) return '';
  if (limited.length <= 1) return `+${limited}`;
  if (limited.length <= 4) return `+${limited.slice(0, 1)} (${limited.slice(1)}`;
  if (limited.length <= 7) return `+${limited.slice(0, 1)} (${limited.slice(1, 4)}) ${limited.slice(4)}`;
  if (limited.length <= 9) return `+${limited.slice(0, 1)} (${limited.slice(1, 4)}) ${limited.slice(4, 7)}-${limited.slice(7)}`;
  return `+${limited.slice(0, 1)} (${limited.slice(1, 4)}) ${limited.slice(4, 7)}-${limited.slice(7, 9)}-${limited.slice(9, 11)}`;
};

// Функция получения только цифр из телефона
const getPhoneDigits = (value: string): string => {
  const digits = value.replace(/\D/g, '');
  return digits.startsWith('8') ? '7' + digits.slice(1) : digits;
};

// Генерация временных слотов с фильтрацией прошедшего времени
const generateTimeSlots = (): string[] => {
  const slots = [
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

  const now = new Date();
  const currentHour = now.getHours();
  const currentMinute = now.getMinutes();
  
  // Фильтруем слоты: исключаем прошедшие и текущий час (запас 1 час)
  return slots.filter((slot) => {
    const [startTime] = slot.split('-');
    const [hour] = startTime.split(':').map(Number);
    
    // Исключаем слоты, которые уже прошли
    if (hour < currentHour) return false;
    
    // Исключаем текущий час (запас 1 час)
    if (hour === currentHour) return false;
    
    return true;
  });
};

type Step = 1 | 2 | 3;
type DeliveryType = 'courier' | 'pickup';

export function CheckoutPage() {
  const navigate = useNavigate();
  const { items, getTotalAmount, clearCart } = useCartStore();
  const { createOrder, loadOrders } = useOrders();
  const totalAmount = getTotalAmount();

  const [step, setStep] = useState<Step>(1);
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingPastOrders, setIsLoadingPastOrders] = useState(false);
  const [formData, setFormData] = useState({
    phone: '',
    name: '',
    address: '',
    deliveryTime: '',
    deliveryType: 'courier' as DeliveryType,
    comment: '',
  });
  
  const [availableTimeSlots] = useState(() => generateTimeSlots());

  // Загрузка прошлых заказов и автозаполнение данных
  useEffect(() => {
    const loadPastOrders = async () => {
      // Ждем немного, чтобы Telegram WebApp успел инициализироваться
      await new Promise(resolve => setTimeout(resolve, 100));
      
      let user = getTelegramUser();
      console.log('CheckoutPage - getTelegramUser result (first try):', user);
      
      // Если пользователь не найден, пробуем еще раз через небольшую задержку
      if (!user?.id) {
        await new Promise(resolve => setTimeout(resolve, 200));
        user = getTelegramUser();
        console.log('CheckoutPage - getTelegramUser result (second try):', user);
      }
      
      if (!user?.id) {
        console.warn('CheckoutPage - No telegram user ID after retries, skipping order load');
        console.warn('CheckoutPage - window.Telegram:', window.Telegram);
        return;
      }

      setIsLoadingPastOrders(true);
      try {
        console.log('CheckoutPage - Loading past orders for user:', user.id);
        const orders = await ordersAPI.getByTelegramId(user.id);
        console.log('CheckoutPage - Loaded orders:', orders, 'count:', orders.length);
        
        // Берем последний заказ для автозаполнения
        if (orders.length > 0) {
          const lastOrder = orders[0]; // Предполагаем, что заказы отсортированы по дате (новые первыми)
          console.log('CheckoutPage - Using last order for autofill:', lastOrder);
          
          // Обрабатываем телефон: если он уже отформатирован, используем как есть, иначе форматируем
          let phoneValue = prev.phone;
          if (lastOrder.phone) {
            // Проверяем, отформатирован ли телефон уже
            const phoneDigits = getPhoneDigits(lastOrder.phone);
            if (phoneDigits.length >= 10) {
              // Форматируем только если есть достаточно цифр
              phoneValue = formatPhone(lastOrder.phone);
            } else {
              // Если формат странный, используем как есть
              phoneValue = lastOrder.phone;
            }
          }
          
          setFormData(prev => ({
            ...prev,
            phone: phoneValue,
            address: lastOrder.deliveryAddress || prev.address,
            name: lastOrder.name || prev.name || '', // Имя берем из заказа
          }));
          
          console.log('CheckoutPage - Form data updated:', {
            originalPhone: lastOrder.phone,
            formattedPhone: phoneValue,
            address: lastOrder.deliveryAddress || 'not set',
            name: lastOrder.name || 'not set',
          });
        } else {
          console.log('CheckoutPage - No past orders found');
        }
      } catch (error) {
        console.error('CheckoutPage - Failed to load past orders:', error);
        // Не показываем ошибку пользователю, так как это не критично
      } finally {
        setIsLoadingPastOrders(false);
      }
    };

    loadPastOrders();
  }, []);

  // Имя заполняется только из прошлых заказов, не из Telegram

  if (items.length === 0) {
    navigate('/cart');
    return null;
  }

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatPhone(e.target.value);
    setFormData({ ...formData, phone: formatted });
  };

  const validateStep = (currentStep: Step): boolean => {
    if (currentStep === 1) {
      const phoneDigits = getPhoneDigits(formData.phone);
      if (phoneDigits.length < 11) {
        toast.error('Введите корректный номер телефона');
        return false;
      }
    }
    if (currentStep === 2) {
      if (formData.deliveryType === 'courier' && !formData.address.trim()) {
        toast.error('Введите адрес доставки');
        return false;
      }
      // Время доставки теперь необязательное
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
      // Подготовка данных заказа
      const phoneDigits = getPhoneDigits(formData.phone);
      const orderData = {
        phone: phoneDigits,
        name: formData.name || undefined,
        deliveryAddress: formData.deliveryType === 'pickup' ? 'Самовывоз' : formData.address,
        deliveryTime: formData.deliveryTime || undefined,
        deliveryType: formData.deliveryType,
        comment: formData.comment || undefined,
        items: items.map(item => ({
          productId: item.product.id,
          productName: item.product.name,
          productImage: item.product.imageUrl,
          quantity: item.quantity,
          unitPrice: item.product.price,
        })),
        totalAmount,
      };

      // Create order with real data
      const order = await createOrder(orderData);

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
                onChange={handlePhoneChange}
                maxLength={18}
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
                Тип доставки *
              </label>
              <select
                value={formData.deliveryType}
                onChange={(e) => setFormData({ ...formData, deliveryType: e.target.value as DeliveryType })}
                className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
                <option value="courier">Курьер</option>
                <option value="pickup">Самовывоз</option>
              </select>
            </div>

            {formData.deliveryType === 'courier' && (
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
            )}

            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Время доставки (опционально)
              </label>
              <select
                value={formData.deliveryTime}
                onChange={(e) => setFormData({ ...formData, deliveryTime: e.target.value })}
                className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
                <option value="">Выберите время</option>
                {availableTimeSlots.length > 0 ? (
                  availableTimeSlots.map((slot) => (
                    <option key={slot} value={slot}>{slot}</option>
                  ))
                ) : (
                  <option value="" disabled>Нет доступных временных слотов на сегодня</option>
                )}
              </select>
              {availableTimeSlots.length === 0 && (
                <p className="mt-1 text-xs text-muted-foreground">
                  Все временные слоты на сегодня заняты. Выберите время или оставьте поле пустым.
                </p>
              )}
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
                  <span className="text-muted-foreground">Тип доставки</span>
                  <span className="text-foreground">
                    {formData.deliveryType === 'courier' ? 'Курьер' : 'Самовывоз'}
                  </span>
                </div>
                {formData.deliveryType === 'courier' && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Адрес</span>
                    <span className="text-foreground text-right max-w-[200px]">{formData.address}</span>
                  </div>
                )}
                {formData.deliveryTime && (
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Время</span>
                    <span className="text-foreground">{formData.deliveryTime}</span>
                  </div>
                )}
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
