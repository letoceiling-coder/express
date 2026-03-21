import { useMemo, useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Search, ShoppingCart, Loader2 } from 'lucide-react';
import { HeroSlider } from '@/components/web/HeroSlider';
import { CategorySection } from '@/components/web/CategorySection';
import { ProductGrid } from '@/components/web/ProductGrid';
import { Benefits } from '@/components/web/Benefits';
import { DeliveryModeToggle } from '@/components/miniapp/DeliveryModeToggle';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { useProducts } from '@/hooks/useProducts';
import { useCartStore } from '@/store/cartStore';
import { useOrderModeStore } from '@/store/orderModeStore';

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

  const sortedProducts = useMemo(() => {
    return [...filteredProducts].sort((a, b) => {
      const orderA = a.sortOrder ?? 0;
      const orderB = b.sortOrder ?? 0;
      if (orderA !== orderB) return orderA - orderB;
      return a.name.localeCompare(b.name);
    });
  }, [filteredProducts]);

  return (
    <>
      {/* Desktop: HeroSlider */}
      <div className="hidden lg:block">
        <HeroSlider />
      </div>

      {loading && (
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка каталога...</p>
        </div>
      )}

      {error && (
        <div className="container mx-auto px-4 py-16 text-center">
          <p className="text-destructive">{error}</p>
          <button
            onClick={() => window.location.reload()}
            className="mt-4 rounded-lg bg-primary px-6 py-2 font-medium text-primary-foreground hover:opacity-90"
          >
            Попробовать снова
          </button>
        </div>
      )}

      {!loading && !error && (
        <>
          {/* Search — premium style */}
          <div className="py-3 flex justify-center lg:justify-start">
            <div className="relative w-full lg:max-w-md">
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

          {/* Mobile: DeliveryModeToggle (MiniApp 1:1) */}
          <div className="lg:hidden">
            <DeliveryModeToggle value={orderMode} onChange={setOrderMode} />
          </div>

          <CategorySection
            categories={categories}
            activeCategoryId={activeCategoryId}
            onCategoryChange={setActiveCategoryId}
          />

          <div id="products" className="px-4 lg:px-0 py-8 -mx-4 lg:mx-0">
            {/* Mobile: ProductCard grid (MiniApp 1:1) */}
            <div className="lg:hidden px-2 sm:px-4">
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
              {sortedProducts.length === 0 && (
                <div className="py-12 text-center text-muted-foreground text-sm">
                  Нет товаров в этой категории
                </div>
              )}
            </div>

            {/* Desktop: ProductGrid */}
            <div className="hidden lg:block">
              <ProductGrid
                products={sortedProducts}
                title={activeCategoryId ? undefined : 'Популярные товары'}
              />
            </div>
          </div>

          {/* Desktop: Benefits */}
          <div className="hidden lg:block">
            <Benefits />
          </div>

          {/* Mobile: Floating Cart (MiniApp 1:1) */}
          {totalItems > 0 && (
            <div
              className="lg:hidden fixed left-4 right-4 z-40 animate-slide-up"
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
        </>
      )}
    </>
  );
}
