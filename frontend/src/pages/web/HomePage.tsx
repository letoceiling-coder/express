import { useMemo, useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { ShoppingCart, Loader2 } from 'lucide-react';
import { HeroSlider } from '@/components/web/HeroSlider';
import { CategorySection } from '@/components/web/CategorySection';
import { SearchInput } from '@/components/web/SearchInput';
import { ProductGrid } from '@/components/web/ProductGrid';
import { Benefits } from '@/components/web/Benefits';
import { CategoryTabs } from '@/components/miniapp/CategoryTabs';
import { DeliveryModeToggle } from '@/components/miniapp/DeliveryModeToggle';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { DeliveryProgressIndicator } from '@/components/miniapp/DeliveryProgressIndicator';
import { useProducts } from '@/hooks/useProducts';
import { useCartStore } from '@/store/cartStore';
import { useOrderModeStore } from '@/store/orderModeStore';
import { deliverySettingsAPI } from '@/api';
import { Product } from '@/types';

export function HomePage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { products, categories, loading, error } = useProducts();
  const [activeCategoryId, setActiveCategoryId] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const orderMode = useOrderModeStore((s) => s.orderMode);
  const setOrderMode = useOrderModeStore((s) => s.setOrderMode);
  const totalItems = useCartStore((s) => s.getTotalItems());
  const totalAmount = useCartStore((s) => s.getTotalAmount());
  const [minDeliveryTotal, setMinDeliveryTotal] = useState(3000);
  const [freeDeliveryThreshold, setFreeDeliveryThreshold] = useState<number | undefined>(undefined);

  useEffect(() => {
    deliverySettingsAPI.getSettings().then((settings) => {
      if (settings?.min_delivery_order_total_rub != null) {
        setMinDeliveryTotal(Number(settings.min_delivery_order_total_rub));
      }
      const th =
        settings?.free_delivery_threshold ??
        settings?.free_delivery_threshold_rub ??
        settings?.freeDeliveryThreshold;
      if (th != null && th !== '') {
        const v = Number(th);
        const min = Number(settings?.min_delivery_order_total_rub ?? 3000);
        if (!isNaN(v) && v > 0 && v > min) setFreeDeliveryThreshold(v);
      }
    }).catch(() => {});
  }, []);

  useEffect(() => {
    const hash = location.hash?.slice(1);
    if (hash && (hash === 'categories' || hash === 'benefits')) {
      const el = document.getElementById(hash);
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }
  }, [location.hash, loading]);

  const filteredProducts = useMemo(() => {
    let list = products;
    if (activeCategoryId) {
      list = list.filter((p) => p.categoryId === activeCategoryId);
    }
    if (search.trim()) {
      const q = search.trim().toLowerCase();
      list = list.filter((p) => p.name.toLowerCase().includes(q));
    }
    return list;
  }, [products, activeCategoryId, search]);

  const sortedProducts = useMemo(
    () =>
      [...filteredProducts].sort((a, b) => {
        const oA = a.sortOrder ?? 0;
        const oB = b.sortOrder ?? 0;
        if (oA !== oB) return oA - oB;
        return a.name.localeCompare(b.name);
      }),
    [filteredProducts]
  );

  const groupedProducts = useMemo(() => {
    if (activeCategoryId) return null;
    const groups: Record<string, Product[]> = {};
    products.forEach((p) => {
      if (!groups[p.categoryId]) groups[p.categoryId] = [];
      groups[p.categoryId].push(p);
    });
    Object.keys(groups).forEach((id) => {
      groups[id].sort((a, b) => {
        const oA = a.sortOrder ?? 0;
        const oB = b.sortOrder ?? 0;
        if (oA !== oB) return oA - oB;
        return a.name.localeCompare(b.name);
      });
    });
    return groups;
  }, [activeCategoryId, products]);

  const getCategoryName = (id: string) => categories.find((c) => c.id === id)?.name ?? '';

  const handleCategoryChange = (id: string | null) => {
    setActiveCategoryId(id);
    window.scrollTo({ top: 0, behavior: 'instant' });
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="mt-4 text-muted-foreground">Загрузка каталога...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto px-4 py-16 text-center">
        <p className="text-destructive">{error}</p>
        <button
          onClick={() => window.location.reload()}
          className="mt-4 rounded-lg bg-primary px-6 py-2 font-medium text-primary-foreground hover:opacity-90"
        >
          Попробовать снова
        </button>
      </div>
    );
  }

  return (
    <>
      {/* ========== DESKTOP LAYOUT (lg+) - NOT touching ========== */}
      <div className="hidden lg:block">
        <HeroSlider />
        <div className="py-3">
          <div className="relative w-full max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground w-4 h-4" />
            <input
              type="text"
              placeholder="Поиск товаров..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-3 rounded-xl border border-border bg-muted/50 focus:bg-background focus:ring-2 focus:ring-primary outline-none transition placeholder:text-muted-foreground"
            />
          </div>
        </div>
        <CategorySection
          categories={categories}
          activeCategoryId={activeCategoryId}
          onCategoryChange={setActiveCategoryId}
        />
        <div id="products" className="py-8">
          <ProductGrid
            products={sortedProducts}
            title={activeCategoryId ? undefined : 'Популярные товары'}
          />
        </div>
        <Benefits />
      </div>

      {/* ========== MOBILE LAYOUT (< lg) - EXACT 1:1 MiniApp CatalogPage ========== */}
      <div className="lg:hidden min-h-screen bg-background pb-28">
        {/* Sticky: Delivery Toggle + Category Tabs - EXACT as MiniApp (no inline search, use header icon) */}
        <div className="sticky top-14 z-30 bg-background border-b border-border">
          <DeliveryModeToggle value={orderMode} onChange={setOrderMode} />
          <CategoryTabs
            categories={categories}
            activeCategory={activeCategoryId}
            onCategoryChange={handleCategoryChange}
          />
        </div>

        <div className="px-2 sm:px-4 pt-4">
          {activeCategoryId ? (
            <div className="grid grid-cols-2 gap-2 sm:gap-3 auto-rows-fr">
              {sortedProducts.map((product) => (
                <ProductCard
                  key={product.id}
                  product={product}
                  variant="grid"
                  onClick={() => navigate(`/product/${product.id}`)}
                />
              ))}
            </div>
          ) : groupedProducts ? (
            Object.entries(groupedProducts)
              .sort(([aId], [bId]) => {
                const a = categories.find((c) => c.id === aId);
                const b = categories.find((c) => c.id === bId);
                const oA = a?.sortOrder ?? 0;
                const oB = b?.sortOrder ?? 0;
                if (oA !== oB) return oA - oB;
                return (a?.name ?? '').localeCompare(b?.name ?? '');
              })
              .map(([catId, catProducts]) => (
                <div key={catId} className="mb-6">
                  <div className="flex items-center justify-between py-3 px-2">
                    <h2 className="text-base sm:text-lg font-bold text-foreground">
                      {getCategoryName(catId)}
                    </h2>
                    <button
                      onClick={() => handleCategoryChange(catId)}
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
          ) : null}
        </div>

        {/* Delivery Progress - EXACT as MiniApp */}
        {orderMode === 'delivery' && (
          <DeliveryProgressIndicator
            cartTotal={totalAmount}
            minDeliveryTotal={minDeliveryTotal}
            freeDeliveryThreshold={freeDeliveryThreshold}
          />
        )}

        {/* Floating Cart - EXACT as MiniApp */}
        {totalItems > 0 && (
          <div
            className="fixed left-4 right-4 z-40 animate-slide-up"
            style={{
              bottom:
                orderMode === 'delivery'
                  ? 'calc(52px + env(safe-area-inset-bottom, 0px) + 8px + 56px + 8px)'
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
      </div>
    </>
  );
}
