import { useState, useEffect } from 'react';
import { MiniAppHeader } from '@/components/miniapp/MiniAppHeader';
import { BottomNavigation } from '@/components/miniapp/BottomNavigation';
import { Loader2, Phone, MapPin, ExternalLink } from 'lucide-react';
import { aboutAPI } from '@/api';
import { OptimizedImage } from '@/components/OptimizedImage';
import { openTelegramLink } from '@/lib/telegram';

interface AboutPageData {
  id: number;
  title: string;
  phone?: string | null;
  address?: string | null;
  description?: string | null;
  bullets?: string[];
  yandex_maps_url?: string | null;
  cover_image_url?: string | null;
}

export function AboutPage() {
  const [data, setData] = useState<AboutPageData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

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
        {data.cover_image_url && (
          <div className="relative -mx-4 h-48 overflow-hidden rounded-xl">
            <OptimizedImage
              src={data.cover_image_url}
              alt={data.title}
              className="h-full w-full object-cover"
            />
          </div>
        )}

        {/* Title */}
        {data.title && (
          <h1 className="text-2xl font-bold text-foreground">{data.title}</h1>
        )}

        {/* Phone */}
        {data.phone && (
          <div className="flex items-center gap-3">
            <Phone className="h-5 w-5 text-muted-foreground" />
            <a
              href={`tel:${data.phone}`}
              onClick={(e) => {
                e.preventDefault();
                handlePhoneClick(data.phone!);
              }}
              className="text-primary underline-offset-4 hover:underline touch-feedback"
            >
              {data.phone}
            </a>
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
              {data.description}
            </p>
          </div>
        )}

        {/* Bullets */}
        {data.bullets && data.bullets.length > 0 && (
          <div className="space-y-2">
            <ul className="space-y-2">
              {data.bullets.map((bullet, index) => (
                <li key={index} className="flex items-start gap-3">
                  <span className="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary" />
                  <span className="text-foreground leading-relaxed">{bullet}</span>
                </li>
              ))}
            </ul>
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
      </div>

      <BottomNavigation />
    </div>
  );
}

