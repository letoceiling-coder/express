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
  const [showFullBullets, setShowFullBullets] = useState(false);
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
    window.location.href = `tel:${phone}`;
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

        {/* Phone */}
        {data.phone && (
          <div className="flex items-center gap-3">
            <Phone className="h-5 w-5 text-muted-foreground flex-shrink-0" />
            <a
              href={`tel:${data.phone.replace(/\s/g, '')}`}
              onClick={(e) => {
                e.preventDefault();
                handlePhoneClick(data.phone!);
              }}
              className="text-primary underline-offset-4 hover:underline touch-feedback flex-1"
            >
              {data.phone}
            </a>
            <button
              onClick={() => handleCopyPhone(data.phone!)}
              className="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted touch-feedback transition-colors"
              aria-label="Скопировать номер телефона"
            >
              {phoneCopied ? (
                <Check className="h-4 w-4 text-green-500" />
              ) : (
                <Copy className="h-4 w-4 text-muted-foreground" />
              )}
            </button>
          </div>
        )}

        {/* Address */}
        {data.address && (
          <div className="flex items-start gap-3">
            <MapPin className="h-5 w-5 mt-0.5 text-muted-foreground flex-shrink-0" />
            <p className="text-foreground leading-relaxed">{data.address}</p>
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

        {/* Bullets */}
        {data.bullets && data.bullets.length > 0 && (
          <div className="space-y-2">
            <ul className="space-y-2">
              {(showFullBullets ? data.bullets : data.bullets.slice(0, 4)).map((bullet, index) => (
                <li key={index} className="flex items-start gap-3">
                  <span className="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary" />
                  <span className="text-foreground leading-relaxed">{bullet}</span>
                </li>
              ))}
            </ul>
            {data.bullets.length > 4 && (
              <button
                onClick={() => setShowFullBullets(!showFullBullets)}
                className="flex items-center gap-1 text-primary hover:underline text-sm font-medium touch-feedback"
              >
                {showFullBullets ? (
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

        {/* Yandex Maps Link */}
        {data.yandex_maps_url && (
          <button
            onClick={() => handleMapsClick(data.yandex_maps_url!)}
            className="w-full flex items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 font-semibold text-primary-foreground touch-feedback"
          >
            <ExternalLink className="h-5 w-5" />
            Открыть в Яндекс.Картах
          </button>
        )}

        {/* Support Block */}
        {data.support_telegram_url && (
          <div className="rounded-xl border border-border bg-card p-4 space-y-3">
            <div className="flex items-start gap-3">
              <MessageCircle className="h-5 w-5 text-muted-foreground flex-shrink-0 mt-0.5" />
              <div className="flex-1 space-y-2">
                <h3 className="font-semibold text-foreground">Поддержка</h3>
                <p className="text-sm text-muted-foreground">
                  Если что-то не работает — напишите в Telegram
                </p>
                <button
                  onClick={() => handleSupportClick(data.support_telegram_url!)}
                  className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground touch-feedback hover:opacity-90 transition-opacity"
                >
                  <MessageCircle className="h-4 w-4" />
                  Написать в поддержку
                </button>
              </div>
            </div>
          </div>
        )}
      </div>

      <BottomNavigation />
    </div>
  );
}

