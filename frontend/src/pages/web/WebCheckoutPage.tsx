import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import { useCartStore } from '@/store/cartStore';
import { useAuthStore } from '@/store/authStore';
import { useWebOrders } from '@/hooks/useWebOrders';
import { AuthModal } from '@/components/web/AuthModal';
import { paymentMethodsAPI, paymentAPI, deliverySettingsAPI } from '@/api';
import { toast } from 'sonner';
import { Loader2 } from 'lucide-react';
import { format } from 'date-fns';
import { ru } from 'date-fns/locale';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CalendarIcon } from 'lucide-react';

const formatPhone = (v: string): string => {
  const d = v.replace(/\D/g, '');
  const norm = d.startsWith('8') ? '7' + d.slice(1) : d;
  const limited = norm.slice(0, 11);
  if (limited.length <= 1) return limited ? `+${limited}` : '';
  if (limited.length <= 4) return `+${limited[0]} (${limited.slice(1)}`;
  if (limited.length <= 7) return `+${limited[0]} (${limited.slice(1, 4)}) ${limited.slice(4)}`;
  if (limited.length <= 9) return `+${limited[0]} (${limited.slice(1, 4)}) ${limited.slice(4, 7)}-${limited.slice(7)}`;
  return `+${limited[0]} (${limited.slice(1, 4)}) ${limited.slice(4, 7)}-${limited.slice(7, 9)}-${limited.slice(9)}`;
};

const getPhoneDigits = (v: string): string => {
  const d = v.replace(/\D/g, '');
  return d.startsWith('8') ? '7' + d.slice(1) : d;
};

type Step = 1 | 2 | 3 | 4;
type DeliveryType = 'courier' | 'pickup';

const TIME_SLOTS = ['10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00', '14:00-15:00', '15:00-16:00', '16:00-17:00', '17:00-18:00', '18:00-19:00', '19:00-20:00', '20:00-21:00'];

