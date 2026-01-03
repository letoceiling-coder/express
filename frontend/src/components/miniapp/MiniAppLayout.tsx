import { ReactNode } from 'react';
import { MiniAppHeader } from './MiniAppHeader';
import { BottomNavigation } from './BottomNavigation';

interface MiniAppLayoutProps {
  children: ReactNode;
  title?: string;
  showBack?: boolean;
  showCart?: boolean;
}

export function MiniAppLayout({ children, title, showBack, showCart = true }: MiniAppLayoutProps) {
  return (
    <div className="min-h-screen bg-background pb-20">
      <MiniAppHeader title={title} showBack={showBack} showCart={showCart} />
      {children}
      <BottomNavigation />
    </div>
  );
}
