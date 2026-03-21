import { useMemo, useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { WebLayout } from '@/components/web/WebLayout';
import { HeroSlider } from '@/components/web/HeroSlider';
import { CategorySection } from '@/components/web/CategorySection';
import { ProductGrid } from '@/components/web/ProductGrid';
import { Benefits } from '@/components/web/Benefits';
import { useProducts } from '@/hooks/useProducts';
import { Loader2 } from 'lucide-react';

export function HomePage() {
  const { products, categories, loading, error } = useProducts();
  const [activeCategoryId, setActiveCategoryId] = useState<string | null>(null);
  const location = useLocation();

  useEffect(() => {
    const hash = location.hash?.slice(1);
    if (hash && (hash === 'categories' || hash === 'benefits')) {
      const el = document.getElementById(hash);
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }
  }, [location.hash, loading]);

  const filteredProducts = useMemo(() => {
    if (!activeCategoryId) return products;
    return products.filter((p) => p.categoryId === activeCategoryId);
  }, [products, activeCategoryId]);

  const sortedProducts = useMemo(() => {
    return [...filteredProducts].sort((a, b) => {
      const orderA = a.sortOrder ?? 0;
      const orderB = b.sortOrder ?? 0;
      if (orderA !== orderB) return orderA - orderB;
      return a.name.localeCompare(b.name);
    });
  }, [filteredProducts]);

  return (
    <WebLayout>
      <HeroSlider />

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
          <CategorySection
            categories={categories}
            activeCategoryId={activeCategoryId}
            onCategoryChange={setActiveCategoryId}
          />

          <div id="products" className="container mx-auto px-4 py-12 lg:px-8">
            <ProductGrid
              products={sortedProducts}
              title={activeCategoryId ? undefined : 'Популярные товары'}
            />
          </div>

          <Benefits />
        </>
      )}
    </WebLayout>
  );
}
