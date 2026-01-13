import { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { ProductCard } from '@/components/miniapp/ProductCard';
import { useProducts } from '@/hooks/useProducts';
import { Product } from '@/types';
import { Search, X, Loader2 } from 'lucide-react';
import { Input } from '@/components/ui/input';

interface SearchResult extends Product {
  priority: number;
  matchPosition: number;
}

export function SearchPage() {
  const navigate = useNavigate();
  const { products, loading } = useProducts();
  const [query, setQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);

  // Debounce query
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedQuery(query);
    }, 200);
    return () => clearTimeout(timer);
  }, [query]);

  // Auto-focus input on mount
  useEffect(() => {
    // Небольшая задержка для корректной работы на мобильных устройствах
    const timer = setTimeout(() => {
      inputRef.current?.focus();
    }, 100);
    return () => clearTimeout(timer);
  }, []);

  // Normalize search string
  const normalizeString = (str: string): string => {
    return str
      .trim()
      .toLowerCase()
      .replace(/\s+/g, ' ');
  };

  // Search function with ranking
  const searchProducts = (searchQuery: string, allProducts: Product[]): SearchResult[] => {
    if (!searchQuery || searchQuery.length < 1) return [];

    const normalizedQuery = normalizeString(searchQuery);
    const results: SearchResult[] = [];

    allProducts.forEach((product) => {
      const nameLower = normalizeString(product.name);
      const descLower = normalizeString(product.description || '');
      
      let priority = 0;
      let matchPosition = Infinity;

      // Priority 1: название начинается с запроса
      if (nameLower.startsWith(normalizedQuery)) {
        priority = 1;
        matchPosition = 0;
      }
      // Priority 2: запрос встречается в названии
      else if (nameLower.includes(normalizedQuery)) {
        priority = 2;
        matchPosition = nameLower.indexOf(normalizedQuery);
      }
      // Priority 3: запрос встречается в описании
      else if (descLower.includes(normalizedQuery)) {
        priority = 3;
        matchPosition = descLower.indexOf(normalizedQuery);
      }

      if (priority > 0) {
        results.push({
          ...product,
          priority,
          matchPosition,
        });
      }
    });

    // Sort by priority, then by match position, then by name
    results.sort((a, b) => {
      if (a.priority !== b.priority) return a.priority - b.priority;
      if (a.matchPosition !== b.matchPosition) return a.matchPosition - b.matchPosition;
      return a.name.localeCompare(b.name);
    });

    return results.slice(0, 50); // Limit to 50 results
  };

  const searchResults = useMemo(() => {
    if (!debouncedQuery || debouncedQuery.length < 1) return [];
    return searchProducts(debouncedQuery, products);
  }, [debouncedQuery, products]);

  // Suggestions (top 8-10 results, only if query >= 2 chars)
  const suggestions = useMemo(() => {
    if (!debouncedQuery || debouncedQuery.length < 2) return [];
    return searchResults.slice(0, 10);
  }, [debouncedQuery, searchResults]);

  const handleSuggestionClick = (productId: string) => {
    // Navigate to product detail page if it exists
    navigate(`/product/${productId}`);
  };

  const handleClear = () => {
    setQuery('');
    setDebouncedQuery('');
  };

  const handleProductClick = (productId: string) => {
    navigate(`/product/${productId}`);
  };

  return (
    <div className="flex flex-col h-screen bg-background overflow-hidden">
      <MiniAppHeader title="Поиск" showBack={true} showSearch={false} />

      {/* Search Input */}
      <div className="px-4 pt-3 pb-2 border-b border-border">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
          <Input
            ref={inputRef}
            type="text"
            placeholder="Введите название блюда"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="pl-10 pr-10 h-11 text-base"
          />
          {query && (
            <button
              onClick={handleClear}
              className="absolute right-3 top-1/2 -translate-y-1/2 flex h-6 w-6 items-center justify-center rounded-full hover:bg-muted touch-feedback"
              aria-label="Очистить"
            >
              <X className="h-4 w-4 text-muted-foreground" />
            </button>
          )}
        </div>
      </div>

      {/* Content Area */}
      <div className="flex-1 overflow-y-auto px-4 pb-20">
        {loading ? (
          <div className="flex flex-col items-center justify-center py-20">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
            <p className="mt-4 text-muted-foreground">Загрузка...</p>
          </div>
        ) : (
          <>
            {/* Empty State - No Query */}
            {!debouncedQuery && (
              <div className="flex flex-col items-center justify-center py-20">
                <Search className="h-12 w-12 text-muted-foreground mb-4" />
                <p className="text-muted-foreground text-center">
                  Начните вводить название блюда
                </p>
              </div>
            )}

            {/* Suggestions (if query >= 2 chars) */}
            {suggestions.length > 0 && (
              <div className="pt-4 pb-2">
                <h3 className="text-sm font-semibold text-foreground mb-3">Подсказки</h3>
                <div className="space-y-1">
                  {suggestions.map((product) => (
                    <button
                      key={product.id}
                      onClick={() => handleSuggestionClick(product.id)}
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

            {/* Search Results */}
            {debouncedQuery && debouncedQuery.length >= 1 && (
              <div className={suggestions.length > 0 ? 'pt-6' : 'pt-4'}>
                {searchResults.length > 0 ? (
                  <>
                    <h3 className="text-sm font-semibold text-foreground mb-3">
                      Результаты поиска {searchResults.length > 0 && `(${searchResults.length})`}
                    </h3>
                    <div className="grid grid-cols-2 gap-2 sm:gap-3 auto-rows-fr">
                      {searchResults.map((product) => (
                        <ProductCard
                          key={product.id}
                          product={product}
                          variant="grid"
                          onClick={() => handleProductClick(product.id)}
                        />
                      ))}
                    </div>
                  </>
                ) : (
                  <div className="flex flex-col items-center justify-center py-20">
                    <Search className="h-12 w-12 text-muted-foreground mb-4" />
                    <p className="text-muted-foreground text-center">
                      Ничего не найдено
                    </p>
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

      <BottomNavigation />
    </div>
  );
}

