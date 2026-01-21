import { useState, useMemo, useEffect, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { CategoryTabs } from '@/components/miniapp/CategoryTabs';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { DeliveryModeToggle } from '@/components/miniapp/DeliveryModeToggle';
import { DeliveryProgressIndicator } from '@/components/miniapp/DeliveryProgressIndicator';
import { useCartStore } from '@/store/cartStore';
import { useCatalogStore } from '@/store/catalogStore';
import { useProducts } from '@/hooks/useProducts';
import { deliverySettingsAPI } from '@/api';
import { ShoppingCart, Loader2 } from 'lucide-react';
import { Product } from '@/types';

export function CatalogPage() {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const { products, categories, loading, error } = useProducts();
  const totalItems = useCartStore((state) => state.getTotalItems());
  const totalAmount = useCartStore((state) => state.getTotalAmount());
  
  // Используем store для сохранения состояния каталога
  const { 
    activeCategoryId, 
    scrollY, 
    setActiveCategoryId, 
    setScrollY 
  } = useCatalogStore();
  
  // Синхронизируем activeCategory с query параметрами и store
  const categoryFromQuery = searchParams.get('cat');
  const activeCategory = categoryFromQuery || activeCategoryId;
  
  // Флаг для отслеживания первого рендера (чтобы восстановить скролл только один раз)
  const hasRestoredScroll = useRef(false);
  const previousCategoryRef = useRef<string | null>(null);

  // Состояние выбора типа доставки
  const [orderMode, setOrderMode] = useState<'pickup' | 'delivery'>(() => {
    const saved = localStorage.getItem('orderMode');
    return (saved === 'delivery' || saved === 'pickup') ? saved : 'pickup';
  });

  // Сохранение orderMode в localStorage
  useEffect(() => {
    localStorage.setItem('orderMode', orderMode);
  }, [orderMode]);

  // Синхронизация activeCategory с query параметрами и store
  useEffect(() => {
    const categoryFromQuery = searchParams.get('cat');
    if (categoryFromQuery !== activeCategoryId) {
      setActiveCategoryId(categoryFromQuery);
    }
    
    // Если категория изменилась (и это не первый рендер), сбрасываем скролл и флаг восстановления
    if (previousCategoryRef.current !== null && previousCategoryRef.current !== activeCategory) {
      setScrollY(0);
      hasRestoredScroll.current = false;
    }
    
    // Обновляем предыдущую категорию
    previousCategoryRef.current = activeCategory;
  }, [searchParams, activeCategoryId, setActiveCategoryId, activeCategory, setScrollY]);

  // Обработчик изменения категории
  const handleCategoryChange = (categoryId: string | null) => {
    setActiveCategoryId(categoryId);
    if (categoryId) {
      setSearchParams({ cat: categoryId });
    } else {
      setSearchParams({});
    }
    // Сбрасываем скролл при смене категории
    setScrollY(0);
    window.scrollTo({ top: 0, behavior: 'instant' as ScrollBehavior });
    hasRestoredScroll.current = false; // Сбрасываем флаг для новой категории
  };

  // Сохранение позиции скролла при прокрутке (скролл происходит на window)
  useEffect(() => {
    const handleScroll = () => {
      setScrollY(window.scrollY);
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    return () => window.removeEventListener('scroll', handleScroll);
  }, [setScrollY]);

  // Восстановление позиции скролла при монтировании
  useEffect(() => {
    // Восстанавливаем скролл только если:
    // 1. Данные загружены
    // 2. Скролл еще не был восстановлен
    // 3. Есть сохраненная позиция скролла
    // 4. Категория не изменилась (чтобы не восстанавливать скролл при смене категории)
    if (!loading && !hasRestoredScroll.current && scrollY > 0 && previousCategoryRef.current === activeCategory) {
      // Небольшая задержка для гарантии рендера контента
      const timer = setTimeout(() => {
        window.scrollTo({
          top: scrollY,
          behavior: 'instant' as ScrollBehavior,
        });
        hasRestoredScroll.current = true;
      }, 150);
      
      return () => clearTimeout(timer);
    } else if (!loading && scrollY === 0) {
      // Если скролл был сброшен, помечаем как восстановленный
      hasRestoredScroll.current = true;
    }
  }, [loading, scrollY, activeCategory]);

  // Загрузка настроек доставки
  const [minDeliveryTotal, setMinDeliveryTotal] = useState<number>(3000);
  const [freeDeliveryThreshold, setFreeDeliveryThreshold] = useState<number | undefined>(undefined);
  useEffect(() => {
    const loadSettings = async () => {
      try {
        const settings = await deliverySettingsAPI.getSettings();
        console.log('[CatalogPage] Delivery settings loaded:', settings);
        
        if (!settings) {
          console.warn('[CatalogPage] No delivery settings received');
          return;
        }

        // Загружаем минимальный заказ
        if (settings.min_delivery_order_total_rub !== undefined && settings.min_delivery_order_total_rub !== null) {
          const minTotal = Number(settings.min_delivery_order_total_rub);
          console.log('[CatalogPage] Setting minDeliveryTotal:', minTotal);
          setMinDeliveryTotal(minTotal);
        }

        // Загружаем порог бесплатной доставки
        // Проверяем разные возможные названия поля
        const thresholdValue = settings.free_delivery_threshold 
          ?? settings.free_delivery_threshold_rub
          ?? settings.freeDeliveryThreshold;
        
        console.log('[CatalogPage] free_delivery_threshold from API:', {
          thresholdValue,
          'free_delivery_threshold': settings.free_delivery_threshold,
          'free_delivery_threshold_rub': settings.free_delivery_threshold_rub,
          'freeDeliveryThreshold': settings.freeDeliveryThreshold,
          allKeys: Object.keys(settings),
        });
        
        const currentMinTotal = Number(settings.min_delivery_order_total_rub || minDeliveryTotal || 3000);
        
        if (thresholdValue !== undefined && thresholdValue !== null && thresholdValue !== '') {
          const threshold = Number(thresholdValue);
          
          console.log('[CatalogPage] Processing threshold:', {
            threshold,
            currentMinTotal,
            thresholdValid: threshold > 0,
            thresholdGreaterThanMin: threshold > currentMinTotal,
            thresholdType: typeof thresholdValue,
          });

          // freeDeliveryThreshold должен быть положительным и строго больше минимального заказа
          if (!isNaN(threshold) && threshold > 0 && threshold > currentMinTotal) {
            console.log('[CatalogPage] ✅ Setting freeDeliveryThreshold:', threshold);
            setFreeDeliveryThreshold(threshold);
          } else {
            console.warn('[CatalogPage] ❌ Threshold invalid:', { 
              threshold, 
              currentMinTotal,
              isValid: !isNaN(threshold),
              isPositive: threshold > 0,
              isGreaterThanMin: threshold > currentMinTotal
            });
            setFreeDeliveryThreshold(undefined);
          }
        } else {
          console.warn('[CatalogPage] ❌ free_delivery_threshold not found or empty in settings');
          setFreeDeliveryThreshold(undefined);
        }
      } catch (error) {
        console.error('[CatalogPage] Error loading delivery settings:', error);
        // В miniApp оставим дефолтные значения (3000 / без бесплатной доставки),
        // чтобы каталог продолжал работать даже при ошибке API.
      }
    };

    loadSettings();
  }, []);

  const filteredProducts = useMemo(() => {
    let filtered = activeCategory 
      ? products.filter((product) => product.categoryId === activeCategory)
      : products;
    
    // Сортируем по sortOrder, затем по названию
    filtered = [...filtered].sort((a, b) => {
      const orderA = a.sortOrder || 0;
      const orderB = b.sortOrder || 0;
      if (orderA !== orderB) return orderA - orderB;
      return a.name.localeCompare(b.name);
    });
    
    return filtered;
  }, [activeCategory, products]);

  // Group products by category when showing all
  const groupedProducts = useMemo(() => {
    if (activeCategory) return null;
    const groups: { [key: string]: Product[] } = {};
    products.forEach((product) => {
      if (!groups[product.categoryId]) {
        groups[product.categoryId] = [];
      }
      groups[product.categoryId].push(product);
    });
    
    // Сортируем товары в каждой категории по sortOrder
    Object.keys(groups).forEach((categoryId) => {
      groups[categoryId].sort((a, b) => {
        const orderA = a.sortOrder || 0;
        const orderB = b.sortOrder || 0;
        if (orderA !== orderB) return orderA - orderB;
        return a.name.localeCompare(b.name);
      });
    });
    
    return groups;
  }, [activeCategory, products]);

  const getCategoryName = (categoryId: string) => {
    return categories.find((c) => c.id === categoryId)?.name || '';
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Свой Хлеб" />
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка...</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="Свой Хлеб" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <p className="text-destructive">{error}</p>
          <button
            onClick={() => window.location.reload()}
            className="mt-4 h-11 rounded-lg bg-primary px-6 font-semibold text-primary-foreground touch-feedback"
          >
            Попробовать снова
          </button>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background pb-28">
      <MiniAppHeader title="Свой Хлеб" />

      {/* Sticky Menu: Delivery Mode Toggle + Category Tabs */}
      <div className="sticky top-14 z-30 bg-background border-b border-border">
        {/* Delivery Mode Toggle */}
        <DeliveryModeToggle value={orderMode} onChange={setOrderMode} />

        {/* Category Tabs */}
        <CategoryTabs
          categories={categories}
          activeCategory={activeCategory}
          onCategoryChange={handleCategoryChange}
        />
      </div>

      <div className="px-2 sm:px-4 pt-4">
        {activeCategory ? (
          // Grid layout when category is selected
          <div className="grid grid-cols-2 gap-2 sm:gap-3 auto-rows-fr">
            {filteredProducts.map((product) => (
              <ProductCard
                key={product.id}
                product={product}
                variant="grid"
                onClick={() => {
                  // Сохраняем текущую позицию скролла перед переходом
                  setScrollY(window.scrollY);
                  navigate(`/product/${product.id}`);
                }}
              />
            ))}
          </div>
        ) : (
          // Show grouped list when no category selected
          groupedProducts &&
          Object.entries(groupedProducts)
            .sort(([categoryIdA], [categoryIdB]) => {
              const categoryA = categories.find(c => c.id === categoryIdA);
              const categoryB = categories.find(c => c.id === categoryIdB);
              const orderA = categoryA?.sortOrder || 0;
              const orderB = categoryB?.sortOrder || 0;
              if (orderA !== orderB) return orderA - orderB;
              return (categoryA?.name || '').localeCompare(categoryB?.name || '');
            })
            .map(([categoryId, catProducts]) => (
            <div key={categoryId} className="mb-6">
              <div className="flex items-center justify-between py-3 px-2">
                <h2 className="text-base sm:text-lg font-bold text-foreground">
                  {getCategoryName(categoryId)}
                </h2>
                <button
                  onClick={() => handleCategoryChange(categoryId)}
                  className="text-xs sm:text-sm font-medium text-primary touch-feedback"
                >
                  Показать все
                </button>
              </div>
              <div className="grid grid-cols-2 gap-2 sm:gap-3 auto-rows-fr">
                {catProducts.slice(0, 4).map((product) => (
                  <ProductCard
                    key={product.id}
                    product={product}
                    variant="grid"
                    onClick={() => {
                  // Сохраняем текущую позицию скролла перед переходом
                  setScrollY(window.scrollY);
                  navigate(`/product/${product.id}`);
                }}
                  />
                ))}
              </div>
            </div>
          ))
        )}
      </div>

      {/* Delivery Progress Bar - Fixed внизу между Корзиной и Bottom Navigation */}
      {orderMode === 'delivery' && (
        <DeliveryProgressIndicator
          cartTotal={totalAmount}
          minDeliveryTotal={minDeliveryTotal}
          freeDeliveryThreshold={freeDeliveryThreshold}
        />
      )}

      {/* Floating Cart Button */}
      {totalItems > 0 && (
        <div
          className="fixed left-4 right-4 z-40 animate-slide-up"
          style={{
            bottom: orderMode === 'delivery'
              ? 'calc(52px + env(safe-area-inset-bottom, 0px) + 8px + 52px + 8px)'
              : '72px',
          }}
        >
          <button
            onClick={() => navigate('/cart')}
            className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary h-12 text-base font-semibold text-primary-foreground shadow-lg touch-feedback hover:opacity-90 transition-opacity"
          >
            <ShoppingCart className="h-5 w-5" />
            Корзина ({totalItems}) · {totalAmount.toLocaleString('ru-RU')} ₽
          </button>
        </div>
      )}

      <BottomNavigation />
    </div>
  );
}
