import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { CategoryTabs } from '@/components/miniapp/CategoryTabs';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { useCartStore } from '@/store/cartStore';
import { useProducts } from '@/hooks/useProducts';
import { ShoppingCart, Loader2 } from 'lucide-react';
import { Product } from '@/types';

export function CatalogPage() {
  const navigate = useNavigate();
  const [activeCategory, setActiveCategory] = useState<string | null>(null);
  const { products, categories, loading, error } = useProducts();
  const totalItems = useCartStore((state) => state.getTotalItems());
  const totalAmount = useCartStore((state) => state.getTotalAmount());

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

      {/* Category Tabs - Sticky with horizontal scroll */}
      <div className="sticky top-14 z-30 bg-background border-b border-border">
        <CategoryTabs
          categories={categories}
          activeCategory={activeCategory}
          onCategoryChange={setActiveCategory}
        />
      </div>

      <div className="px-4 pt-4">
        {activeCategory ? (
          // Grid layout when category is selected
          <div className="grid grid-cols-2 gap-3 auto-rows-fr">
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
          Object.entries(groupedProducts).map(([categoryId, catProducts]) => (
            <div key={categoryId} className="mb-6">
              <div className="flex items-center justify-between py-3">
                <h2 className="text-lg font-bold text-foreground">
                  {getCategoryName(categoryId)}
                </h2>
                <button
                  onClick={() => setActiveCategory(categoryId)}
                  className="text-sm font-medium text-primary touch-feedback"
                >
                  Показать все
                </button>
              </div>
              <div className="grid grid-cols-2 gap-3 auto-rows-fr">
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
        <div className="fixed bottom-20 left-4 right-4 z-40 animate-slide-up">
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
