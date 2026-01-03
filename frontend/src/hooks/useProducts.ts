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
      
      setCategories(categoriesData);
      setProducts(productsData);
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
