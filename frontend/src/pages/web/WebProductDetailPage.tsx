import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Plus, Minus, ChevronLeft, Loader2 } from 'lucide-react';
import { useCartStore } from '@/store/cartStore';
import { productsAPI, categoriesAPI } from '@/api';
import { toast } from 'sonner';
import { Product, Category } from '@/types';
import { OptimizedImage } from '@/components/OptimizedImage';
import { Button } from '@/components/ui/button';

export function WebProductDetailPage() {
  const { productId } = useParams();
  const navigate = useNavigate();
  const { items, addItem, updateQuantity } = useCartStore();
  const [product, setProduct] = useState<Product | null>(null);
  const [category, setCategory] = useState<Category | null>(null);
  const [loading, setLoading] = useState(true);

  const cartItem = items.find((item) => item.product.id === productId);
  const quantityInCart = cartItem?.quantity || 0;

  useEffect(() => {
    const fetchProduct = async () => {
      if (!productId) return;
      setLoading(true);
      const productData = await productsAPI.getById(productId);
      setProduct(productData);
      if (productData?.categoryId) {
        const categories = await categoriesAPI.getAll();
        const cat = categories.find((c) => c.id === productData.categoryId);
        setCategory(cat || null);
      }
      setLoading(false);
    };
    fetchProduct();
  }, [productId]);

  const handleAddToCart = () => {
    if (!product) return;
    addItem(product, 1);
    toast.success('Добавлено в корзину', {
      description: product.name,
      duration: 2000,
    });
  };

  const handleIncrement = () => {
    if (!product) return;
    addItem(product, 1);
  };

  const handleDecrement = () => {
    if (!product) return;
    updateQuantity(product.id, quantityInCart - 1);
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
        <p className="mt-4 text-muted-foreground">Загрузка...</p>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="container mx-auto px-4 py-16 text-center">
        <p className="text-muted-foreground">Товар не найден</p>
        <Button variant="outline" className="mt-4" onClick={() => navigate('/')}>
          Вернуться в каталог
        </Button>
      </div>
    );
  }

  return (
      <div className="container mx-auto px-4 py-8 lg:px-8">
        <button
          onClick={() => navigate(-1)}
          className="mb-6 flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors"
        >
          <ChevronLeft className="h-5 w-5" />
          Назад
        </button>

        <div className="grid gap-8 lg:grid-cols-2">
          <div className="aspect-square overflow-hidden rounded-xl bg-muted">
            <OptimizedImage
              src={product.imageUrl || '/placeholder-image.jpg'}
              webpSrc={product.webpUrl}
              variants={product.imageVariants}
              alt={product.name}
              className="h-full w-full object-contain"
              size="large"
            />
          </div>

          <div>
            {category && (
              <p className="mb-2 text-sm text-muted-foreground">{category.name}</p>
            )}
            <h1 className="text-2xl font-bold md:text-3xl">{product.name}</h1>
            <p className="mt-4 text-muted-foreground">{product.description}</p>
            <p className="mt-6 text-2xl font-bold text-primary">
              {product.price.toLocaleString('ru-RU')} ₽
              {product.isWeightProduct && (
                <span className="text-base font-normal text-muted-foreground"> / ед.</span>
              )}
            </p>

            <div className="mt-8 flex flex-wrap items-center gap-4">
              {quantityInCart === 0 ? (
                <Button onClick={handleAddToCart} size="lg">
                  <Plus className="mr-2 h-4 w-4" />
                  В корзину
                </Button>
              ) : (
                <div className="flex items-center gap-2 rounded-lg border border-border">
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={handleDecrement}
                    aria-label="Уменьшить"
                  >
                    <Minus className="h-4 w-4" />
                  </Button>
                  <span className="w-12 text-center font-medium">{quantityInCart}</span>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={handleIncrement}
                    aria-label="Увеличить"
                  >
                    <Plus className="h-4 w-4" />
                  </Button>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
  );
}
