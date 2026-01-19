import { useState, useEffect, useMemo } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { useCartStore } from '@/store/cartStore';
import { useOrders } from '@/hooks/useOrders';
import { toast } from 'sonner';
import { Loader2, Check, CalendarIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { getTelegramUser, hapticFeedback } from '@/lib/telegram';
import { ordersAPI, paymentMethodsAPI, paymentAPI, deliverySettingsAPI } from '@/api';
import { format } from 'date-fns';
import { ru } from 'date-fns/locale';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';

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

type Step = 1 | 2 | 3 | 4;
type DeliveryType = 'courier' | 'pickup';

export function CheckoutPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { items, getTotalAmount, clearCart } = useCartStore();
  const { createOrder, loadOrders } = useOrders();
  const totalAmount = getTotalAmount();

  // Получение orderMode из state или localStorage для начального значения
  const getInitialDeliveryType = (): DeliveryType => {
    if (location.state?.orderMode === 'delivery') return 'courier';
    if (location.state?.orderMode === 'pickup') return 'pickup';
    const saved = localStorage.getItem('orderMode');
    if (saved === 'delivery') return 'courier';
    if (saved === 'pickup') return 'pickup';
    return 'courier'; // Дефолт из формы
  };

  const [step, setStep] = useState<Step>(1);
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingPastOrders, setIsLoadingPastOrders] = useState(false);
  const [deliveryCost, setDeliveryCost] = useState<number | null>(null);
  const [isCalculatingDelivery, setIsCalculatingDelivery] = useState(false);
  const [deliveryValidation, setDeliveryValidation] = useState<{
    valid: boolean;
    address?: string;
    distance?: number;
    zone?: string;
    error?: string;
  } | null>(null);
  // Подсказки адресов отключены (Яндекс Suggest API требует коммерческий договор)
  // const [addressSuggestions, setAddressSuggestions] = useState<Array<{ value: string; display: string }>>([]);
  // const [showSuggestions, setShowSuggestions] = useState(false);
  const [addressInputRef, setAddressInputRef] = useState<HTMLInputElement | null>(null);
  const [defaultCity, setDefaultCity] = useState<string>('Екатеринбург');
  const [formData, setFormData] = useState(() => ({
    phone: '',
    name: '',
    address: '',
    deliveryDate: '',
    deliveryTimeSlot: '',
    deliveryType: getInitialDeliveryType(),
    comment: '',
    paymentMethod: null as any | null,
  }));
  
  // Минимальная дата (сегодня)
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // Максимальная дата (2 месяца вперед)
  const maxDate = new Date();
  maxDate.setMonth(maxDate.getMonth() + 2);
  maxDate.setHours(23, 59, 59, 999);
  
  // Генерация временных слотов для выбранной даты
  const getTimeSlotsForDate = (selectedDate: string): string[] => {
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
    
    if (!selectedDate) {
      return [];
    }
    
    const now = new Date();
    const selected = new Date(selectedDate + 'T00:00:00'); // Устанавливаем время на начало дня для корректного сравнения
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selected.setHours(0, 0, 0, 0);
    
    const isToday = selected.getTime() === today.getTime();
    const isPast = selected < today;
    
    // Если дата в прошлом, возвращаем пустой массив
    if (isPast) {
      return [];
    }
    
    if (!isToday) {
      // Если не сегодня, все слоты доступны
      return slots;
    }
    
    // Если сегодня, фильтруем прошедшие слоты
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();
    
    return slots.filter((slot) => {
      const [startTime] = slot.split('-');
      const [hour, minute] = startTime.split(':').map(Number);
      
      // Исключаем слоты, которые уже прошли
      if (hour < currentHour) return false;
      if (hour === currentHour && minute < currentMinute) return false;
      
      // Исключаем текущий час (запас 1 час)
      if (hour === currentHour) return false;
      
      return true;
    });
  };
  
  const [paymentMethods, setPaymentMethods] = useState<any[]>([]);
  const [loadingPaymentMethods, setLoadingPaymentMethods] = useState(false);
  const [discountInfo, setDiscountInfo] = useState<{ discount: number; final_amount: number; applied: boolean } | null>(null);
  const [minDeliveryOrderTotal, setMinDeliveryOrderTotal] = useState<number>(3000);

  // Загрузка настроек доставки для получения города по умолчанию
  useEffect(() => {
    const loadDeliverySettings = async () => {
      try {
        const settings = await deliverySettingsAPI.getSettings();
        if (settings) {
          if (settings.default_city) {
            setDefaultCity(settings.default_city);
          }
          if (settings.min_delivery_order_total_rub !== undefined) {
            setMinDeliveryOrderTotal(settings.min_delivery_order_total_rub);
          }
        }
      } catch (error) {
        console.error('Error loading delivery settings:', error);
        // Используем значение по умолчанию
      }
    };
    loadDeliverySettings();
  }, []);

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
          let phoneValue = '';
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
            phone: phoneValue || prev.phone,
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

  // Загрузка способов оплаты
  useEffect(() => {
    const loadPaymentMethods = async () => {
      setLoadingPaymentMethods(true);
      try {
        const methods = await paymentMethodsAPI.getAll();
        setPaymentMethods(methods);
      } catch (error) {
        console.error('CheckoutPage - Failed to load payment methods:', error);
        toast.error('Не удалось загрузить способы оплаты');
      } finally {
        setLoadingPaymentMethods(false);
      }
    };
    loadPaymentMethods();
  }, []);

  // Расчет скидки при изменении способа оплаты или суммы корзины
  useEffect(() => {
    const calculateDiscount = async () => {
      if (formData.paymentMethod && formData.paymentMethod.id && totalAmount > 0) {
        try {
          const methodInfo = await paymentMethodsAPI.getById(formData.paymentMethod.id, totalAmount);
          if (methodInfo) {
            // Обновляем информацию о способе оплаты с расчетом скидки
            setFormData(prev => ({
              ...prev,
              paymentMethod: {
                ...prev.paymentMethod,
                ...methodInfo,
              },
            }));
            
            if (methodInfo.discount) {
              setDiscountInfo(methodInfo.discount);
            } else {
              setDiscountInfo(null);
            }
          } else {
            setDiscountInfo(null);
          }
        } catch (error) {
          console.error('Failed to calculate discount:', error);
          setDiscountInfo(null);
        }
      } else {
        setDiscountInfo(null);
      }
    };
    
    calculateDiscount();
  }, [formData.paymentMethod?.id, totalAmount]);

  // Фильтрация способов оплаты по доступности
  const availablePaymentMethods = useMemo(() => {
    if (formData.deliveryType === 'pickup') {
      return paymentMethods.filter((method: any) => method.availableForPickup !== false);
    } else {
      return paymentMethods.filter((method: any) => method.availableForDelivery !== false);
    }
  }, [paymentMethods, formData.deliveryType]);

  // Автоматический выбор доступного способа оплаты при изменении типа доставки
  useEffect(() => {
    if (availablePaymentMethods.length > 0 && !formData.paymentMethod) {
      // Ищем способ оплаты по умолчанию
      const defaultMethod = availablePaymentMethods.find((m: any) => m.isDefault);
      if (defaultMethod) {
        setFormData(prev => ({
          ...prev,
          paymentMethod: defaultMethod,
        }));
      } else {
        // Выбираем первый доступный способ оплаты
        setFormData(prev => ({
          ...prev,
          paymentMethod: availablePaymentMethods[0],
        }));
      }
    } else if (formData.paymentMethod) {
      // Проверяем, что выбранный способ оплаты доступен для текущего типа доставки
      const isCurrentMethodAvailable = availablePaymentMethods.some((m: any) => m.id === formData.paymentMethod?.id);
      if (!isCurrentMethodAvailable) {
        // Выбираем первый доступный способ оплаты
        const defaultMethod = availablePaymentMethods.find((m: any) => m.isDefault) || availablePaymentMethods[0];
        if (defaultMethod) {
          setFormData(prev => ({
            ...prev,
            paymentMethod: defaultMethod,
          }));
        }
      }
    }
  }, [availablePaymentMethods, formData.deliveryType]);

  // Имя заполняется только из прошлых заказов, не из Telegram

  if (items.length === 0) {
    navigate('/cart');
    return null;
  }

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatPhone(e.target.value);
    setFormData({ ...formData, phone: formatted });
  };

  // Подсказки адресов отключены (Яндекс Suggest API требует коммерческий договор)
  // Пользователи вводят адрес вручную, валидация происходит через Геокодер

  // Обработчик изменения адреса
  const handleAddressChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setFormData({ ...formData, address: value });
    setDeliveryValidation(null);
    setDeliveryCost(null);
  };

  // Расчет стоимости доставки
  const calculateDeliveryCost = async (address: string) => {
    if (!address.trim() || formData.deliveryType !== 'courier') {
      setDeliveryCost(null);
      setDeliveryValidation(null);
      return;
    }

    setIsCalculatingDelivery(true);
    try {
      // Добавляем город по умолчанию к адресу для поиска
      const addressWithCity = defaultCity && !address.toLowerCase().includes(defaultCity.toLowerCase())
        ? `${defaultCity}, ${address}`
        : address;
      
      const result = await deliverySettingsAPI.calculateCost(addressWithCity, totalAmount);
      if (result.valid) {
        setDeliveryCost(result.cost || null);
        setDeliveryValidation({
          valid: true,
          address: result.address,
          distance: result.distance,
          zone: result.zone,
        });
      } else {
        setDeliveryCost(null);
        setDeliveryValidation({
          valid: false,
          error: result.error || 'Ошибка валидации адреса',
        });
      }
    } catch (error: any) {
      console.error('Error calculating delivery cost:', error);
      setDeliveryCost(null);
      setDeliveryValidation({
        valid: false,
        error: 'Ошибка при расчете стоимости доставки',
      });
    } finally {
      setIsCalculatingDelivery(false);
    }
  };

  // Debounce для расчета стоимости доставки
  useEffect(() => {
    const timer = setTimeout(() => {
      if (formData.deliveryType === 'courier' && formData.address.trim()) {
        calculateDeliveryCost(formData.address);
      } else {
        setDeliveryCost(null);
        setDeliveryValidation(null);
      }
    }, 1000); // Задержка 1 секунда после ввода

    return () => clearTimeout(timer);
  }, [formData.address, formData.deliveryType, defaultCity]);

  const validateStep = async (currentStep: Step): Promise<boolean> => {
    if (currentStep === 1) {
      const phoneDigits = getPhoneDigits(formData.phone);
      if (phoneDigits.length < 11) {
        toast.error('Введите корректный номер телефона');
        return false;
      }
    }
    if (currentStep === 2) {
      if (formData.deliveryType === 'courier') {
        if (!formData.address.trim()) {
          toast.error('Введите адрес доставки');
          return false;
        }
        
        // Проверяем валидность адреса
        if (deliveryValidation && !deliveryValidation.valid) {
          toast.error(deliveryValidation.error || 'Адрес не найден. Проверьте правильность адреса');
          return false;
        }
        
        // Если еще не рассчитано, делаем расчет
        if (deliveryValidation === null && !isCalculatingDelivery) {
          setIsCalculatingDelivery(true);
          try {
            // Добавляем город по умолчанию к адресу для поиска
            const addressWithCity = defaultCity && !formData.address.toLowerCase().includes(defaultCity.toLowerCase())
              ? `${defaultCity}, ${formData.address}`
              : formData.address;
            
            const result = await deliverySettingsAPI.calculateCost(addressWithCity, totalAmount);
            if (!result.valid) {
              toast.error(result.error || 'Адрес не найден. Проверьте правильность адреса');
              setIsCalculatingDelivery(false);
              return false;
            }
            setDeliveryCost(result.cost || null);
            setDeliveryValidation({
              valid: true,
              address: result.address,
              distance: result.distance,
              zone: result.zone,
            });
          } catch (error: any) {
            toast.error('Ошибка при проверке адреса');
            setIsCalculatingDelivery(false);
            return false;
          } finally {
            setIsCalculatingDelivery(false);
          }
        }
        // Проверка минимальной суммы для доставки
        if (totalAmount < minDeliveryOrderTotal) {
          toast.error(`Минимальный заказ на доставку — ${minDeliveryOrderTotal.toLocaleString('ru-RU')} ₽. Добавьте товаров еще на ${(minDeliveryOrderTotal - totalAmount).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`);
          return false;
        }
      }
      
      // Проверка обязательного выбора даты и времени доставки
      if (!formData.deliveryDate) {
        toast.error('Выберите дату доставки');
        return false;
      }
      
      if (!formData.deliveryTimeSlot) {
        toast.error('Выберите время доставки');
        return false;
      }
    }
    if (currentStep === 3) {
      if (!formData.paymentMethod) {
        toast.error('Выберите способ оплаты');
        return false;
      }
    }
    return true;
  };

  const handleNext = async () => {
    if (await validateStep(step)) {
      // Если переходим на шаг 3 (способ оплаты), загружаем информацию о скидке
      if (step === 2 && formData.paymentMethod) {
        try {
          const methodInfo = await paymentMethodsAPI.getById(formData.paymentMethod.id, totalAmount);
          if (methodInfo) {
            setFormData(prev => ({
              ...prev,
              paymentMethod: methodInfo,
            }));
          }
        } catch (error) {
          console.error('Failed to load payment method details:', error);
        }
      }
      setStep((prev) => Math.min(prev + 1, 4) as Step);
    }
  };

  const handleBack = () => {
    if (step === 1) {
      navigate('/cart');
    } else {
      setStep((prev) => (prev - 1) as Step);
    }
  };

  // Получить текст кнопки оплаты в зависимости от способа оплаты
  const getPaymentButtonText = (): string => {
    if (!formData.paymentMethod) {
      return 'Оформить заказ';
    }
    
    const paymentCode = formData.paymentMethod.code?.toLowerCase();
    
    switch (paymentCode) {
      case 'yookassa':
        return 'Оплатить через ЮKassa';
      case 'cash':
        return 'Оформить заказ';
      default:
        return `Оплатить через ${formData.paymentMethod.name}`;
    }
  };

  const handleSubmit = async () => {
    setIsLoading(true);
    hapticFeedback('medium');
    
    try {
      // Подготовка данных заказа
      const phoneDigits = getPhoneDigits(formData.phone);
      const deliveryCostFinal = formData.deliveryType === 'courier' && deliveryCost ? deliveryCost : 0;
      const finalAmount = (discountInfo?.final_amount || totalAmount) + deliveryCostFinal;
      
      // Формируем строку времени доставки
      let deliveryTimeStr: string | undefined;
      if (formData.deliveryDate && formData.deliveryTimeSlot) {
        // Форматируем дату и время
        const date = new Date(formData.deliveryDate);
        const dateStr = date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
        deliveryTimeStr = `${dateStr}, ${formData.deliveryTimeSlot}`;
      }
      
      const orderData = {
        phone: phoneDigits,
        name: formData.name || undefined,
        deliveryAddress: formData.deliveryType === 'pickup' ? 'Самовывоз' : (deliveryValidation?.address || formData.address),
        deliveryTime: deliveryTimeStr,
        deliveryType: formData.deliveryType,
        deliveryCost: deliveryCostFinal,
        comment: formData.comment || undefined,
        paymentMethod: formData.paymentMethod?.code || null,
        items: items.map(item => ({
          productId: item.product.id,
          productName: item.product.name,
          productImage: item.product.imageUrl,
          quantity: item.quantity,
          unitPrice: item.product.price,
        })),
        totalAmount: finalAmount,
        originalAmount: totalAmount,
        discount: discountInfo?.discount || 0,
      };

      const paymentCode = formData.paymentMethod?.code?.toLowerCase();
      
      // Для наличных - просто создаем заказ
      if (paymentCode === 'cash') {
        const order = await createOrder(orderData);
        
        // Очищаем корзину
        clearCart();
        hapticFeedback('success');
        
        // Переходим на страницу успеха
        navigate(`/orders/${order.orderId}?success=true`);
        toast.success('Заказ успешно оформлен!');
        return;
      }
      
      // Для ЮКассы и других способов оплаты - создаем заказ
      const order = await createOrder(orderData);
      
      // Очищаем корзину
      clearCart();
      hapticFeedback('success');
      
      // Для ЮКассы - создаем платеж и переходим к оплате
      if (paymentCode === 'yookassa') {
        try {
          // Получаем telegram_id для проверки владельца заказа
          const user = getTelegramUser();
          const telegramId = user?.id || order.telegramId;
          
          // Пытаемся получить email из Telegram WebApp для отправки квитанции
          const telegramEmail = window.Telegram?.WebApp?.initDataUnsafe?.user?.email;
          
          // Создаем платеж через ЮKassa
          const returnUrl = `${window.location.origin}/orders/${order.orderId}?payment=success`;
          const paymentData = await paymentAPI.createYooKassaPayment(
            Number(order.id),
            finalAmount,
            returnUrl,
            `Оплата заказа #${order.orderId}`,
            telegramId,
            telegramEmail // Передаем email для квитанции
          );

          console.log('Payment data received:', paymentData);
          
          // Проверяем, используется ли тестовый режим
          const isTestMode = paymentData?.data?.is_test_mode ?? false;
          if (isTestMode) {
            console.log('⚠️ Тестовый режим активен - используется тестовый магазин YooKassa');
          }

          // Получаем URL для оплаты (проверяем разные варианты структуры ответа)
          const confirmationUrl = 
            paymentData?.data?.confirmation_url || 
            paymentData?.data?.yookassa_payment?.confirmation?.confirmation_url ||
            paymentData?.yookassa_payment?.confirmation?.confirmation_url ||
            paymentData?.confirmation_url;
          
          console.log('Confirmation URL:', confirmationUrl);
          console.log('Test mode:', isTestMode);
          
          if (confirmationUrl) {
            // Перенаправляем на страницу оплаты ЮKassa
            console.log('Redirecting to:', confirmationUrl);
            
            // Показываем информацию о тестовом режиме пользователю
            if (isTestMode) {
              toast.info('Тестовый режим: используется тестовая среда YooKassa', {
                duration: 3000,
              });
            }
            
            window.location.href = confirmationUrl;
            toast.success('Переход к оплате...');
          } else {
            // Если URL не получен, переходим на страницу заказа
            console.error('Confirmation URL not found in response:', paymentData);
            navigate(`/orders/${order.orderId}?payment=yookassa`);
            toast.error('Ошибка: URL для оплаты не получен. Заказ создан.');
          }
        } catch (paymentError: any) {
          console.error('Ошибка при создании платежа ЮKassa:', paymentError);
          toast.error('Ошибка при создании платежа. Заказ создан, но оплата не была инициирована.');
          navigate(`/orders/${order.orderId}?payment=error`);
        }
      } else {
        // Для других способов оплаты - переходим на страницу успеха
        navigate(`/orders/${order.orderId}?success=true`);
        toast.success('Заказ успешно оформлен!');
      }
    } catch (err) {
      hapticFeedback('error');
      toast.error('Ошибка при оформлении заказа');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-background pb-32">
      <MiniAppHeader title="Оформление заказа" showBack />

      {/* Progress Steps */}
      <div className="px-4 py-4">
        <div className="flex items-center justify-center gap-2">
          {[1, 2, 3, 4].map((s) => (
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
              {s < 4 && (
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
          Шаг {step} из 4
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
              <div className="space-y-2">
                {/* Проверка минимальной суммы для доставки */}
                {totalAmount < minDeliveryOrderTotal && (
                  <div className="rounded-lg border border-destructive/20 bg-destructive/10 p-4 space-y-2">
                    <p className="text-sm font-semibold text-destructive">
                      Минимальный заказ на доставку — {minDeliveryOrderTotal.toLocaleString('ru-RU')} ₽
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Добавьте товаров еще на{' '}
                      <span className="font-semibold text-foreground">
                        {(minDeliveryOrderTotal - totalAmount).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽
                      </span>
                    </p>
                    <button
                      onClick={() => {
                        setFormData({ ...formData, deliveryType: 'pickup' });
                        toast.info('Переключено на самовывоз');
                      }}
                      className="text-sm text-primary hover:underline font-medium touch-feedback"
                    >
                      Переключиться на самовывоз
                    </button>
                  </div>
                )}
                
                <label className="mb-1.5 block text-sm text-muted-foreground">
                  Адрес доставки *
                </label>
                <div className="relative">
                  <input
                    type="text"
                    placeholder="г. Екатеринбург, ул., д., кв."
                    value={formData.address}
                    onChange={handleAddressChange}
                    className={`w-full h-11 rounded-lg border bg-background px-4 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 ${
                      deliveryValidation && !deliveryValidation.valid
                        ? 'border-destructive focus:border-destructive'
                        : deliveryValidation?.valid
                        ? 'border-green-500 focus:border-primary'
                        : 'border-border focus:border-primary'
                    }`}
                  />
                </div>
                {isCalculatingDelivery && (
                  <p className="text-xs text-muted-foreground flex items-center gap-2">
                    <Loader2 className="h-3 w-3 animate-spin" />
                    Проверка адреса...
                  </p>
                )}
                {deliveryValidation && !deliveryValidation.valid && (
                  <p className="text-xs text-destructive">{deliveryValidation.error}</p>
                )}
                {deliveryValidation?.valid && deliveryCost !== null && (
                  <div className="space-y-1">
                    {deliveryValidation.address && deliveryValidation.address !== formData.address && (
                      <p className="text-xs text-muted-foreground">
                        Найден адрес: {deliveryValidation.address}
                      </p>
                    )}
                    {deliveryValidation.distance && (
                      <p className="text-xs text-muted-foreground">
                        Расстояние: {deliveryValidation.distance} км
                        {deliveryValidation.zone && ` (${deliveryValidation.zone})`}
                      </p>
                    )}
                    <p className="text-sm font-semibold text-primary">
                      {deliveryCost === 0 ? 'Доставка бесплатна' : `Стоимость доставки: ${deliveryCost.toLocaleString('ru-RU')} ₽`}
                    </p>
                  </div>
                )}
              </div>
            )}

            <div>
              <label className="mb-1.5 block text-sm text-muted-foreground">
                Время доставки *
              </label>
              
              {/* Выбор даты и времени */}
              <div className="space-y-3">
                {/* Выбор даты через календарь */}
                <div>
                  <label className="mb-1.5 block text-sm text-muted-foreground">
                    Дата доставки *
                  </label>
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant="outline"
                        className={cn(
                          "w-full justify-start text-left font-normal h-11",
                          !formData.deliveryDate && "text-muted-foreground"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {formData.deliveryDate ? (
                          format(new Date(formData.deliveryDate), "PPP", { locale: ru })
                        ) : (
                          <span>Выберите дату</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={formData.deliveryDate ? new Date(formData.deliveryDate) : undefined}
                        onSelect={(date) => {
                          if (date) {
                            const dateStr = format(date, "yyyy-MM-dd");
                            setFormData({ 
                              ...formData, 
                              deliveryDate: dateStr,
                              deliveryTimeSlot: '', // Сбрасываем время при смене даты
                            });
                          }
                        }}
                        disabled={(date) => {
                          // Отключаем прошлые даты и даты после максимума
                          const dateStart = new Date(date);
                          dateStart.setHours(0, 0, 0, 0);
                          return dateStart < today || dateStart > maxDate;
                        }}
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
                </div>
                
                {/* Выбор времени */}
                {formData.deliveryDate && (
                  <div>
                    <label className="mb-1.5 block text-sm text-muted-foreground">
                      Временной интервал *
                    </label>
                    <select
                      value={formData.deliveryTimeSlot}
                      onChange={(e) => setFormData({ 
                        ...formData, 
                        deliveryTimeSlot: e.target.value,
                      })}
                      className="w-full h-11 rounded-lg border border-border bg-background px-4 text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                      <option value="">Выберите время</option>
                      {getTimeSlotsForDate(formData.deliveryDate).map((slot) => (
                        <option key={slot} value={slot}>
                          {slot}
                        </option>
                      ))}
                    </select>
                    {getTimeSlotsForDate(formData.deliveryDate).length === 0 && (
                      <p className="mt-1 text-xs text-destructive">
                        Нет доступных временных слотов на выбранную дату
                      </p>
                    )}
                  </div>
                )}
              </div>
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

        {/* Step 3: Payment Method */}
        {step === 3 && (
          <div className="space-y-4 animate-fade-in">
            <h2 className="text-xl font-semibold text-foreground">Способ оплаты</h2>
            
            {loadingPaymentMethods ? (
              <div className="flex items-center justify-center py-8">
                <Loader2 className="h-6 w-6 animate-spin text-primary" />
              </div>
            ) : (
              <div className="space-y-3">
                {availablePaymentMethods.map((method) => {
                  const isSelected = formData.paymentMethod?.id === method.id;
                  const methodDiscount = method.discount || {};
                  const hasDiscount = methodDiscount.applied && methodDiscount.discount > 0;
                  
                  return (
                    <div
                      key={method.id}
                      onClick={async () => {
                        hapticFeedback('light');
                        // Загружаем информацию о скидке при выборе
                        try {
                          const methodInfo = await paymentMethodsAPI.getById(method.id, totalAmount);
                          if (methodInfo) {
                            setFormData(prev => ({
                              ...prev,
                              paymentMethod: methodInfo,
                            }));
                          } else {
                            setFormData(prev => ({
                              ...prev,
                              paymentMethod: method,
                            }));
                          }
                        } catch (error) {
                          console.error('Failed to load payment method details:', error);
                          setFormData(prev => ({
                            ...prev,
                            paymentMethod: method,
                          }));
                        }
                      }}
                      className={cn(
                        'rounded-xl border-2 p-4 cursor-pointer transition-all touch-feedback',
                        isSelected
                          ? 'border-primary bg-primary/5'
                          : 'border-border bg-card hover:border-primary/50'
                      )}
                    >
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <div className={cn(
                              'h-5 w-5 rounded-full border-2 flex items-center justify-center',
                              isSelected
                                ? 'border-primary bg-primary'
                                : 'border-muted-foreground'
                            )}>
                              {isSelected && (
                                <div className="h-2 w-2 rounded-full bg-primary-foreground" />
                              )}
                            </div>
                            <h3 className="font-semibold text-foreground">{method.name}</h3>
                          </div>
                          {method.description && (
                            <p className="mt-1 text-sm text-muted-foreground ml-7">
                              {method.description}
                            </p>
                          )}
                          {isSelected && formData.paymentMethod?.notification && (
                            <div className="mt-2 ml-7 p-2 rounded-lg bg-primary/10 border border-primary/20">
                              <p className="text-sm text-foreground">{formData.paymentMethod.notification}</p>
                            </div>
                          )}
                        </div>
                        {isSelected && discountInfo && discountInfo.applied && discountInfo.discount > 0 && (
                          <div className="ml-2 text-right">
                            <div className="text-xs text-muted-foreground">Скидка</div>
                            <div className="text-sm font-semibold text-primary">
                              -{discountInfo.discount.toLocaleString('ru-RU')} ₽
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        )}

        {/* Step 4: Confirmation */}
        {step === 4 && (
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
                  <>
                    <div className="flex justify-between">
                      <span className="text-muted-foreground">Адрес</span>
                      <span className="text-foreground text-right max-w-[200px]">
                        {deliveryValidation?.address || formData.address}
                      </span>
                    </div>
                    {deliveryCost !== null && (
                      <div className="flex justify-between">
                        <span className="text-muted-foreground">Стоимость доставки</span>
                        <span className="text-foreground">
                          {deliveryCost === 0 ? 'Бесплатно' : `${deliveryCost.toLocaleString('ru-RU')} ₽`}
                        </span>
                      </div>
                    )}
                  </>
                )}
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Время доставки</span>
                  <span className="text-foreground">
                    {formData.deliveryDate && formData.deliveryTimeSlot
                      ? `${new Date(formData.deliveryDate).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })}, ${formData.deliveryTimeSlot}`
                      : formData.deliveryDate
                      ? new Date(formData.deliveryDate).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' })
                      : 'Не выбрано'}
                  </span>
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

            {/* Payment Method */}
            {formData.paymentMethod && (
              <div className="rounded-lg border border-border bg-card p-4">
                <h3 className="mb-3 font-semibold text-foreground">Способ оплаты</h3>
                <div className="text-sm">
                  <span className="text-foreground">{formData.paymentMethod.name}</span>
                </div>
              </div>
            )}

            {/* Total */}
            <div className="rounded-lg bg-secondary p-4">
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-muted-foreground">Товары</span>
                  <span className="text-sm text-foreground">
                    {totalAmount.toLocaleString('ru-RU')} ₽
                  </span>
                </div>
                {discountInfo && discountInfo.applied && discountInfo.discount > 0 && (
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Скидка</span>
                    <span className="text-sm font-semibold text-primary">
                      -{discountInfo.discount.toLocaleString('ru-RU')} ₽
                    </span>
                  </div>
                )}
                <div className="pt-2 border-t border-border flex items-center justify-between">
                  <span className="text-lg font-semibold text-foreground">К оплате</span>
                  <span className="text-xl font-bold text-primary">
                    {(discountInfo?.final_amount || totalAmount).toLocaleString('ru-RU')} ₽
                  </span>
                </div>
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
              disabled={
                step === 2 &&
                formData.deliveryType === 'courier' &&
                totalAmount < minDeliveryOrderTotal
              }
              className={cn(
                "flex-1 h-11 rounded-lg font-semibold touch-feedback",
                step === 2 &&
                formData.deliveryType === 'courier' &&
                totalAmount < minDeliveryOrderTotal
                  ? "bg-muted text-muted-foreground cursor-not-allowed"
                  : "bg-primary text-primary-foreground"
              )}
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
                getPaymentButtonText()
              )}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
