import { useState, useEffect } from 'react';
import { Plus, Pencil, Trash2, Search, Upload, GripVertical, Save, Loader2 } from 'lucide-react';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
  useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { mockProducts, mockCategories } from '@/data/mockData';
import { Product } from '@/types';
import { toast } from 'sonner';
import { productsAPI, categoriesAPI } from '@/api';

// SortableRow компонент для drag-and-drop
function SortableProductRow({ product, onEdit, onDelete, getCategoryName }: any) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: product.id });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <TableRow ref={setNodeRef} style={style}>
      <TableCell>
        <div
          {...attributes}
          {...listeners}
          className="cursor-grab active:cursor-grabbing p-1"
        >
          <GripVertical className="h-5 w-5 text-slate-400" />
        </div>
      </TableCell>
      <TableCell>
        <img
          src={product.imageUrl}
          alt={product.name}
          className="h-12 w-12 rounded-lg object-cover"
        />
      </TableCell>
      <TableCell>
        <div>
          <p className="font-medium">{product.name}</p>
          <p className="max-w-xs truncate text-sm text-slate-500 dark:text-slate-400">
            {product.description}
          </p>
        </div>
      </TableCell>
      <TableCell>{getCategoryName(product.categoryId)}</TableCell>
      <TableCell>
        {product.price.toLocaleString('ru-RU')} ₽
        {product.isWeightProduct && (
          <span className="ml-1 text-xs text-slate-400">/ед.</span>
        )}
      </TableCell>
      <TableCell className="text-right">
        <div className="flex justify-end gap-2">
          <Button variant="ghost" size="icon" onClick={() => onEdit(product)}>
            <Pencil className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="text-red-500 hover:text-red-600"
            onClick={() => onDelete(product.id)}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </TableCell>
    </TableRow>
  );
}

