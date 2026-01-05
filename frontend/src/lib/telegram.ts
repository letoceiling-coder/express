// Telegram WebApp API Integration

declare global {
  interface Window {
    Telegram?: {
      WebApp?: TelegramWebApp;
    };
  }
}

interface TelegramWebApp {
  initData: string;
  initDataUnsafe: {
    user?: {
      id: number;
      first_name: string;
      last_name?: string;
      username?: string;
      language_code?: string;
      is_premium?: boolean;
    };
    query_id?: string;
    auth_date?: number;
    hash?: string;
  };
  version: string;
  platform: string;
  colorScheme: 'light' | 'dark';
  themeParams: {
    bg_color?: string;
    text_color?: string;
    hint_color?: string;
    link_color?: string;
    button_color?: string;
    button_text_color?: string;
    secondary_bg_color?: string;
  };
  isExpanded: boolean;
  viewportHeight: number;
  viewportStableHeight: number;
  headerColor: string;
  backgroundColor: string;
  BackButton: {
    isVisible: boolean;
    show: () => void;
    hide: () => void;
    onClick: (callback: () => void) => void;
    offClick: (callback: () => void) => void;
  };
  MainButton: {
    text: string;
    color: string;
    textColor: string;
    isVisible: boolean;
    isActive: boolean;
    isProgressVisible: boolean;
    setText: (text: string) => void;
    show: () => void;
    hide: () => void;
    enable: () => void;
    disable: () => void;
    showProgress: (leaveActive?: boolean) => void;
    hideProgress: () => void;
    onClick: (callback: () => void) => void;
    offClick: (callback: () => void) => void;
  };
  HapticFeedback: {
    impactOccurred: (style: 'light' | 'medium' | 'heavy' | 'rigid' | 'soft') => void;
    notificationOccurred: (type: 'error' | 'success' | 'warning') => void;
    selectionChanged: () => void;
  };
  ready: () => void;
  expand: () => void;
  close: () => void;
  sendData: (data: string) => void;
  openLink: (url: string, options?: { try_instant_view?: boolean }) => void;
  openTelegramLink: (url: string) => void;
  showPopup: (params: {
    title?: string;
    message: string;
    buttons?: Array<{
      id?: string;
      type?: 'default' | 'ok' | 'close' | 'cancel' | 'destructive';
      text?: string;
    }>;
  }, callback?: (buttonId: string) => void) => void;
  showAlert: (message: string, callback?: () => void) => void;
  showConfirm: (message: string, callback?: (confirmed: boolean) => void) => void;
  requestContact: (callback?: (shared: boolean) => void) => void;
  setHeaderColor: (color: string) => void;
  setBackgroundColor: (color: string) => void;
  enableClosingConfirmation: () => void;
  disableClosingConfirmation: () => void;
}

export function initTelegramWebApp(): void {
  const tg = window.Telegram?.WebApp;
  
  if (tg) {
    // Initialize the app
    tg.ready();
    
    // Expand to full height
    tg.expand();
    
    // Set theme colors
    if (tg.colorScheme === 'dark') {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
    
    console.log('Telegram WebApp initialized', {
      version: tg.version,
      platform: tg.platform,
      colorScheme: tg.colorScheme,
      user: tg.initDataUnsafe?.user,
    });
  } else {
    console.log('Running outside Telegram WebApp');
  }
}

export function getTelegramUser() {
  // Пробуем получить пользователя из разных источников
  const tg = window.Telegram?.WebApp;
  
  if (!tg) {
    console.warn('getTelegramUser - window.Telegram is not available');
    return null;
  }
  
  const user = tg.initDataUnsafe?.user;
  
  if (user) {
    console.log('getTelegramUser - User found:', { id: user.id, firstName: user.first_name });
    return user;
  }
  
  // Пробуем получить из initData напрямую
  if (tg.initData) {
    try {
      const params = new URLSearchParams(tg.initData);
      const userParam = params.get('user');
      if (userParam) {
        const userData = JSON.parse(decodeURIComponent(userParam));
        console.log('getTelegramUser - User from initData:', userData);
        return userData;
      }
    } catch (e) {
      console.warn('getTelegramUser - Failed to parse initData:', e);
    }
  }
  
  console.warn('getTelegramUser - No user data found', {
    hasWebApp: !!tg,
    hasInitData: !!tg.initData,
    hasInitDataUnsafe: !!tg.initDataUnsafe,
  });
  
  return null;
}

export function getTelegramTheme(): 'light' | 'dark' {
  return window.Telegram?.WebApp?.colorScheme || 'light';
}

export function hapticFeedback(type: 'light' | 'medium' | 'heavy' | 'success' | 'error' | 'warning' | 'selection') {
  const tg = window.Telegram?.WebApp;
  if (!tg?.HapticFeedback) return;
  
  switch (type) {
    case 'light':
    case 'medium':
    case 'heavy':
      tg.HapticFeedback.impactOccurred(type);
      break;
    case 'success':
    case 'error':
    case 'warning':
      tg.HapticFeedback.notificationOccurred(type);
      break;
    case 'selection':
      tg.HapticFeedback.selectionChanged();
      break;
  }
}

export function showTelegramPopup(
  message: string,
  title?: string,
  callback?: () => void
) {
  const tg = window.Telegram?.WebApp;
  if (tg) {
    tg.showAlert(message, callback);
  } else {
    alert(message);
    callback?.();
  }
}

export function showTelegramConfirm(
  message: string,
  callback: (confirmed: boolean) => void
) {
  const tg = window.Telegram?.WebApp;
  if (tg) {
    tg.showConfirm(message, callback);
  } else {
    const confirmed = confirm(message);
    callback(confirmed);
  }
}

export function openTelegramLink(url: string) {
  const tg = window.Telegram?.WebApp;
  if (tg) {
    tg.openTelegramLink(url);
  } else {
    window.open(url, '_blank');
  }
}

export function closeTelegramApp() {
  const tg = window.Telegram?.WebApp;
  if (tg) {
    tg.close();
  }
}