export function WebCheckoutPage() {
  const navigate = useNavigate();
  const { items, getTotalAmount, clearCart } = useCartStore();
  const isAuth = useAuthStore((s) => s.isAuthenticated());
  const user = useAuthStore((s) => s.user);
  const { createOrder } = useWebOrders();
  const totalAmount = getTotalAmount();
  const [showAuth, setShowAuth] = useState(false);

  const [step, setStep] = useState<Step>(1);
  const [isLoading, setIsLoading] = useState(false);
  const [deliveryCost, setDeliveryCost] = useState<number | null>(null);
  const [isCalculatingDelivery, setIsCalculatingDelivery] = useState(false);
  const [deliveryValidation, setDeliveryValidation] = useState<{ valid: boolean; address?: string; error?: string } | null>(null);
  const [defaultCity, setDefaultCity] = useState('Екатеринбург');
  const [paymentMethods, setPaymentMethods] = useState<any[]>([]);
  const [loadingPaymentMethods, setLoadingPaymentMethods] = useState(false);
  const [discountInfo, setDiscountInfo] = useState<{ discount: number; final_amount: number; applied: boolean } | null>(null);
  const [minDeliveryOrderTotal, setMinDeliveryOrderTotal] = useState(3000);
  const [deliveryMinLeadHours, setDeliveryMinLeadHours] = useState(3);

  const [formData, setFormData] = useState({
    phone: user?.phone ? formatPhone(user.phone) : '',
    name: '',
    address: '',
    deliveryDate: '',
    deliveryTimeSlot: '',
    deliveryType: 'courier' as DeliveryType,
    comment: '',
    paymentMethod: null as any,
  });

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const maxDate = new Date();
  maxDate.setMonth(maxDate.getMonth() + 2);

  const getTimeSlotsForDate = (selectedDate: string): string[] => {
    if (!selectedDate) return [];
    const selected = new Date(selectedDate + 'T00:00:00');
    const todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);
    if (selected < todayStart) return [];
    const now = new Date();
    const isToday = selected.getTime() === todayStart.getTime();
    if (!isToday) return TIME_SLOTS;
    const minTime = new Date(now.getTime() + deliveryMinLeadHours * 60 * 60 * 1000);
    return TIME_SLOTS.filter((slot) => {
      const [h, m] = slot.split('-')[0].split(':').map(Number);
      return h > minTime.getHours() || (h === minTime.getHours() && m >= minTime.getMinutes());
    });
  };

  useEffect(() => {
    if (user?.phone) setFormData((p) => ({ ...p, phone: formatPhone(user.phone || '') }));
  }, [user?.phone]);

  useEffect(() => {
    deliverySettingsAPI.getSettings().then((s) => {
      if (s?.default_city) setDefaultCity(s.default_city);
      if (s?.min_delivery_order_total_rub != null) setMinDeliveryOrderTotal(Number(s.min_delivery_order_total_rub));
      if (s?.delivery_min_lead_hours != null) setDeliveryMinLeadHours(Number(s.delivery_min_lead_hours));
    }).catch(() => {});
  }, []);

  useEffect(() => {
    paymentMethodsAPI.getAll().then(setPaymentMethods).catch(() => toast.error('Не удалось загрузить способы оплаты'));
  }, []);

  useEffect(() => {
    if (formData.paymentMethod && totalAmount > 0) {
      paymentMethodsAPI.getById(formData.paymentMethod.id, totalAmount).then((info) => {
        if (info?.discount) setDiscountInfo(info.discount);
        else setDiscountInfo(null);
      }).catch(() => setDiscountInfo(null));
    } else setDiscountInfo(null);
  }, [formData.paymentMethod?.id, totalAmount]);

  const availablePaymentMethods = formData.deliveryType === 'pickup'
    ? paymentMethods.filter((m: any) => m.availableForPickup !== false)
    : paymentMethods.filter((m: any) => m.availableForDelivery !== false);

  useEffect(() => {
    if (availablePaymentMethods.length > 0 && !formData.paymentMethod) {
      const def = availablePaymentMethods.find((m: any) => m.isDefault) || availablePaymentMethods[0];
      if (def) setFormData((p) => ({ ...p, paymentMethod: def }));
    }
  }, [availablePaymentMethods.length]);

  const calculateDeliveryCost = async (address: string) => {
    if (!address.trim() || formData.deliveryType !== 'courier') {
      setDeliveryCost(null);
      setDeliveryValidation(null);
      return;
    }
    setIsCalculatingDelivery(true);
    try {
      const addr = defaultCity && !address.toLowerCase().includes(defaultCity.toLowerCase()) ? `${defaultCity}, ${address}` : address;
      const result = await deliverySettingsAPI.calculateCost(addr, totalAmount);
      if (result.valid) {
        setDeliveryCost(result.cost ?? null);
        setDeliveryValidation({ valid: true, address: result.address });
      } else {
        setDeliveryCost(null);
        setDeliveryValidation({ valid: false, error: result.error });
      }
    } catch {
      setDeliveryCost(null);
      setDeliveryValidation({ valid: false, error: 'Ошибка проверки адреса' });
    } finally {
      setIsCalculatingDelivery(false);
    }
  };

  useEffect(() => {
    const t = setTimeout(() => {
      if (formData.deliveryType === 'courier' && formData.address.trim()) calculateDeliveryCost(formData.address);
      else setDeliveryCost(null), setDeliveryValidation(null);
    }, 1000);
    return () => clearTimeout(t);
  }, [formData.address, formData.deliveryType, defaultCity]);

  if (items.length === 0) {
    navigate('/cart');
    return null;
  }

  if (!isAuth) {
    return (
      <div className="container mx-auto flex flex-col items-center justify-center px-4 py-24">
        <h1 className="text-2xl font-bold">Оформление заказа</h1>
        <p className="mt-4 text-center text-muted-foreground">Войдите, чтобы оформить заказ</p>
        <Button className="mt-6" onClick={() => setShowAuth(true)}>Войти по номеру телефона</Button>
        <Button asChild variant="outline" className="mt-4">
          <Link to="/cart">Вернуться в корзину</Link>
        </Button>
        {showAuth && <AuthModal onClose={() => setShowAuth(false)} onSuccess={() => setShowAuth(false)} />}
      </div>
    );
  }

  const validateStep = async (s: Step): Promise<boolean> => {
    if (s === 1) {
      if (getPhoneDigits(formData.phone).length < 11) {
        toast.error('Введите корректный номер телефона');
        return false;
      }
    }
    if (s === 2) {
      if (formData.deliveryType === 'courier') {
        if (!formData.address.trim()) { toast.error('Введите адрес'); return false; }
        if (deliveryValidation && !deliveryValidation.valid) { toast.error(deliveryValidation.error); return false; }
        if (totalAmount < minDeliveryOrderTotal) {
          toast.error(`Минимальный заказ на доставку — ${minDeliveryOrderTotal.toLocaleString('ru-RU')} ₽`);
          return false;
        }
      }
      if (!formData.deliveryDate) { toast.error('Выберите дату'); return false; }
      if (!formData.deliveryTimeSlot) { toast.error('Выберите время'); return false; }
    }
    if (s === 3 && !formData.paymentMethod) {
      toast.error('Выберите способ оплаты');
      return false;
    }
    return true;
  };

  const handleSubmit = async () => {
    if (!(await validateStep(step))) return;
    if (step < 4) {
      setStep((p) => (p + 1) as Step);
      return;
    }

    setIsLoading(true);
    try {
      const phoneDigits = getPhoneDigits(formData.phone);
      const deliveryCostFinal = formData.deliveryType === 'courier' && deliveryCost ? deliveryCost : 0;
      const itemsTotal = discountInfo?.final_amount || totalAmount;
      const grandTotal = itemsTotal + deliveryCostFinal;
      const deliveryTimeStr = formData.deliveryDate && formData.deliveryTimeSlot
        ? `${format(new Date(formData.deliveryDate), 'd MMMM', { locale: ru })}, ${formData.deliveryTimeSlot}`
        : '';

      const orderData = {
        phone: phoneDigits,
        name: formData.name || undefined,
        deliveryAddress: formData.deliveryType === 'pickup' ? 'Самовывоз' : (deliveryValidation?.address || formData.address),
        deliveryTime: deliveryTimeStr,
        deliveryType: formData.deliveryType,
        deliveryCost: deliveryCostFinal,
        comment: formData.comment || undefined,
        paymentMethod: formData.paymentMethod?.code || null,
        items: items.map((i) => ({
          productId: i.product.id,
          productName: i.product.name,
          productImage: i.product.imageUrl,
          quantity: i.quantity,
          unitPrice: i.product.price,
        })),
        totalAmount: grandTotal,
        originalAmount: totalAmount,
        discount: discountInfo?.discount || 0,
      };

      const order = await createOrder(orderData);
      clearCart();

      const code = formData.paymentMethod?.code?.toLowerCase();
      if (code === 'yookassa') {
        const returnUrl = `${window.location.origin}/orders/${order.orderId}?payment=success`;
        const paymentData = await paymentAPI.createYooKassaPayment(
          Number(order.id), grandTotal, returnUrl, `Оплата #${order.orderId}`, undefined, user?.email
        );
        const url = paymentData?.confirmation_url || paymentData?.data?.confirmation_url;
        if (url) window.location.href = url;
        else navigate(`/orders/${order.orderId}`);
      } else {
        toast.success('Заказ оформлен!');
        navigate(`/orders/${order.orderId}?success=true`);
      }
    } catch (e: any) {
      toast.error(e?.message || 'Ошибка оформления');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="container mx-auto px-4 py-8 max-w-2xl">
      <h1 className="text-2xl font-bold mb-6">Оформление заказа</h1>

      <div className="flex gap-2 mb-6">
        {[1, 2, 3, 4].map((s) => (
          <div key={s} className={`h-2 flex-1 rounded-full ${s <= step ? 'bg-primary' : 'bg-muted'}`} />
        ))}
      </div>

      {step === 1 && (
        <div className="space-y-4">
          <div>
            <label className="block text-sm text-muted-foreground mb-1">Телефон *</label>
            <input
              type="tel"
              value={formData.phone}
              onChange={(e) => setFormData({ ...formData, phone: formatPhone(e.target.value) })}
              className="w-full rounded-xl border bg-muted px-4 py-3"
              placeholder="+7 (___) ___-__-__"
            />
          </div>
          <div>
            <label className="block text-sm text-muted-foreground mb-1">Имя</label>
            <input
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              className="w-full rounded-xl border bg-muted px-4 py-3"
              placeholder="Как к вам обращаться"
            />
          </div>
        </div>
      )}

      {step === 2 && (
        <div className="space-y-4">
          <div>
            <label className="block text-sm mb-1">Тип доставки</label>
            <select
              value={formData.deliveryType}
              onChange={(e) => setFormData({ ...formData, deliveryType: e.target.value as DeliveryType })}
              className="w-full rounded-xl border bg-muted px-4 py-3"
            >
              <option value="courier">Курьер</option>
              <option value="pickup">Самовывоз</option>
            </select>
          </div>
          {formData.deliveryType === 'courier' && (
            <>
              <div>
                <label className="block text-sm mb-1">Адрес *</label>
                <input
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  className={`w-full rounded-xl border px-4 py-3 ${deliveryValidation && !deliveryValidation.valid ? 'border-destructive' : ''}`}
                  placeholder="г. Екатеринбург, ул., д."
                />
                {isCalculatingDelivery && <p className="text-xs mt-1">Проверка адреса...</p>}
                {deliveryValidation && !deliveryValidation.valid && <p className="text-xs text-destructive">{deliveryValidation.error}</p>}
                {deliveryValidation?.valid && deliveryCost != null && (
                  <p className="text-sm text-primary mt-1">
                    {deliveryCost === 0 ? 'Бесплатно' : `${deliveryCost.toLocaleString('ru-RU')} ₽`}
                  </p>
                )}
              </div>
              {totalAmount < minDeliveryOrderTotal && (
                <p className="text-sm text-destructive">Минимум {minDeliveryOrderTotal.toLocaleString('ru-RU')} ₽ для доставки</p>
              )}
            </>
          )}
          <div>
            <label className="block text-sm mb-1">Дата *</label>
            <Popover>
              <PopoverTrigger asChild>
                <Button variant="outline" className="w-full justify-start">
                  <CalendarIcon className="mr-2 h-4 w-4" />
                  {formData.deliveryDate ? format(new Date(formData.deliveryDate), 'PPP', { locale: ru }) : 'Выберите дату'}
                </Button>
              </PopoverTrigger>
              <PopoverContent>
                <Calendar
                  mode="single"
                  selected={formData.deliveryDate ? new Date(formData.deliveryDate) : undefined}
                  onSelect={(d) => d && setFormData({ ...formData, deliveryDate: format(d, 'yyyy-MM-dd'), deliveryTimeSlot: '' })}
                  disabled={(d) => d < today || d > maxDate}
                />
              </PopoverContent>
            </Popover>
          </div>
          {formData.deliveryDate && (
            <div>
              <label className="block text-sm mb-1">Время *</label>
              <select
                value={formData.deliveryTimeSlot}
                onChange={(e) => setFormData({ ...formData, deliveryTimeSlot: e.target.value })}
                className="w-full rounded-xl border bg-muted px-4 py-3"
              >
                <option value="">Выберите время</option>
                {getTimeSlotsForDate(formData.deliveryDate).map((slot) => (
                  <option key={slot} value={slot}>{slot}</option>
                ))}
              </select>
            </div>
          )}
          <div>
            <label className="block text-sm mb-1">Комментарий</label>
            <textarea
              value={formData.comment}
              onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
              className="w-full rounded-xl border bg-muted px-4 py-3"
              rows={2}
              placeholder="Дополнительные пожелания"
            />
          </div>
        </div>
      )}

      {step === 3 && (
        <div className="space-y-3">
          {availablePaymentMethods.map((m: any) => (
            <button
              key={m.id}
              type="button"
              onClick={() => setFormData({ ...formData, paymentMethod: m })}
              className={`w-full rounded-xl border-2 p-4 text-left ${formData.paymentMethod?.id === m.id ? 'border-primary bg-primary/5' : 'border-border'}`}
            >
              <div className="flex items-center gap-2">
                <div className={`h-4 w-4 rounded-full border-2 ${formData.paymentMethod?.id === m.id ? 'border-primary bg-primary' : ''}`} />
                <span className="font-medium">{m.name}</span>
              </div>
            </button>
          ))}
        </div>
      )}

      {step === 4 && (
        <div className="space-y-4">
          <div className="rounded-xl border p-4">
            <h3 className="font-semibold mb-2">Ваш заказ</h3>
            {items.map((i) => (
              <div key={i.product.id} className="flex justify-between text-sm py-1">
                <span>{i.product.name} × {i.quantity}</span>
                <span>{(i.product.price * i.quantity).toLocaleString('ru-RU')} ₽</span>
              </div>
            ))}
          </div>
          <div className="rounded-xl border p-4">
            <p className="text-sm text-muted-foreground">Адрес: {formData.deliveryType === 'courier' ? (deliveryValidation?.address || formData.address) : 'Самовывоз'}</p>
            <p className="text-sm text-muted-foreground">Время: {formData.deliveryDate && formData.deliveryTimeSlot ? `${format(new Date(formData.deliveryDate), 'd MMM', { locale: ru })}, ${formData.deliveryTimeSlot}` : '-'}</p>
            <p className="text-sm text-muted-foreground">Оплата: {formData.paymentMethod?.name}</p>
          </div>
          <div className="rounded-xl bg-muted p-4">
            <div className="flex justify-between">
              <span>К оплате</span>
              <span className="text-xl font-bold">
                {((discountInfo?.final_amount ?? totalAmount) + (formData.deliveryType === 'courier' && deliveryCost ? deliveryCost : 0)).toLocaleString('ru-RU')} ₽
              </span>
            </div>
          </div>
        </div>
      )}

      <div className="flex gap-3 mt-8">
        <Button variant="outline" className="flex-1" onClick={() => step === 1 ? navigate('/cart') : setStep((p) => (p - 1) as Step)}>
          {step === 1 ? 'Назад' : 'Отмена'}
        </Button>
        <Button className="flex-1" onClick={handleSubmit} disabled={isLoading}>
          {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : step < 4 ? 'Далее' : 'Оформить заказ'}
        </Button>
      </div>
    </div>
  );
}
