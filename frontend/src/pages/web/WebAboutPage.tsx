import { useState, useEffect } from 'react';
import { aboutAPI, supportSettingsAPI } from '@/api';
import { Loader2, Phone, MapPin, MessageCircle, FileText } from 'lucide-react';
import { OptimizedImage } from '@/components/OptimizedImage';
import { Link } from 'react-router-dom';

interface AboutData {
  id: number;
  title: string;
  phone?: string | null;
  address?: string | null;
  description?: string | null;
  bullets?: string[];
  yandex_maps_url?: string | null;
  cover_image_url?: string | null;
}

export function WebAboutPage() {
  const [data, setData] = useState<AboutData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [supportUrl, setSupportUrl] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const aboutData = await aboutAPI.get();
        setData(aboutData);
        const support = await supportSettingsAPI.get().catch(() => null);
        setSupportUrl(support?.telegram_url ?? null);
      } catch (err: unknown) {
        setError(err instanceof Error ? err.message : 'Ошибка загрузки');
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="mt-4 text-muted-foreground">Загрузка...</p>
        </div>
    );
  }

  if (error || !data) {
    return (
      <div className="container mx-auto px-4 py-16 text-center">
          <p className="text-destructive">{error || 'Данные не найдены'}</p>
        </div>
    );
  }

  return (
      <div className="container mx-auto px-4 py-12 lg:px-8">
        {data.cover_image_url && (
          <div className="mb-8 aspect-[21/9] overflow-hidden rounded-xl bg-muted">
            <OptimizedImage
              src={data.cover_image_url}
              alt={data.title}
              className="h-full w-full object-cover"
              size="large"
            />
          </div>
        )}
        <h1 className="text-3xl font-bold">{data.title}</h1>

        {/* Quick Actions (as in MiniApp) */}
        <div className="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
          <a
            href={`tel:${(data.phone || '+79826824368').replace(/\s+/g, '').replace(/[^\d+]/g, '')}`}
            className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors no-underline"
            aria-label="Позвонить"
          >
            <Phone className="h-5 w-5 text-primary" />
            <span className="text-xs text-foreground text-center leading-tight">Телефон</span>
          </a>

          <button
            type="button"
            onClick={() => {
              const url =
                data.yandex_maps_url ||
                (data.address ? `https://yandex.ru/maps/?text=${encodeURIComponent(data.address)}` : null) ||
                'https://yandex.ru/maps/';
              window.open(url, '_blank', 'noopener,noreferrer');
            }}
            className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors"
            aria-label="Открыть карту"
          >
            <MapPin className="h-5 w-5 text-primary" />
            <span className="text-xs text-foreground text-center leading-tight">Адрес</span>
          </button>

          <button
            type="button"
            onClick={() => {
              const url = supportUrl || 'https://t.me/+79826824368';
              window.open(url, '_blank', 'noopener,noreferrer');
            }}
            className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors"
            aria-label="Поддержка"
          >
            <MessageCircle className="h-5 w-5 text-primary" />
            <span className="text-xs text-foreground text-center leading-tight">Поддержка</span>
          </button>

          <Link
            to="/legal-documents"
            className="flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-card p-3 touch-feedback hover:bg-muted transition-colors no-underline"
            aria-label="Документы"
          >
            <FileText className="h-5 w-5 text-primary" />
            <span className="text-xs text-foreground text-center leading-tight">Документы</span>
          </Link>
        </div>

        {data.description && (
          <div className="mt-6 prose prose-neutral dark:prose-invert max-w-none">
            <p className="text-muted-foreground whitespace-pre-wrap">
              {data.description}
            </p>
          </div>
        )}
        {data.bullets && data.bullets.length > 0 && (
          <ul className="mt-6 list-disc space-y-2 pl-6 text-muted-foreground">
            {data.bullets.map((item, i) => (
              <li key={i}>{item}</li>
            ))}
          </ul>
        )}
        {(data.phone || data.address) && (
          <div className="mt-8 space-y-2 text-muted-foreground">
            {data.phone && <p>Тел: {data.phone}</p>}
            {data.address && <p>Адрес: {data.address}</p>}
          </div>
        )}
      </div>
  );
}
