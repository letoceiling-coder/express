import React, { useState } from 'react';
import { cn } from '@/lib/utils';

interface OptimizedImageProps {
  src: string;
  webpSrc?: string;
  variants?: {
    thumbnail?: { webp?: string; jpeg?: string };
    medium?: { webp?: string; jpeg?: string };
    large?: { webp?: string; jpeg?: string };
  };
  alt: string;
  className?: string;
  size?: 'thumbnail' | 'medium' | 'large' | 'original';
  loading?: 'lazy' | 'eager';
  onError?: (e: React.SyntheticEvent<HTMLImageElement, Event>) => void;
  placeholder?: string;
}

/**
 * Оптимизированный компонент изображения с поддержкой WebP и fallback
 * 
 * Автоматически выбирает оптимальный размер и формат:
 * - Использует WebP для современных браузеров
 * - Fallback на JPEG для старых браузеров
 * - Поддержка lazy loading
 * - Placeholder при загрузке
 */
export function OptimizedImage({
  src,
  webpSrc,
  variants,
  alt,
  className,
  size = 'medium',
  loading = 'lazy',
  onError,
  placeholder,
}: OptimizedImageProps) {
  const [imageError, setImageError] = useState(false);
  const [isLoading, setIsLoading] = useState(true);

  const handleError = (e: React.SyntheticEvent<HTMLImageElement, Event>) => {
    setImageError(true);
    setIsLoading(false);
    if (onError) {
      onError(e);
    }
  };

  const handleLoad = () => {
    setIsLoading(false);
  };

  // Если ошибка загрузки, показываем placeholder или оригинальный src
  if (imageError) {
    return (
      <img
        src={placeholder || src}
        alt={alt}
        className={cn('object-cover object-center', className)}
        loading={loading}
      />
    );
  }

  // Определяем источники для picture элемента
  let webpSource: string | null = null;
  let jpegSource: string | null = null;

  if (variants && size !== 'original') {
    // Используем вариант указанного размера
    const variant = variants[size];
    if (variant) {
      webpSource = variant.webp || null;
      jpegSource = variant.jpeg || null;
    }
  } else if (webpSrc) {
    // Используем WebP оригинал
    webpSource = webpSrc;
    jpegSource = src;
  } else {
    // Fallback на оригинальный src
    jpegSource = src;
  }

  // Если есть WebP и JPEG варианты, используем picture элемент
  if (webpSource && jpegSource) {
    return (
      <picture className={cn('block', className)}>
        <source srcSet={webpSource} type="image/webp" />
        <img
          src={jpegSource}
          alt={alt}
          className={cn(
            'w-full h-full object-cover object-center transition-opacity duration-300',
            isLoading && 'opacity-0',
            !isLoading && 'opacity-100',
            className
          )}
          loading={loading}
          onError={handleError}
          onLoad={handleLoad}
        />
        {isLoading && placeholder && (
          <img
            src={placeholder}
            alt=""
            className={cn(
              'absolute inset-0 w-full h-full object-cover object-center opacity-50 blur-sm',
              className
            )}
            aria-hidden="true"
          />
        )}
      </picture>
    );
  }

  // Простой img элемент если нет вариантов
  return (
    <img
      src={jpegSource || src}
      alt={alt}
      className={cn(
        'w-full h-full object-cover object-center transition-opacity duration-300',
        isLoading && 'opacity-0',
        !isLoading && 'opacity-100',
        className
      )}
      loading={loading}
      onError={handleError}
      onLoad={handleLoad}
    />
  );
}

/**
 * Хук для получения оптимального размера изображения на основе viewport
 */
export function useOptimalImageSize() {
  const [size, setSize] = React.useState<'thumbnail' | 'medium' | 'large'>('medium');

  React.useEffect(() => {
    const updateSize = () => {
      const width = window.innerWidth;
      if (width <= 600) {
        setSize('thumbnail');
      } else if (width <= 1024) {
        setSize('medium');
      } else {
        setSize('large');
      }
    };

    updateSize();
    window.addEventListener('resize', updateSize);
    return () => window.removeEventListener('resize', updateSize);
  }, []);

  return size;
}






