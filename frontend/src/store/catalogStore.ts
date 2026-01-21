import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface CatalogStore {
  activeCategoryId: string | null;
  scrollY: number;
  search: string;
  filters: Record<string, any>;
  
  setActiveCategoryId: (categoryId: string | null) => void;
  setScrollY: (scrollY: number) => void;
  setSearch: (search: string) => void;
  setFilters: (filters: Record<string, any>) => void;
  reset: () => void;
}

export const useCatalogStore = create<CatalogStore>()(
  persist(
    (set) => ({
      activeCategoryId: null,
      scrollY: 0,
      search: '',
      filters: {},

      setActiveCategoryId: (categoryId) => {
        set({ activeCategoryId: categoryId });
      },

      setScrollY: (scrollY) => {
        set({ scrollY });
      },

      setSearch: (search) => {
        set({ search });
      },

      setFilters: (filters) => {
        set({ filters });
      },

      reset: () => {
        set({
          activeCategoryId: null,
          scrollY: 0,
          search: '',
          filters: {},
        });
      },
    }),
    {
      name: 'catalog-storage',
    }
  )
);
