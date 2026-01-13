import { ReactNode } from 'react';
import { MiniAppHeader } from './MiniAppHeader';
import { BottomNavigation } from './BottomNavigation';

interface MiniAppLayoutProps {
  children: ReactNode;
  title?: string;
  showBack?: boolean;
}

export function MiniAppLayout({ children, title, showBack }: MiniAppLayoutProps) {
  return (
    <div className="min-h-screen bg-background pb-20">
      <MiniAppHeader title={title} showBack={showBack} />
      {children}
      <BottomNavigation />
    </div>
  );
}
