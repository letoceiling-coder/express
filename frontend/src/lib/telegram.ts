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
  // Функция инициализации
  const initialize = (tg: TelegramWebApp) => {
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
      userId: tg.initDataUnsafe?.user?.id,
      initData: tg.initData ? 'present (' + tg.initData.length + ' chars)' : 'missing',
      initDataUnsafe: tg.initDataUnsafe ? 'present' : 'missing',
    });
  };
  
  // Проверяем, доступен ли Telegram WebApp
  const checkTelegram = () => {
    // Telegram может инжектировать скрипт, поэтому проверяем несколько способов
    if (window.Telegram?.WebApp) {
      initialize(window.Telegram.WebApp);
      return true;
    }
    
    // Также проверяем глобальный объект (если скрипт загружен по-другому)
    if ((window as any).Telegram?.WebApp) {
      initialize((window as any).Telegram.WebApp);
      return true;
    }
    
    return false;
  };
  
  // Пробуем сразу
  if (checkTelegram()) {
    return;
  }
  
  // Если не загружен, ждем события загрузки DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      if (!checkTelegram()) {
        // Пробуем через интервал
        waitForTelegram();
      }
    });
  } else {
    waitForTelegram();
  }
  
  function waitForTelegram() {
    let attempts = 0;
    const maxAttempts = 20; // Увеличиваем количество попыток
    const interval = setInterval(() => {
      attempts++;
      if (checkTelegram() || attempts >= maxAttempts) {
        clearInterval(interval);
        if (attempts >= maxAttempts) {
          console.warn('Telegram WebApp not found after', maxAttempts, 'attempts');
          console.warn('window.Telegram:', window.Telegram);
          console.warn('document.readyState:', document.readyState);
        }
      }
    }, 200); // Увеличиваем интервал до 200мс
  }
}

export function getTelegramUser() {
  // Пробуем получить пользователя из разных источников
  let tg = window.Telegram?.WebApp;
  
  // Если не найден, пробуем через глобальный объект
  if (!tg && (window as any).Telegram?.WebApp) {
    tg = (window as any).Telegram.WebApp;
  }
  
  if (!tg) {
    console.warn('getTelegramUser - window.Telegram.WebApp is not available');
    console.warn('getTelegramUser - window.Telegram:', window.Telegram);
    console.warn('getTelegramUser - (window as any).Telegram:', (window as any).Telegram);
    return null;
  }
  
  console.log('getTelegramUser - Telegram WebApp found:', {
    version: tg.version,
    platform: tg.platform,
    hasInitData: !!tg.initData,
    hasInitDataUnsafe: !!tg.initDataUnsafe,
  });
  
  const user = tg.initDataUnsafe?.user;
  
  if (user) {
    console.log('getTelegramUser - User found in initDataUnsafe:', { 
      id: user.id, 
      firstName: user.first_name,
      username: user.username,
    });
    return user;
  }
  
  // Пробуем получить из initData напрямую
  if (tg.initData) {
    try {
      console.log('getTelegramUser - Trying to parse initData, length:', tg.initData.length);
      const params = new URLSearchParams(tg.initData);
      const userParam = params.get('user');
      if (userParam) {
        const userData = JSON.parse(decodeURIComponent(userParam));
        console.log('getTelegramUser - User from initData:', userData);
        return userData;
      } else {
        console.warn('getTelegramUser - initData exists but user parameter not found');
        console.warn('getTelegramUser - initData keys:', Array.from(params.keys()));
      }
    } catch (e) {
      console.warn('getTelegramUser - Failed to parse initData:', e);
      console.warn('getTelegramUser - initData preview:', tg.initData.substring(0, 100));
    }
  }
  
  console.error('getTelegramUser - No user data found', {
    hasWebApp: !!tg,
    hasInitData: !!tg.initData,
    initDataLength: tg.initData?.length || 0,
    hasInitDataUnsafe: !!tg.initDataUnsafe,
    initDataUnsafeKeys: tg.initDataUnsafe ? Object.keys(tg.initDataUnsafe) : [],
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
