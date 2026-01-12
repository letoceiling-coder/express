import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Loader2, Check, Plus, Trash2, Image as ImageIcon } from 'lucide-react';
import { toast } from 'sonner';
import { aboutPageAPI } from '@/api';

export function AdminAbout() {
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingData, setIsLoadingData] = useState(true);
  const [formData, setFormData] = useState({
    title: '',
    phone: '',
    address: '',
    description: '',
    bullets: [] as string[],
    yandex_maps_url: '',
    cover_image_url: '',
  });

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setIsLoadingData(true);
      const data = await aboutPageAPI.get();

      if (data) {
        setFormData({
          title: data.title || '',
          phone: data.phone || '',
          address: data.address || '',
          description: data.description || '',
          bullets: data.bullets && Array.isArray(data.bullets) ? data.bullets : [],
          yandex_maps_url: data.yandex_maps_url || '',
          cover_image_url: data.cover_image_url || '',
        });
      }
    } catch (error: any) {
      console.error('Error loading about page data:', error);
      if (error?.response?.status !== 404) {
        toast.error('Ошибка при загрузке данных');
      }
    } finally {
      setIsLoadingData(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      await aboutPageAPI.update(formData);
      toast.success('Страница "О нас" успешно сохранена');
    } catch (error: any) {
      console.error('Error saving about page:', error);
      toast.error(error.response?.data?.message || 'Ошибка при сохранении данных');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAddBullet = () => {
    setFormData((prev) => ({
      ...prev,
      bullets: [...prev.bullets, ''],
    }));
  };

  const handleRemoveBullet = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      bullets: prev.bullets.filter((_, i) => i !== index),
    }));
  };

  const handleBulletChange = (index: number, value: string) => {
    setFormData((prev) => {
      const newBullets = [...prev.bullets];
      newBullets[index] = value;
      return { ...prev, bullets: newBullets };
    });
  };

  if (isLoadingData) {
    return (
      <div className="p-4 lg:p-8">
        <div className="flex items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="ml-4 text-muted-foreground">Загрузка данных...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6">
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-800 dark:text-slate-100">О нас</h1>
        <p className="mt-1 text-slate-500 dark:text-slate-400">
          Редактирование информации о компании
        </p>
      </div>

      <form onSubmit={handleSubmit}>
        <div className="space-y-6">
          {/* Basic Info */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Основная информация</CardTitle>
              <CardDescription>
                Название компании и контактные данные
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="title">Название компании *</Label>
                <Input
                  id="title"
                  placeholder="СВОЙ ХЛЕБ"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="mt-1"
                  required
                />
              </div>
              <div>
                <Label htmlFor="phone">Телефон</Label>
                <Input
                  id="phone"
                  placeholder="+7 982 682-43-68"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="mt-1"
                />
              </div>
              <div>
                <Label htmlFor="address">Адрес</Label>
                <Input
                  id="address"
                  placeholder="поселок Исток, ул. Главная, дом 15"
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  className="mt-1"
                />
              </div>
            </CardContent>
          </Card>

          {/* Cover Image */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Обложка</CardTitle>
              <CardDescription>
                Изображение для страницы "О нас". Загрузите изображение через Медиа-библиотеку и вставьте URL сюда.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="cover_image_url">URL обложки</Label>
                <div className="flex gap-2 mt-1">
                  <Input
                    id="cover_image_url"
                    placeholder="/upload/..."
                    value={formData.cover_image_url}
                    onChange={(e) => setFormData({ ...formData, cover_image_url: e.target.value })}
                    className="flex-1"
                  />
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => window.open('/admin/media', '_blank')}
                    className="whitespace-nowrap"
                  >
                    <ImageIcon className="h-4 w-4 mr-2" />
                    Медиа
                  </Button>
                </div>
                <p className="text-sm text-muted-foreground mt-1">
                  Выберите изображение из медиа-библиотеки или введите URL вручную
                </p>
                {formData.cover_image_url && (
                  <div className="mt-3">
                    <img
                      src={formData.cover_image_url}
                      alt="Preview"
                      className="h-32 w-full rounded-lg object-cover border border-border"
                      onError={(e) => {
                        (e.target as HTMLImageElement).style.display = 'none';
                      }}
                    />
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Description */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Описание</CardTitle>
              <CardDescription>
                Подробное описание компании
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div>
                <Label htmlFor="description">Описание</Label>
                <Textarea
                  id="description"
                  placeholder="Представляем вашему вниманию компанию..."
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  className="mt-1 min-h-[120px]"
                  rows={5}
                />
                <p className="mt-1 text-sm text-muted-foreground">
                  Поддерживается многострочный текст
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Bullets */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Список пунктов</CardTitle>
              <CardDescription>
                Важные моменты, которые будут отображаться в виде списка
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {formData.bullets.map((bullet, index) => (
                <div key={index} className="flex items-center gap-2">
                  <Textarea
                    value={bullet}
                    onChange={(e) => handleBulletChange(index, e.target.value)}
                    placeholder="Введите пункт списка..."
                    className="flex-1 min-h-[60px]"
                    rows={2}
                  />
                  {formData.bullets.length > 0 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      onClick={() => handleRemoveBullet(index)}
                      className="text-destructive hover:text-destructive flex-shrink-0"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              ))}
              <Button type="button" variant="outline" onClick={handleAddBullet} className="w-full">
                <Plus className="mr-2 h-4 w-4" />
                Добавить пункт
              </Button>
            </CardContent>
          </Card>

          {/* Yandex Maps */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Яндекс.Карты</CardTitle>
              <CardDescription>
                Ссылка на карту в Яндекс.Картах
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div>
                <Label htmlFor="yandex_maps_url">URL Яндекс.Карт</Label>
                <Input
                  id="yandex_maps_url"
                  type="url"
                  placeholder="https://yandex.ru/maps/..."
                  value={formData.yandex_maps_url}
                  onChange={(e) => setFormData({ ...formData, yandex_maps_url: e.target.value })}
                  className="mt-1"
                />
              </div>
            </CardContent>
          </Card>

          {/* Submit Button */}
          <div className="flex justify-end gap-4">
            <Button type="submit" disabled={isLoading} className="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600">
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Сохранение...
                </>
              ) : (
                <>
                  <Check className="mr-2 h-4 w-4" />
                  Сохранить
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}

