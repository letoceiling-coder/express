import { useState, useEffect } from 'react';
import { aboutAPI } from '@/api';
import { Loader2 } from 'lucide-react';
import { OptimizedImage } from '@/components/OptimizedImage';

interface AboutData {
  id: number;
  title: string;
  phone?: string | null;
  address?: string | null;
  description?: string | null;
  bullets?: string[];
  cover_image_url?: string | null;
}

export function WebAboutPage() {
  const [data, setData] = useState<AboutData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const aboutData = await aboutAPI.get();
        setData(aboutData);
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
              className="h-full w-full object-contain"
              size="large"
            />
          </div>
        )}
        <h1 className="text-3xl font-bold">{data.title}</h1>
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
