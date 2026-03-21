import { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { useProducts } from '@/hooks/useProducts';
import { Product } from '@/types';
import { Search, X, Loader2 } from 'lucide-react';
import { Input } from '@/components/ui/input';

interface SearchResult extends Product {
  priority: number;
  matchPosition: number;
}

export function WebSearchPage() {
  const navigate = useNavigate();
  const { products, loading } = useProducts();
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedQuery(query), 200);
    return () => clearTimeout(timer);
  }, [query]);

  useEffect(() => {
    const timer = setTimeout(() => inputRef.current?.focus(), 100);
    return () => clearTimeout(timer);
  }, []);

  const normalizeString = (str: string): string =>
    str.trim().toLowerCase().replace(/\s+/g, ' ');

  const searchProducts = (searchQuery: string, allProducts: Product[]): SearchResult[] => {
    if (!searchQuery || searchQuery.length < 1) return [];
    const normalizedQuery = normalizeString(searchQuery);
    const results: SearchResult[] = [];

    allProducts.forEach((product) => {
      const nameLower = normalizeString(product.name);
      const descLower = normalizeString(product.description || '');
      let priority = 0;
      let matchPosition = Infinity;

      if (nameLower.startsWith(normalizedQuery)) {
        priority = 1;
        matchPosition = 0;
      } else if (nameLower.includes(normalizedQuery)) {
        priority = 2;
        matchPosition = nameLower.indexOf(normalizedQuery);
      } else if (descLower.includes(normalizedQuery)) {
        priority = 3;
        matchPosition = descLower.indexOf(normalizedQuery);
      }

      if (priority > 0) {
        results.push({ ...product, priority, matchPosition });
      }
    });

    results.sort((a, b) => {
      if (a.priority !== b.priority) return a.priority - b.priority;
      if (a.matchPosition !== b.matchPosition) return a.matchPosition - b.matchPosition;
      return a.name.localeCompare(b.name);
    });
    return results.slice(0, 50);
  };

  const searchResults = useMemo(
    () => (debouncedQuery ? searchProducts(debouncedQuery, products) : []),
    [debouncedQuery, products]
  );

  const suggestions = useMemo(
    () => (debouncedQuery.length >= 2 ? searchResults.slice(0, 10) : []),
    [debouncedQuery, searchResults]
  );

  return (
    <div className="flex flex-col min-h-[60vh] lg:min-h-0">
      {/* Search Input - EXACT as MiniApp SearchPage */}
      <div className="px-4 pt-3 pb-2 border-b border-border">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
          <Input
            ref={inputRef}
            type="text"
            placeholder="Введите название блюда"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="pl-10 pr-10 h-11 text-base rounded-xl border border-border bg-muted/50 focus:bg-background"
          />
          {query && (
            <button
              onClick={() => setQuery('')}
              className="absolute right-3 top-1/2 -translate-y-1/2 flex h-6 w-6 items-center justify-center rounded-full hover:bg-muted touch-feedback"
              aria-label="Очистить"
            >
              <X className="h-4 w-4 text-muted-foreground" />
            </button>
          )}
        </div>
      </div>

      <div className="flex-1 overflow-y-auto px-4 pb-24 lg:pb-8">
        {loading ? (
          <div className="flex flex-col items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
            <p className="mt-4 text-muted-foreground">Загрузка...</p>
          </div>
        ) : (
          <>
            {!debouncedQuery && (
              <div className="flex flex-col items-center justify-center py-20">
                <Search className="h-12 w-12 text-muted-foreground mb-4" />
                <p className="text-muted-foreground text-center">Начните вводить название блюда</p>
              </div>
            )}

            {suggestions.length > 0 && (
              <div className="pt-4 pb-2">
                <h3 className="text-sm font-semibold text-foreground mb-3">Подсказки</h3>
                <div className="space-y-1">
                  {suggestions.map((product) => (
                    <button
                      key={product.id}
                      onClick={() => navigate(`/product/${product.id}`)}
                      className="w-full flex items-center justify-between gap-3 p-3 rounded-lg border border-border bg-card hover:bg-muted transition-colors touch-feedback text-left"
                    >
                      <span className="flex-1 text-sm font-medium text-foreground truncate">
                        {product.name}
                      </span>
                      <span className="text-sm font-bold text-primary flex-shrink-0">
                        {product.price.toLocaleString('ru-RU')} ₽
                      </span>
                    </button>
                  ))}
                </div>
              </div>
            )}

            {debouncedQuery && debouncedQuery.length >= 1 && (
              <div className={suggestions.length > 0 ? 'pt-6' : 'pt-4'}>
                {searchResults.length > 0 ? (
                  <>
                    <h3 className="text-sm font-semibold text-foreground mb-3">
                      Результаты поиска ({searchResults.length})
                    </h3>
                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                      {searchResults.map((product) => (
                        <ProductCard
                          key={product.id}
                          product={product}
                          variant="grid"
                          onClick={() => navigate(`/product/${product.id}`)}
                        />
                      ))}
                    </div>
                  </>
                ) : (
                  <div className="flex flex-col items-center justify-center py-20">
                    <Search className="h-12 w-12 text-muted-foreground mb-4" />
                    <p className="text-muted-foreground text-center">Ничего не найдено</p>
                    <p className="text-xs text-muted-foreground text-center mt-2">
                      Попробуйте изменить запрос
                    </p>
                  </div>
                )}
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