export function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [categoryFilter, setCategoryFilter] = useState<string>('all');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [hasPositionChanges, setHasPositionChanges] = useState(false);
  const [isSavingPositions, setIsSavingPositions] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    price: '',
    categoryId: '',
    imageUrl: '',
    isWeightProduct: false,
  });

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8, // Минимальное расстояние для активации drag (8px)
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  // Загрузка товаров и категорий из API
  useEffect(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        const [productsData, categoriesData] = await Promise.all([
          productsAPI.getAll(),
          categoriesAPI.getAll(),
        ]);
        setProducts(productsData);
        setCategories(categoriesData);
      } catch (error) {
        console.error('Error loading products:', error);
        toast.error('Ошибка при загрузке товаров');
        // Fallback на mock данные
        setProducts(mockProducts);
        setCategories(mockCategories);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, []);

  const filteredProducts = products.filter((product) => {
    const matchesSearch = product.name
      .toLowerCase()
      .includes(searchTerm.toLowerCase());
    const matchesCategory =
      categoryFilter === 'all' || product.categoryId === categoryFilter;
    return matchesSearch && matchesCategory;
  });

  const getCategoryName = (categoryId: string) => {
    return mockCategories.find((c) => c.id === categoryId)?.name || '';
  };

  const openCreateDialog = () => {
    setEditingProduct(null);
    setFormData({
      name: '',
      description: '',
      price: '',
      categoryId: '',
      imageUrl: '',
      isWeightProduct: false,
    });
    setIsDialogOpen(true);
  };

  const openEditDialog = (product: Product) => {
    setEditingProduct(product);
    setFormData({
      name: product.name,
      description: product.description,
      price: product.price.toString(),
      categoryId: product.categoryId,
      imageUrl: product.imageUrl,
      isWeightProduct: product.isWeightProduct,
    });
    setIsDialogOpen(true);
  };

  const handleSubmit = () => {
    if (!formData.name || !formData.price || !formData.categoryId) {
      toast.error('Заполните все обязательные поля');
      return;
    }

    if (editingProduct) {
      setProducts((prev) =>
        prev.map((p) =>
          p.id === editingProduct.id
            ? {
                ...p,
                ...formData,
                price: parseFloat(formData.price),
                updatedAt: new Date(),
              }
            : p
        )
      );
      toast.success('Товар обновлён');
    } else {
      const newProduct: Product = {
        id: Date.now().toString(),
        name: formData.name,
        description: formData.description,
        price: parseFloat(formData.price),
        categoryId: formData.categoryId,
        imageUrl: formData.imageUrl || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400',
        isWeightProduct: formData.isWeightProduct,
        createdAt: new Date(),
        updatedAt: new Date(),
      };
      setProducts((prev) => [...prev, newProduct]);
      toast.success('Товар добавлен');
    }

    setIsDialogOpen(false);
  };

  const handleDelete = (productId: string) => {
    if (confirm('Вы уверены, что хотите удалить этот товар?')) {
      setProducts((prev) => prev.filter((p) => p.id !== productId));
      toast.success('Товар удалён');
    }
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    if (over && active.id !== over.id) {
      setProducts((items) => {
        const oldIndex = items.findIndex((item) => item.id === active.id);
        const newIndex = items.findIndex((item) => item.id === over.id);
        
        const newItems = arrayMove(items, oldIndex, newIndex);
        setHasPositionChanges(true);
        return newItems;
      });
    }
  };

  const handleSavePositions = async () => {
    setIsSavingPositions(true);
    try {
      const positions = filteredProducts.map((product, index) => ({
        id: parseInt(product.id),
        position: index,
      }));
      await productsAPI.updatePositions(positions);
      toast.success('Порядок товаров сохранён');
      setHasPositionChanges(false);
      
      // Обновляем позиции в локальном состоянии
      setProducts((prev) => {
        const updated = [...prev];
        positions.forEach(({ id, position }) => {
          const product = updated.find((p) => parseInt(p.id) === id);
          if (product) {
            product.position = position;
          }
        });
        return updated;
      });
    } catch (error: any) {
      console.error('Error saving positions:', error);
      toast.error(error?.response?.data?.message || 'Ошибка при сохранении порядка');
    } finally {
      setIsSavingPositions(false);
    }
  };

  if (loading) {
    return (
      <div className="p-4 lg:p-8">
        <div className="flex items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="ml-4 text-muted-foreground">Загрузка товаров...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 lg:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-slate-800 dark:text-slate-100">Товары</h1>
          <p className="mt-1 text-slate-500 dark:text-slate-400">
            Управление каталогом товаров • Перетащите товары для изменения порядка
          </p>
        </div>
        <div className="flex gap-2">
          {hasPositionChanges && (
            <Button
              onClick={handleSavePositions}
              disabled={isSavingPositions}
              className="bg-blue-500 hover:bg-blue-600"
            >
              <Save className="mr-2 h-4 w-4" />
              {isSavingPositions ? 'Сохранение...' : 'Сохранить порядок'}
            </Button>
          )}
          <Button onClick={openCreateDialog} className="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-md shadow-emerald-200 dark:shadow-emerald-900/30">
            <Plus className="mr-2 h-4 w-4" />
            Добавить товар
          </Button>
        </div>
      </div>

      <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
        <CardHeader className="p-4 lg:p-6">
          <div className="flex flex-col gap-4">
            <CardTitle className="text-slate-800 dark:text-slate-100">Список товаров ({filteredProducts.length})</CardTitle>
            <div className="flex flex-col gap-2 sm:flex-row">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <Input
                  placeholder="Поиск товара"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-9"
                />
              </div>
              <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                <SelectTrigger className="w-full sm:w-40">
                  <SelectValue placeholder="Категория" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Все категории</SelectItem>
                  {mockCategories.map((category) => (
                    <SelectItem key={category.id} value={category.id}>
                      {category.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-4 lg:p-6 pt-0">
          {/* Mobile Cards View */}
          <div className="lg:hidden space-y-3">
            {filteredProducts.map((product) => (
              <div
                key={product.id}
                className="flex gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-700/50"
              >
                <img
                  src={product.imageUrl}
                  alt={product.name}
                  className="h-16 w-16 rounded-lg object-cover flex-shrink-0"
                />
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-slate-800 dark:text-slate-100 truncate">{product.name}</p>
                  <p className="text-xs text-slate-500 dark:text-slate-400">{getCategoryName(product.categoryId)}</p>
                  <p className="text-sm font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                    {product.price.toLocaleString('ru-RU')} ₽
                  </p>
                </div>
                <div className="flex flex-col gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    onClick={() => openEditDialog(product)}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-red-500 hover:text-red-600"
                    onClick={() => handleDelete(product.id)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}
          </div>

          {/* Desktop Table View with Drag-and-Drop */}
          <div className="hidden lg:block">
            <DndContext
              sensors={sensors}
              collisionDetection={closestCenter}
              onDragEnd={handleDragEnd}
            >
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12"></TableHead>
                    <TableHead>Фото</TableHead>
                    <TableHead>Название</TableHead>
                    <TableHead>Категория</TableHead>
                    <TableHead>Цена</TableHead>
                    <TableHead className="text-right">Действия</TableHead>
                  </TableRow>
                </TableHeader>
                <SortableContext
                  items={filteredProducts.map(p => p.id)}
                  strategy={verticalListSortingStrategy}
                >
                  <TableBody>
                    {filteredProducts.map((product) => (
                      <SortableProductRow
                        key={product.id}
                        product={product}
                        onEdit={openEditDialog}
                        onDelete={handleDelete}
                        getCategoryName={getCategoryName}
                      />
                    ))}
                  </TableBody>
                </SortableContext>
              </Table>
            </DndContext>
          </div>

          {filteredProducts.length === 0 && (
            <div className="py-8 text-center text-slate-400 dark:text-slate-500">
              Товары не найдены
            </div>
          )}
        </CardContent>
      </Card>

      {/* Product Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {editingProduct ? 'Редактировать товар' : 'Добавить товар'}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label htmlFor="name">Название *</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) =>
                  setFormData({ ...formData, name: e.target.value })
                }
                className="mt-1.5"
              />
            </div>
            <div>
              <Label htmlFor="description">Описание *</Label>
              <Textarea
                id="description"
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
                className="mt-1.5"
                rows={3}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <Label htmlFor="price">Цена (₽) *</Label>
                <Input
                  id="price"
                  type="number"
                  value={formData.price}
                  onChange={(e) =>
                    setFormData({ ...formData, price: e.target.value })
                  }
                  className="mt-1.5"
                />
              </div>
              <div>
                <Label htmlFor="category">Категория *</Label>
                <Select
                  value={formData.categoryId}
                  onValueChange={(value) =>
                    setFormData({ ...formData, categoryId: value })
                  }
                >
                  <SelectTrigger className="mt-1.5">
                    <SelectValue placeholder="Выберите категорию" />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((category) => (
                      <SelectItem key={category.id} value={category.id}>
                        {category.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div>
              <Label htmlFor="imageUrl">URL изображения</Label>
              <Input
                id="imageUrl"
                value={formData.imageUrl}
                onChange={(e) =>
                  setFormData({ ...formData, imageUrl: e.target.value })
                }
                placeholder="https://..."
                className="mt-1.5"
              />
            </div>
            <div className="flex items-center gap-2">
              <Checkbox
                id="isWeight"
                checked={formData.isWeightProduct}
                onCheckedChange={(checked) =>
                  setFormData({ ...formData, isWeightProduct: checked as boolean })
                }
              />
              <Label htmlFor="isWeight" className="cursor-pointer">
                Весовой товар (цена за единицу измерения)
              </Label>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
              Отмена
            </Button>
            <Button onClick={handleSubmit}>
              {editingProduct ? 'Сохранить' : 'Добавить'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
