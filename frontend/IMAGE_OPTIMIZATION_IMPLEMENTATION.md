# Реализация оптимизации изображений

## Выполнено

### Backend (Laravel)

1. ✅ Установлена библиотека `intervention/image` для работы с изображениями
2. ✅ Создан `ImageService` для автоматической обработки:
   - Конвертация в WebP формат
   - Генерация вариантов размеров: thumbnail (300px), medium (800px), large (1200px)
   - Сохранение как WebP, так и JPEG fallback для каждого варианта
3. ✅ Обновлен `MediaController`:
   - При загрузке изображений автоматически запускается обработка
   - Информация о WebP и вариантах сохраняется в metadata
4. ✅ Обновлена модель `Media`:
   - Добавлены методы `getWebpUrlAttribute()`, `getVariantUrl()`, `getOptimalVariant()`
5. ✅ Обновлен `MediaResource`:
   - Возвращает `webp_url` и `variants` в API ответах

### Frontend (React)

1. ✅ Создан компонент `OptimizedImage`:
   - Поддержка `<picture>` элемента с WebP и fallback
   - Автоматический выбор оптимального размера
   - Lazy loading
   - Placeholder при загрузке
   - Обработка ошибок
2. ✅ Обновлены типы `Product`:
   - Добавлены поля `webpUrl` и `imageVariants`
3. ✅ Обновлен API mapping:
   - Маппинг `webp_url` и `variants` из ответа сервера
4. ✅ Интегрирован `OptimizedImage`:
   - В `ProductCard` (grid и list варианты)
   - В `ProductDetailPage`

## Структура файлов

```
public/media/photos/
  ├── original/          # Оригинальные файлы
  ├── webp/              # WebP версии оригиналов
  └── variants/          # Варианты размеров
      ├── {name}_thumbnail.webp
      ├── {name}_thumbnail.jpg
      ├── {name}_medium.webp
      ├── {name}_medium.jpg
      ├── {name}_large.webp
      └── {name}_large.jpg
```

## Использование

### На фронтенде

```tsx
import { OptimizedImage } from '@/components/OptimizedImage';

<OptimizedImage
  src={product.imageUrl}
  webpSrc={product.webpUrl}
  variants={product.imageVariants}
  alt={product.name}
  size="medium"  // thumbnail | medium | large | original
  loading="lazy"
  placeholder="/placeholder-image.jpg"
/>
```

### На бэкенде

При загрузке изображения через API `/api/v1/media`, обработка происходит автоматически. В ответе будут доступны:

```json
{
  "data": {
    "id": 1,
    "url": "/upload/photo.jpg",
    "webp_url": "/upload/webp/photo.webp",
    "variants": {
      "thumbnail": {
        "webp": "/upload/variants/photo_thumbnail.webp",
        "jpeg": "/upload/variants/photo_thumbnail.jpg",
        "width": 300,
        "height": 300
      },
      "medium": {
        "webp": "/upload/variants/photo_medium.webp",
        "jpeg": "/upload/variants/photo_medium.jpg",
        "width": 800,
        "height": 800
      },
      "large": {
        "webp": "/upload/variants/photo_large.webp",
        "jpeg": "/upload/variants/photo_large.jpg",
        "width": 1200,
        "height": 1200
      }
    }
  }
}
```

## Преимущества

1. **Автоматическая оптимизация**: Все изображения автоматически конвертируются в WebP и создаются варианты размеров
2. **Fallback поддержка**: Старые браузеры получают JPEG версию через `<picture>` элемент
3. **Быстрая загрузка**: Меньший размер файлов (WebP на 25-35% меньше JPEG)
4. **Адаптивность**: Автоматический выбор оптимального размера для viewport
5. **Lazy loading**: Изображения загружаются только при необходимости

## Настройки

В `app/Services/ImageService.php` можно настроить:

- **Размеры вариантов**: Константа `SIZES`
- **Качество WebP**: Константа `WEBP_QUALITY` (по умолчанию 85)
- **Качество JPEG**: Константа `JPEG_QUALITY` (по умолчанию 80)

## Примечания

- Обработка происходит синхронно при загрузке (может замедлить загрузку больших файлов)
- Для продакшена рекомендуется добавить очередь обработки (queue jobs)
- Существующие изображения не будут обработаны автоматически - нужно загрузить заново или создать команду для миграции





