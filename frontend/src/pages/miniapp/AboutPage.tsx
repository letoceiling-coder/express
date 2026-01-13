import { useState, useEffect } from 'react';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { Loader2, Phone, MapPin, Copy, Check, MessageCircle, ChevronDown, ChevronUp } from 'lucide-react';
import { aboutAPI } from '@/api';
import { OptimizedImage } from '@/components/OptimizedImage';
import { openTelegramLink } from '@/lib/telegram';
import { toast } from '@/hooks/use-toast';

interface AboutPageData {
  id: number;
  title: string;
  phone?: string | null;
  address?: string | null;
  description?: string | null;
  bullets?: string[];
  yandex_maps_url?: string | null;
  support_telegram_url?: string | null;
  cover_image_url?: string | null;
}

export function AboutPage() {
  const [data, setData] = useState<AboutPageData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showFullDescription, setShowFullDescription] = useState(false);
  const [phoneCopied, setPhoneCopied] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);
        const response = await aboutAPI.get();
        setData(response);
      } catch (err: any) {
        console.error('Error loading about page:', err);
        setError(err.message || 'Ошибка при загрузке данных');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const handlePhoneClick = (phone: string) => {
    // Очищаем номер от пробелов и других символов для tel: ссылки
    const cleanPhone = phone.replace(/\s+/g, '').replace(/[^\d+]/g, '');
    const telUrl = `tel:${cleanPhone}`;
    
    // Для iOS нужно использовать создание элемента <a> и программный клик
    // Это работает как на Android, так и на iOS
    const link = document.createElement('a');
    link.href = telUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    
    // Программно кликаем по ссылке
    link.click();
    
    // Удаляем элемент после небольшой задержки
    setTimeout(() => {
      document.body.removeChild(link);
    }, 100);
  };

  const handleMapsClick = (url: string) => {
    const tg = window.Telegram?.WebApp;
    if (tg && tg.openLink) {
      tg.openLink(url, { try_instant_view: false });
    } else {
      window.open(url, '_blank');
    }
  };

  const handleCopyPhone = async (phone: string) => {
    try {
      await navigator.clipboard.writeText(phone);
      setPhoneCopied(true);
      toast({
        title: 'Номер скопирован',
        description: 'Номер телефона скопирован в буфер обмена',
        duration: 2000,
      });
      setTimeout(() => setPhoneCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy phone:', err);
      toast({
        title: 'Ошибка',
        description: 'Не удалось скопировать номер телефона',
        variant: 'destructive',
      });
    }
  };

  const handleSupportClick = (url: string) => {
    if (url) {
      openTelegramLink(url);
    }
  };

  // Функция для обрезки описания до 4-6 строк
  const getTruncatedDescription = (text: string, maxLines: number = 4) => {
    const lines = text.split('\n');
    if (lines.length <= maxLines) return text;
    return lines.slice(0, maxLines).join('\n');
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="О нас" />
        <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка...</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="О нас" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <p className="text-destructive">{error || 'Ошибка загрузки данных'}</p>
          <button
            onClick={() => window.location.reload()}
            className="mt-4 h-11 rounded-lg bg-primary px-6 font-semibold text-primary-foreground touch-feedback"
          >
            Попробовать снова
          </button>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  // Если данных нет, показываем плейсхолдер
  if (!data.title && !data.description && !data.phone && !data.address) {
    return (
      <div className="min-h-screen bg-background pb-20">
        <MiniAppHeader title="О нас" />
        <div className="flex flex-col items-center justify-center px-4 py-16">
          <p className="text-center text-muted-foreground">Информация скоро появится</p>
        </div>
        <BottomNavigation />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background pb-20">
      <MiniAppHeader title="О нас" />

      <div className="space-y-6 px-4 py-6">
        {/* Cover Image */}
        <div className="relative -mx-4 h-48 overflow-hidden rounded-xl bg-muted">
          {data.cover_image_url ? (
            <OptimizedImage
              src={data.cover_image_url}
              alt={data.title}
              className="h-full w-full object-cover"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center">
              <p className="text-muted-foreground">Изображение не загружено</p>
            </div>
          )}
        </div>

        {/* Title */}
        {data.title && (
          <h1 className="text-2xl font-bold text-foreground">{data.title}</h1>
        )}

        {/* Quick Actions - 3 buttons in a row */}
        {(data.phone || data.address || data.yandex_maps_url || data.support_telegram_url) && (
          <div className="grid grid-cols-3 gap-2">
            {/* Phone Button */}
            {data.phone && (
              <a
                href={`tel:${data.phone.replace(/\s+/g, '').replace(/[^\d+]/g, '')}`}
                onClick={(e) => {
                  e.preventDefault();
                  handlePhoneClick(data.phone!);
                }}
                className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors no-underline"
                aria-label="Позвонить"
              >
                <div className="relative">
                  <Phone className="h-5 w-5 text-primary" />
                  <button
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      handleCopyPhone(data.phone!);
                    }}
                    className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-background border border-border z-10"
                    aria-label="Скопировать номер"
                  >
                    {phoneCopied ? (
                      <Check className="h-2.5 w-2.5 text-green-500" />
                    ) : (
                      <Copy className="h-2.5 w-2.5 text-muted-foreground" />
                    )}
                  </button>
                </div>
                <span className="text-xs text-foreground text-center leading-tight">Телефон</span>
              </a>
            )}

            {/* Address/Map Button */}
            {(data.address || data.yandex_maps_url) && (
              <button
                onClick={() => {
                  const mapsUrl = data.yandex_maps_url || 'https://yandex.ru/maps/-/CLRQaBlB';
                  handleMapsClick(mapsUrl);
                }}
                className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors"
                aria-label="Открыть карту"
              >
                <MapPin className="h-5 w-5 text-primary" />
                <span className="text-xs text-foreground text-center leading-tight">Адрес</span>
              </button>
            )}

            {/* Support Button */}
            <button
              onClick={() => {
                const supportUrl = data.support_telegram_url || 'https://t.me/+79826824368';
                handleSupportClick(supportUrl);
              }}
              className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors"
              aria-label="Поддержка"
            >
              <MessageCircle className="h-5 w-5 text-primary" />
              <span className="text-xs text-foreground text-center leading-tight">Поддержка</span>
            </button>
          </div>
        )}

        {/* Description */}
        {data.description && (
          <div className="space-y-2">
            <p className="text-foreground whitespace-pre-line leading-relaxed">
              {showFullDescription 
                ? data.description 
                : getTruncatedDescription(data.description, 4)}
            </p>
            {data.description.split('\n').length > 4 && (
              <button
                onClick={() => setShowFullDescription(!showFullDescription)}
                className="flex items-center gap-1 text-primary hover:underline text-sm font-medium touch-feedback"
              >
                {showFullDescription ? (
                  <>
                    <ChevronUp className="h-4 w-4" />
                    Скрыть
                  </>
                ) : (
                  <>
                    <ChevronDown className="h-4 w-4" />
                    Показать больше
                  </>
                )}
              </button>
            )}
          </div>
        )}

        {/* Info Cards - 3 cards from bullets */}
        {data.bullets && data.bullets.length > 0 && (
          <div className="grid gap-3">
            {data.bullets.slice(0, 3).map((bullet, index) => (
              <div
                key={index}
                className="rounded-xl border border-border bg-card p-4"
              >
                <p className="text-sm text-foreground leading-relaxed">{bullet}</p>
              </div>
            ))}
          </div>
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}

