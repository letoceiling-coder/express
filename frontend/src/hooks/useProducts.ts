import { useState, useEffect } from 'react';
import { categoriesAPI, productsAPI } from '@/api';
import { Category, Product } from '@/types';

export function useProducts(categoryId?: string) {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const [categoriesData, productsData] = await Promise.all([
        categoriesAPI.getAll(),
        productsAPI.getAll(categoryId),
      ]);
      
      // Сортируем категории по sortOrder
      const sortedCategories = [...categoriesData].sort((a, b) => {
        const orderA = a.sortOrder || 0;
        const orderB = b.sortOrder || 0;
        if (orderA !== orderB) return orderA - orderB;
        return a.name.localeCompare(b.name);
      });
      
      // Сортируем товары по sortOrder
      const sortedProducts = [...productsData].sort((a, b) => {
        const orderA = a.sortOrder || 0;
        const orderB = b.sortOrder || 0;
        if (orderA !== orderB) return orderA - orderB;
        return a.name.localeCompare(b.name);
      });
      
      setCategories(sortedCategories);
      setProducts(sortedProducts);
    } catch (err) {
      console.error('Failed to load products:', err);
      setError('Ошибка загрузки данных');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [categoryId]);

  return { products, categories, loading, error, refetch: loadData };
}
