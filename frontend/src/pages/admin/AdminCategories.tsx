import { useState, useEffect, useCallback } from 'react';
import { Plus, Pencil, Trash2, GripVertical } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { categoriesAPI, productsAPI } from '@/api';
import { Category, Product } from '@/types';
import { toast } from 'sonner';

export function AdminCategories() {
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [categoryName, setCategoryName] = useState('');
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);
  const [draggedOverIndex, setDraggedOverIndex] = useState<number | null>(null);

  // Загрузка данных
  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      const [categoriesData, productsData] = await Promise.all([
        categoriesAPI.getAll(),
        productsAPI.getAll(),
      ]);
      
      // Сортируем категории по sortOrder
      const sortedCategories = [...categoriesData].sort((a, b) => {
        const orderA = a.sortOrder || 0;
        const orderB = b.sortOrder || 0;
        if (orderA !== orderB) return orderA - orderB;
        return a.name.localeCompare(b.name);
      });
      
      setCategories(sortedCategories);
      setProducts(productsData);
    } catch (error: any) {
      console.error('Failed to load categories:', error);
      toast.error('Ошибка загрузки категорий');
    } finally {
      setLoading(false);
    }
  };

  const getProductCount = useCallback((categoryId: string) => {
    return products.filter((p) => p.categoryId === categoryId).length;
  }, [products]);

  const openCreateDialog = () => {
    setEditingCategory(null);
    setCategoryName('');
    setIsDialogOpen(true);
  };

  const openEditDialog = (category: Category) => {
    setEditingCategory(category);
    setCategoryName(category.name);
    setIsDialogOpen(true);
  };

  const handleSubmit = async () => {
    if (!categoryName.trim()) {
      toast.error('Введите название категории');
      return;
    }

    try {
      // TODO: Реализовать создание и обновление через API
      // Пока используем локальное состояние для демонстрации
      if (editingCategory) {
        setCategories((prev) =>
          prev.map((c) =>
            c.id === editingCategory.id
              ? { ...c, name: categoryName, updatedAt: new Date() }
              : c
          )
        );
        toast.success('Категория обновлена');
      } else {
        const newCategory: Category = {
          id: Date.now().toString(),
          name: categoryName,
          sortOrder: categories.length,
          isActive: true,
          createdAt: new Date(),
          updatedAt: new Date(),
        };
        setCategories((prev) => [...prev, newCategory]);
        toast.success('Категория добавлена');
      }
      setIsDialogOpen(false);
    } catch (error: any) {
      toast.error('Ошибка при сохранении категории');
      console.error('Failed to save category:', error);
    }
  };

  const handleDelete = async (categoryId: string) => {
    const productCount = getProductCount(categoryId);
    if (productCount > 0) {
      toast.error(
        `Невозможно удалить категорию с ${productCount} товарами. Сначала переместите или удалите товары.`
      );
      return;
    }

    if (confirm('Вы уверены, что хотите удалить эту категорию?')) {
      try {
        // TODO: Реализовать удаление через API
        setCategories((prev) => prev.filter((c) => c.id !== categoryId));
        toast.success('Категория удалена');
      } catch (error: any) {
        toast.error('Ошибка при удалении категории');
        console.error('Failed to delete category:', error);
      }
    }
  };

  // Drag and Drop handlers
  const handleDragStart = (index: number) => {
    setDraggedIndex(index);
  };

  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    setDraggedOverIndex(index);
  };

  const handleDragLeave = () => {
    setDraggedOverIndex(null);
  };

  const handleDrop = async (e: React.DragEvent, dropIndex: number) => {
    e.preventDefault();
    
    if (draggedIndex === null || draggedIndex === dropIndex) {
      setDraggedIndex(null);
      setDraggedOverIndex(null);
      return;
    }

    const newCategories = [...categories];
    const draggedCategory = newCategories[draggedIndex];
    
    // Удаляем элемент из старой позиции
    newCategories.splice(draggedIndex, 1);
    
    // Вставляем элемент в новую позицию
    newCategories.splice(dropIndex, 0, draggedCategory);
    
    // Обновляем sortOrder для всех категорий
    const updatedCategories = newCategories.map((cat, index) => ({
      ...cat,
      sortOrder: index,
    }));
    
    // Обновляем локальное состояние сразу для плавности UI
    setCategories(updatedCategories);
    setDraggedIndex(null);
    setDraggedOverIndex(null);

    try {
      // Отправляем обновленный порядок на сервер
      await categoriesAPI.updatePositions(
        updatedCategories.map((cat) => ({
          id: cat.id,
          sort_order: cat.sortOrder || 0,
        }))
      );
      toast.success('Порядок категорий обновлен');
    } catch (error: any) {
      console.error('Failed to update positions:', error);
      toast.error('Ошибка при сохранении порядка');
      // Откатываем изменения при ошибке
      loadData();
    }
  };

  const handleDragEnd = () => {
    setDraggedIndex(null);
    setDraggedOverIndex(null);
  };

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(new Date(date));
  };

  if (loading) {
    return (
      <div className="p-4 lg:p-8 flex items-center justify-center min-h-[400px]">
        <div className="text-slate-500 dark:text-slate-400">Загрузка категорий...</div>
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 lg:mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-slate-800 dark:text-slate-100">Категории</h1>
          <p className="mt-1 text-slate-500 dark:text-slate-400">
            Управление категориями товаров. Перетащите для изменения порядка.
          </p>
        </div>
        <Button onClick={openCreateDialog} className="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-md shadow-emerald-200 dark:shadow-emerald-900/30">
          <Plus className="mr-2 h-4 w-4" />
          Добавить категорию
        </Button>
      </div>

      <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
        <CardHeader className="p-4 lg:p-6">
          <CardTitle className="text-slate-800 dark:text-slate-100">Список категорий ({categories.length})</CardTitle>
        </CardHeader>
        <CardContent className="p-4 lg:p-6 pt-0">
          {/* Mobile Cards View */}
          <div className="lg:hidden space-y-3">
            {categories.map((category, index) => (
              <div
                key={category.id}
                draggable
                onDragStart={() => handleDragStart(index)}
                onDragOver={(e) => handleDragOver(e, index)}
                onDragLeave={handleDragLeave}
                onDrop={(e) => handleDrop(e, index)}
                onDragEnd={handleDragEnd}
                className={`flex items-center gap-3 p-4 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-700/50 cursor-move transition-all ${
                  draggedIndex === index ? 'opacity-50' : ''
                } ${draggedOverIndex === index ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''}`}
              >
                <GripVertical className="h-5 w-5 text-slate-400 flex-shrink-0" />
                <div className="flex-1">
                  <p className="font-medium text-slate-800 dark:text-slate-100">{category.name}</p>
                  <p className="text-sm text-slate-500 dark:text-slate-400">
                    {getProductCount(category.id)} товаров • {formatDate(category.createdAt)}
                  </p>
                </div>
                <div className="flex gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                    onClick={(e) => {
                      e.stopPropagation();
                      openEditDialog(category);
                    }}
                  >
                    <Pencil className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 text-red-500 hover:text-red-600"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDelete(category.id);
                    }}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}
          </div>

          {/* Desktop Table View */}
          <div className="hidden lg:block">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-10"></TableHead>
                  <TableHead>Название</TableHead>
                  <TableHead>Порядок</TableHead>
                  <TableHead>Товаров</TableHead>
                  <TableHead>Создана</TableHead>
                  <TableHead className="text-right">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {categories.map((category, index) => (
                  <TableRow
                    key={category.id}
                    draggable
                    onDragStart={() => handleDragStart(index)}
                    onDragOver={(e) => handleDragOver(e, index)}
                    onDragLeave={handleDragLeave}
                    onDrop={(e) => handleDrop(e, index)}
                    onDragEnd={handleDragEnd}
                    className={`cursor-move transition-all ${
                      draggedIndex === index ? 'opacity-50' : ''
                    } ${draggedOverIndex === index ? 'bg-blue-50 dark:bg-blue-900/20' : ''}`}
                  >
                    <TableCell className="w-10">
                      <GripVertical className="h-5 w-5 text-slate-400" />
                    </TableCell>
                    <TableCell className="font-medium text-slate-700 dark:text-slate-200">
                      {category.name}
                    </TableCell>
                    <TableCell className="text-slate-600 dark:text-slate-300">
                      {category.sortOrder ?? index}
                    </TableCell>
                    <TableCell className="text-slate-600 dark:text-slate-300">
                      {getProductCount(category.id)}
                    </TableCell>
                    <TableCell className="text-slate-500 dark:text-slate-400">
                      {formatDate(category.createdAt)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                          onClick={(e) => {
                            e.stopPropagation();
                            openEditDialog(category);
                          }}
                        >
                          <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                          onClick={(e) => {
                            e.stopPropagation();
                            handleDelete(category.id);
                          }}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          {categories.length === 0 && (
            <div className="py-8 text-center text-slate-400 dark:text-slate-500">
              Категории не найдены
            </div>
          )}
        </CardContent>
      </Card>

      {/* Category Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {editingCategory ? 'Редактировать категорию' : 'Добавить категорию'}
            </DialogTitle>
          </DialogHeader>
          <div>
            <Label htmlFor="categoryName">Название категории *</Label>
            <Input
              id="categoryName"
              value={categoryName}
              onChange={(e) => setCategoryName(e.target.value)}
              placeholder="Например: Горячие блюда"
              className="mt-1.5"
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  handleSubmit();
                }
              }}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
              Отмена
            </Button>
            <Button onClick={handleSubmit}>
              {editingCategory ? 'Сохранить' : 'Добавить'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
