import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type OrderMode = 'pickup' | 'delivery';

interface OrderModeState {
  orderMode: OrderMode;
  setOrderMode: (mode: OrderMode) => void;
}

export const useOrderModeStore = create<OrderModeState>()(
  persist(
    (set) => ({
      orderMode: 'pickup',
      setOrderMode: (mode) => set({ orderMode: mode }),
    }),
    { name: 'order-mode', version: 1 }
  )
);
