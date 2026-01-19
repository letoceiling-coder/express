import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Check, X, Plus, Trash2, MapPin } from 'lucide-react';
import { toast } from 'sonner';
import { deliverySettingsAPI } from '@/api';

interface DeliveryZone {
  max_distance: number | null;
  cost: number;
}

export function DeliverySettings() {
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingData, setIsLoadingData] = useState(true);
  const [formData, setFormData] = useState({
    yandex_geocoder_api_key: '',
    origin_address: '',
    origin_latitude: '',
    origin_longitude: '',
    delivery_zones: [
      { max_distance: 3, cost: 300 },
      { max_distance: 7, cost: 500 },
      { max_distance: 12, cost: 800 },
      { max_distance: null, cost: 1000 },
    ] as DeliveryZone[],
    is_enabled: false,
    min_delivery_order_total_rub: 3000,
    delivery_min_lead_hours: 3,
  });

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setIsLoadingData(true);
      const data = await deliverySettingsAPI.getSettings();

      if (data) {
        setFormData((prev) => {
          const hasCurrentApiKey = prev.yandex_geocoder_api_key && prev.yandex_geocoder_api_key.length > 0;

          return {
            yandex_geocoder_api_key: hasCurrentApiKey ? prev.yandex_geocoder_api_key : '',
            origin_address: data.origin_address ?? prev.origin_address ?? '',
            origin_latitude: data.origin_latitude ? String(data.origin_latitude) : prev.origin_latitude ?? '',
            origin_longitude: data.origin_longitude ? String(data.origin_longitude) : prev.origin_longitude ?? '',
            delivery_zones: data.delivery_zones && Array.isArray(data.delivery_zones) && data.delivery_zones.length > 0
              ? data.delivery_zones
              : prev.delivery_zones,
            is_enabled: data.is_enabled !== undefined ? data.is_enabled : prev.is_enabled,
            min_delivery_order_total_rub: data.min_delivery_order_total_rub !== undefined ? data.min_delivery_order_total_rub : (prev.min_delivery_order_total_rub ?? 3000),
            delivery_min_lead_hours: data.delivery_min_lead_hours !== undefined ? data.delivery_min_lead_hours : (prev.delivery_min_lead_hours ?? 3),
          };
        });
      }
    } catch (error: any) {
      console.error('Error loading delivery settings:', error);
      if (error?.response?.status !== 404) {
        toast.error('Ошибка при загрузке настроек');
      }
    } finally {
      setIsLoadingData(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const submitData = {
        ...formData,
        origin_latitude: formData.origin_latitude ? parseFloat(formData.origin_latitude) : null,
        origin_longitude: formData.origin_longitude ? parseFloat(formData.origin_longitude) : null,
        // Удаляем пустой API ключ из отправки (чтобы не перезаписывать существующий)
        yandex_geocoder_api_key: formData.yandex_geocoder_api_key || undefined,
      };

      const response = await deliverySettingsAPI.updateSettings(submitData);
      
      // Обновляем координаты в форме из ответа сервера (они могли быть перегеокодированы)
      const updatedData = response?.data || response;
      if (updatedData) {
        setFormData((prev) => ({
          ...prev,
          origin_latitude: updatedData.origin_latitude ? String(updatedData.origin_latitude) : prev.origin_latitude,
          origin_longitude: updatedData.origin_longitude ? String(updatedData.origin_longitude) : prev.origin_longitude,
          origin_address: updatedData.origin_address || prev.origin_address,
        }));
      }
      
      toast.success('Настройки доставки успешно сохранены');
    } catch (error: any) {
      console.error('Error saving delivery settings:', error);
      toast.error(error.response?.data?.message || 'Ошибка при сохранении настроек');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAddZone = () => {
    setFormData((prev) => ({
      ...prev,
      delivery_zones: [...prev.delivery_zones, { max_distance: null, cost: 0 }],
    }));
  };

  const handleRemoveZone = (index: number) => {
    setFormData((prev) => ({
      ...prev,
      delivery_zones: prev.delivery_zones.filter((_, i) => i !== index),
    }));
  };

  const handleZoneChange = (index: number, field: keyof DeliveryZone, value: string | number | null) => {
    setFormData((prev) => {
      const newZones = [...prev.delivery_zones];
      newZones[index] = { ...newZones[index], [field]: value };
      return { ...prev, delivery_zones: newZones };
    });
  };

  if (isLoadingData) {
    return (
      <div className="p-4 lg:p-8">
        <div className="flex items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
          <p className="ml-4 text-muted-foreground">Загрузка настроек...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6">
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-800 dark:text-slate-100">Настройки доставки</h1>
        <p className="mt-1 text-slate-500 dark:text-slate-400">
          Настройка расчета стоимости доставки по расстоянию
        </p>
      </div>

      <form onSubmit={handleSubmit}>
        <div className="space-y-6">
          {/* API Settings */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">API Яндекс.Геокодер</CardTitle>
              <CardDescription>
                API ключ для геокодинга адресов и расчета расстояния
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="yandex_geocoder_api_key">API ключ</Label>
                <Input
                  id="yandex_geocoder_api_key"
                  type="password"
                  placeholder="Введите API ключ Яндекс.Геокодера"
                  value={formData.yandex_geocoder_api_key}
                  onChange={(e) => setFormData({ ...formData, yandex_geocoder_api_key: e.target.value })}
                  className="mt-1"
                />
                <p className="mt-1 text-sm text-muted-foreground">
                  Оставьте пустым, чтобы не изменять существующий ключ
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Origin Point */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <MapPin className="h-5 w-5" />
                Точка начала доставки
              </CardTitle>
              <CardDescription>
                Адрес и координаты точки, от которой рассчитывается расстояние доставки
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="origin_address">Адрес</Label>
                <Input
                  id="origin_address"
                  placeholder="г. Екатеринбург, ул. Ленина, 1"
                  value={formData.origin_address}
                  onChange={(e) => setFormData({ ...formData, origin_address: e.target.value })}
                  className="mt-1"
                />
                <p className="mt-1 text-sm text-muted-foreground">
                  Адрес будет автоматически геокодирован при сохранении
                </p>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="origin_latitude">Широта</Label>
                  <Input
                    id="origin_latitude"
                    type="number"
                    step="any"
                    placeholder="56.8431"
                    value={formData.origin_latitude}
                    onChange={(e) => setFormData({ ...formData, origin_latitude: e.target.value })}
                    className="mt-1"
                  />
                </div>
                <div>
                  <Label htmlFor="origin_longitude">Долгота</Label>
                  <Input
                    id="origin_longitude"
                    type="number"
                    step="any"
                    placeholder="60.6454"
                    value={formData.origin_longitude}
                    onChange={(e) => setFormData({ ...formData, origin_longitude: e.target.value })}
                    className="mt-1"
                  />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Delivery Zones */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Зоны доставки</CardTitle>
              <CardDescription>
                Настройте зоны доставки по расстоянию. Последняя зона с пустым расстоянием будет применяться для всех адресов дальше предыдущих зон.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {formData.delivery_zones.map((zone, index) => (
                <div key={index} className="flex items-end gap-4 p-4 border border-border rounded-lg">
                  <div className="flex-1">
                    <Label>Максимальное расстояние (км)</Label>
                    <Input
                      type="number"
                      step="0.1"
                      min="0"
                      placeholder={index === formData.delivery_zones.length - 1 ? 'Свыше предыдущей зоны' : '3'}
                      value={zone.max_distance ?? ''}
                      onChange={(e) =>
                        handleZoneChange(
                          index,
                          'max_distance',
                          e.target.value === '' ? null : parseFloat(e.target.value)
                        )
                      }
                      className="mt-1"
                      disabled={index === formData.delivery_zones.length - 1 && zone.max_distance === null}
                    />
                    {index === formData.delivery_zones.length - 1 && (
                      <p className="mt-1 text-xs text-muted-foreground">Оставьте пустым для последней зоны</p>
                    )}
                  </div>
                  <div className="flex-1">
                    <Label>Стоимость (₽)</Label>
                    <Input
                      type="number"
                      step="1"
                      min="0"
                      placeholder="300"
                      value={zone.cost}
                      onChange={(e) => handleZoneChange(index, 'cost', parseFloat(e.target.value) || 0)}
                      className="mt-1"
                    />
                  </div>
                  {formData.delivery_zones.length > 1 && (
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      onClick={() => handleRemoveZone(index)}
                      className="text-destructive hover:text-destructive"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              ))}
              <Button type="button" variant="outline" onClick={handleAddZone} className="w-full">
                <Plus className="mr-2 h-4 w-4" />
                Добавить зону
              </Button>
            </CardContent>
          </Card>

          {/* Enable/Disable */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Включить систему расчета доставки</CardTitle>
              <CardDescription>
                Включите эту опцию, чтобы система автоматически рассчитывала стоимость доставки
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center space-x-2">
                <Switch
                  id="is_enabled"
                  checked={formData.is_enabled}
                  onCheckedChange={(checked) => setFormData({ ...formData, is_enabled: checked })}
                />
                <Label htmlFor="is_enabled" className="cursor-pointer">
                  {formData.is_enabled ? 'Включено' : 'Выключено'}
                </Label>
              </div>
            </CardContent>
          </Card>

          {/* Minimum Delivery Order Total & Lead Time */}
          <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
            <CardHeader>
              <CardTitle className="text-slate-800 dark:text-slate-100">Ограничения для доставки</CardTitle>
              <CardDescription>
                Минимальная сумма заказа и время подготовки для доставки курьером
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="min_delivery_order_total_rub">Минимальный заказ для доставки (₽) *</Label>
                <Input
                  id="min_delivery_order_total_rub"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="3000"
                  value={formData.min_delivery_order_total_rub}
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    min_delivery_order_total_rub: parseFloat(e.target.value) || 0 
                  })}
                  className="mt-1"
                  required
                />
                <p className="mt-1 text-sm text-muted-foreground">
                  Минимальная сумма заказа для оформления доставки курьером. На самовывоз не влияет.
                </p>
              </div>
              
              <div>
                <Label htmlFor="delivery_min_lead_hours">Минимальное время подготовки (часы) *</Label>
                <Input
                  id="delivery_min_lead_hours"
                  type="number"
                  min="0"
                  max="72"
                  step="1"
                  placeholder="3"
                  value={formData.delivery_min_lead_hours}
                  onChange={(e) => setFormData({ 
                    ...formData, 
                    delivery_min_lead_hours: parseInt(e.target.value) || 0 
                  })}
                  className="mt-1"
                  required
                />
                <p className="mt-1 text-sm text-muted-foreground">
                  Минимальное количество часов от текущего момента до доступного времени доставки. Например, при значении 3 и текущем времени 10:00, самый ранний слот будет 13:00.
                </p>
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
                  Сохранить настройки
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}

