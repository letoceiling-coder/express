import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Plus, Minus, ChevronLeft, Loader2 } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { useCatalogStore } from '@/store/catalogStore';
import { productsAPI, categoriesAPI } from '@/api';
import { toast } from 'sonner';
import { hapticFeedback } from '@/lib/telegram';
import { Product, Category } from '@/types';
import { OptimizedImage } from '@/components/OptimizedImage';

export function ProductDetailPage() {
  const { productId } = useParams();
  const navigate = useNavigate();
  const { items, addItem, updateQuantity } = useCartStore();
  const { setScrollY } = useCatalogStore();
  const [localQuantity, setLocalQuantity] = useState(1);
  const [product, setProduct] = useState<Product | null>(null);
  const [category, setCategory] = useState<Category | null>(null);
  const [loading, setLoading] = useState(true);
  
  const cartItem = items.find((item) => item.product.id === productId);
  const quantityInCart = cartItem?.quantity || 0;

  // Сохраняем позицию скролла при переходе на страницу товара
  useEffect(() => {
    setScrollY(window.scrollY);
  }, [setScrollY]);

  // Обработчик возврата назад
  const handleBack = () => {
    // Используем history.back() для возврата на предыдущую страницу
    // Это сохранит контекст каталога (категорию, скролл)
    if (window.history.length > 1) {
      navigate(-1);
    } else {
      // Если нет истории, переходим на главную с сохранением категории
      navigate('/');
    }
  };

  useEffect(() => {
    const fetchProduct = async () => {
      if (!productId) return;
      setLoading(true);
      
      const productData = await productsAPI.getById(productId);
      setProduct(productData);
      
      if (productData?.categoryId) {
        const categories = await categoriesAPI.getAll();
        const cat = categories.find(c => c.id === productData.categoryId);
        setCategory(cat || null);
      }
      
      setLoading(false);
    };
    fetchProduct();
  }, [productId]);

  if (loading) {
    return (
      <div className="min-h-screen bg-background">
        <header className="sticky top-0 z-50 flex h-14 items-center px-4 bg-background border-b border-border">
          <button
            onClick={handleBack}
            className="flex h-11 w-11 items-center justify-center rounded-lg touch-feedback -ml-2"
          >
            <ChevronLeft className="h-6 w-6" />
          </button>
          <h1 className="ml-2 text-lg font-semibold">Товар</h1>
        </header>
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка...</p>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen bg-background">
        <header className="sticky top-0 z-50 flex h-14 items-center px-4 bg-background border-b border-border">
          <button
            onClick={() => navigate(-1)}
            className="flex h-11 w-11 items-center justify-center rounded-lg touch-feedback -ml-2"
          >
            <ChevronLeft className="h-6 w-6" />
          </button>
          <h1 className="ml-2 text-lg font-semibold">Товар</h1>
        </header>
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <h2 className="text-xl font-bold text-foreground">Товар не найден</h2>
          <button
            onClick={() => navigate('/')}
            className="mt-6 h-11 rounded-lg bg-primary px-8 font-semibold text-primary-foreground touch-feedback"
          >
            В каталог
          </button>
        </div>
      </div>
    );
  }

  const handleAddToCart = () => {
    hapticFeedback('light');
    addItem(product, localQuantity);
    toast.success('Добавлено в корзину', {
      description: `${product.name} × ${localQuantity}`,
      duration: 2000,
    });
    setLocalQuantity(1);
  };

  const handleIncrement = () => {
    hapticFeedback('selection');
    if (quantityInCart > 0) {
      addItem(product);
    } else {
      setLocalQuantity(prev => prev + 1);
    }
  };

  const handleDecrement = () => {
    hapticFeedback('selection');
    if (quantityInCart > 0) {
      updateQuantity(product.id, quantityInCart - 1);
    } else if (localQuantity > 1) {
      setLocalQuantity(prev => prev - 1);
    }
  };

  return (
    <div className="min-h-screen bg-background pb-28 safe-area-bottom">
      {/* Header - Fixed with back button */}
      <header className="sticky top-0 z-50 flex h-11 items-center px-4 bg-background/95 backdrop-blur-sm safe-area-top">
        <button
          onClick={handleBack}
          className="flex h-11 w-11 items-center justify-center rounded-lg touch-feedback -ml-2"
          aria-label="Назад"
        >
          <ChevronLeft className="h-6 w-6 text-foreground" />
        </button>
      </header>

      {/* Product Image - Square cover */}
      <div className="aspect-square w-full bg-muted">
        <OptimizedImage
          src={product.imageUrl}
          webpSrc={product.webpUrl}
          variants={product.imageVariants}
          alt={product.name}
          className="h-full w-full"
          size="large"
          loading="eager"
        />
      </div>

      {/* Product Info */}
      <div className="px-4 py-4 space-y-3">
        {category && (
          <span className="inline-block rounded-full bg-secondary px-3 py-1 text-xs font-medium text-muted-foreground">
            {category.name}
          </span>
        )}
        
        <h1 className="text-xl font-semibold text-foreground">{product.name}</h1>

        <p className="text-sm text-muted-foreground leading-relaxed">
          {product.description}
        </p>

        {product.isWeightProduct && (
          <p className="text-sm text-warning font-medium">
            ⚠️ Весовой товар, цена указана за единицу
          </p>
        )}

        <div className="pt-2">
          <span className="text-2xl font-bold text-primary">
            {product.price.toLocaleString('ru-RU')} ₽
          </span>
          {product.isWeightProduct && (
            <span className="ml-1 text-muted-foreground">/ед.</span>
          )}
        </div>
      </div>

      {/* Bottom Bar - Sticky */}
      <div className="fixed bottom-0 left-0 right-0 z-40 border-t border-border bg-background p-3 sm:p-4 safe-area-bottom">
        {quantityInCart > 0 ? (
          <div className="flex items-center justify-between">
            <span className="text-sm text-muted-foreground">В корзине</span>
            <div className="flex items-center gap-2 sm:gap-3">
              <button
                onClick={handleDecrement}
                className="flex h-11 w-11 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
                aria-label="Уменьшить"
              >
                <Minus className="h-5 w-5" />
              </button>
              <span className="w-8 text-center text-lg font-bold text-foreground">
                {quantityInCart}
              </span>
              <button
                onClick={handleIncrement}
                className="flex h-11 w-11 items-center justify-center rounded-lg bg-secondary text-foreground touch-feedback"
                aria-label="Увеличить"
              >
                <Plus className="h-5 w-5" />
              </button>
            </div>
          </div>
        ) : (
          <div className="flex items-center gap-2 sm:gap-3">
            {/* Quantity controls */}
            <div className="flex items-center border border-border rounded-lg flex-shrink-0">
              <button
                onClick={handleDecrement}
                disabled={localQuantity <= 1}
                className="flex h-11 w-10 sm:w-11 items-center justify-center rounded-l-lg text-foreground touch-feedback disabled:opacity-40"
                aria-label="Уменьшить"
              >
                <Minus className="h-4 w-4" />
              </button>
              <span className="w-8 sm:w-10 text-center text-sm sm:text-base font-semibold text-foreground">
                {localQuantity}
              </span>
              <button
                onClick={handleIncrement}
                className="flex h-11 w-10 sm:w-11 items-center justify-center rounded-r-lg text-foreground touch-feedback"
                aria-label="Увеличить"
              >
                <Plus className="h-4 w-4" />
              </button>
            </div>
            
            {/* Add to cart button */}
            <button
              onClick={handleAddToCart}
              className="flex-1 h-11 rounded-lg bg-primary text-xs sm:text-sm font-semibold text-primary-foreground touch-feedback hover:opacity-90 transition-opacity whitespace-nowrap px-2 sm:px-4"
            >
              <span className="hidden sm:inline">Добавить в корзину</span>
              <span className="sm:hidden">Добавить</span>
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
