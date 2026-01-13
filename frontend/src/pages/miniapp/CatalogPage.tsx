import { useState, useMemo, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { CategoryTabs } from '@/components/miniapp/CategoryTabs';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { DeliveryModeToggle } from '@/components/miniapp/DeliveryModeToggle';
import { DeliveryProgressBar } from '@/components/miniapp/DeliveryProgressBar';
import { useCartStore } from '@/store/cartStore';
import { useProducts } from '@/hooks/useProducts';
import { deliverySettingsAPI } from '@/api';
import { ShoppingCart, Loader2 } from 'lucide-react';
import { Product } from '@/types';

export function CatalogPage() {
  const navigate = useNavigate();
  const [activeCategory, setActiveCategory] = useState<string | null>(null);
  const { products, categories, loading, error } = useProducts();
  const totalItems = useCartStore((state) => state.getTotalItems());
  const totalAmount = useCartStore((state) => state.getTotalAmount());

  // Состояние выбора типа доставки
  const [orderMode, setOrderMode] = useState<'pickup' | 'delivery'>(() => {
    const saved = localStorage.getItem('orderMode');
    return (saved === 'delivery' || saved === 'pickup') ? saved : 'pickup';
  });

  // Сохранение orderMode в localStorage
  useEffect(() => {
    localStorage.setItem('orderMode', orderMode);
  }, [orderMode]);

  // Загрузка настроек доставки
  const [minDeliveryTotal, setMinDeliveryTotal] = useState<number>(3000);
  useEffect(() => {
    const loadSettings = async () => {
      try {
        const settings = await deliverySettingsAPI.getSettings();
        if (settings?.min_delivery_order_total_rub !== undefined) {
          setMinDeliveryTotal(settings.min_delivery_order_total_rub);
        }
      } catch (error) {
        console.error('Error loading delivery settings:', error);
        // Используем значение по умолчанию 3000
      }
    };
    loadSettings();
  }, []);

  const filteredProducts = useMemo(() => {
    if (!activeCategory) return products;
    return products.filter((product) => product.categoryId === activeCategory);
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

      {/* Delivery Mode Toggle */}
      <DeliveryModeToggle value={orderMode} onChange={setOrderMode} />

      {/* Category Tabs - Sticky with horizontal scroll */}
      <div className="sticky top-[104px] z-30 bg-background border-b border-border">
        <CategoryTabs
          categories={categories}
          activeCategory={activeCategory}
          onCategoryChange={setActiveCategory}
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
                onClick={() => navigate(`/product/${product.id}`)}
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
                  onClick={() => setActiveCategory(categoryId)}
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
                    onClick={() => navigate(`/product/${product.id}`)}
                  />
                ))}
              </div>
            </div>
          ))
        )}
      </div>

      {/* Floating Cart Button */}
      {totalItems > 0 && (
        <div className={`fixed ${orderMode === 'delivery' ? 'bottom-28' : 'bottom-20'} left-4 right-4 z-40 animate-slide-up`}>
          <button
            onClick={() => navigate('/cart')}
            className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary h-12 text-base font-semibold text-primary-foreground shadow-lg touch-feedback hover:opacity-90 transition-opacity"
          >
            <ShoppingCart className="h-5 w-5" />
            Корзина ({totalItems}) · {totalAmount.toLocaleString('ru-RU')} ₽
          </button>
        </div>
      )}

      {/* Delivery Progress Bar - показывается только при выборе доставки */}
      {orderMode === 'delivery' && (
        <DeliveryProgressBar
          cartTotal={totalAmount}
          minDeliveryTotal={minDeliveryTotal}
          onNavigateToCart={() => navigate('/cart')}
        />
      )}

      <BottomNavigation />
    </div>
  );
}
