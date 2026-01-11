import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Check, X, AlertCircle, Settings } from 'lucide-react';
import { toast } from 'sonner';
import { paymentSettingsAPI } from '@/api';

export function YooKassaSettings() {
  const [isLoading, setIsLoading] = useState(false);
  const [isTesting, setIsTesting] = useState(false);
  const [isLoadingData, setIsLoadingData] = useState(true);
  const [formData, setFormData] = useState({
    shop_id: '',
    secret_key: '',
    test_shop_id: '',
    test_secret_key: '',
    is_test_mode: true,
    is_enabled: false,
    webhook_url: '',
    description_template: '',
    merchant_name: '',
    auto_capture: true,
  });
  const [testResult, setTestResult] = useState<any>(null);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setIsLoadingData(true);
      const data = await paymentSettingsAPI.getYooKassa();
      
      if (data) {
        setFormData({
          shop_id: data.shop_id || '',
          secret_key: '', // Не показываем секретный ключ
          test_shop_id: data.test_shop_id || '',
          test_secret_key: '', // Не показываем секретный ключ
          is_test_mode: data.is_test_mode ?? true,
          is_enabled: data.is_enabled ?? false,
          webhook_url: data.webhook_url || '',
          description_template: data.description_template || '',
          merchant_name: data.merchant_name || '',
          auto_capture: data.auto_capture ?? true,
        });
      }
    } catch (error: any) {
      console.error('Error loading YooKassa settings:', error);
      toast.error('Ошибка при загрузке настроек');
    } finally {
      setIsLoadingData(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      await paymentSettingsAPI.updateYooKassa(formData);
      toast.success('Настройки успешно сохранены');
      await loadSettings(); // Перезагружаем настройки после сохранения
    } catch (error: any) {
      console.error('Error saving YooKassa settings:', error);
      toast.error(error?.response?.data?.message || 'Ошибка при сохранении настроек');
    } finally {
      setIsLoading(false);
    }
  };

  const handleTest = async () => {
    setIsTesting(true);
    setTestResult(null);

    try {
      const result = await paymentSettingsAPI.testYooKassa();
      setTestResult(result);
      
      if (result.success) {
        toast.success('Подключение успешно');
      } else {
        toast.error(result.message || 'Ошибка подключения');
      }
    } catch (error: any) {
      console.error('Error testing YooKassa connection:', error);
      setTestResult({
        success: false,
        message: error?.response?.data?.message || 'Ошибка при тестировании подключения',
      });
      toast.error('Ошибка при тестировании подключения');
    } finally {
      setIsTesting(false);
    }
  };

  if (isLoadingData) {
    return (
      <div className="p-4 lg:p-8 flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 lg:mb-8">
        <h1 className="text-2xl lg:text-3xl font-bold text-foreground">Настройки ЮKassa</h1>
        <p className="mt-1 text-muted-foreground">
          Настройка интеграции с платежной системой ЮKassa
        </p>
      </div>

      <form onSubmit={handleSubmit}>
        <div className="space-y-6">
          {/* Основные настройки */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Settings className="h-5 w-5" />
                Основные настройки
              </CardTitle>
              <CardDescription>
                Общие параметры интеграции с ЮKassa
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is_enabled">Включить интеграцию</Label>
                  <p className="text-sm text-muted-foreground">
                    Разрешить прием платежей через ЮKassa
                  </p>
                </div>
                <Switch
                  id="is_enabled"
                  checked={formData.is_enabled}
                  onCheckedChange={(checked) =>
                    setFormData({ ...formData, is_enabled: checked })
                  }
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is_test_mode">Тестовый режим</Label>
                  <p className="text-sm text-muted-foreground">
                    Использовать тестовые ключи для проверки
                  </p>
                </div>
                <Switch
                  id="is_test_mode"
                  checked={formData.is_test_mode}
                  onCheckedChange={(checked) =>
                    setFormData({ ...formData, is_test_mode: checked })
                  }
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="auto_capture">Автоматическое подтверждение</Label>
                  <p className="text-sm text-muted-foreground">
                    Автоматически подтверждать платежи
                  </p>
                </div>
                <Switch
                  id="auto_capture"
                  checked={formData.auto_capture}
                  onCheckedChange={(checked) =>
                    setFormData({ ...formData, auto_capture: checked })
                  }
                />
              </div>
            </CardContent>
          </Card>

          {/* Реальные ключи */}
          <Card>
            <CardHeader>
              <CardTitle>Реальные ключи (Production)</CardTitle>
              <CardDescription>
                Параметры для реальных платежей
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="shop_id">Shop ID *</Label>
                <Input
                  id="shop_id"
                  value={formData.shop_id}
                  onChange={(e) =>
                    setFormData({ ...formData, shop_id: e.target.value })
                  }
                  placeholder="Идентификатор магазина"
                  className="mt-1.5"
                  disabled={formData.is_test_mode}
                />
              </div>

              <div>
                <Label htmlFor="secret_key">Secret Key *</Label>
                <Input
                  id="secret_key"
                  type="password"
                  value={formData.secret_key}
                  onChange={(e) =>
                    setFormData({ ...formData, secret_key: e.target.value })
                  }
                  placeholder="Введите новый секретный ключ (оставьте пустым, чтобы не менять)"
                  className="mt-1.5"
                  disabled={formData.is_test_mode}
                />
                <p className="text-sm text-muted-foreground mt-1">
                  Оставьте пустым, если не хотите менять существующий ключ
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Тестовые ключи */}
          <Card>
            <CardHeader>
              <CardTitle>Тестовые ключи (Sandbox)</CardTitle>
              <CardDescription>
                Параметры для тестирования платежей
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="test_shop_id">Test Shop ID *</Label>
                <Input
                  id="test_shop_id"
                  value={formData.test_shop_id}
                  onChange={(e) =>
                    setFormData({ ...formData, test_shop_id: e.target.value })
                  }
                  placeholder="Идентификатор тестового магазина"
                  className="mt-1.5"
                  disabled={!formData.is_test_mode}
                />
              </div>

              <div>
                <Label htmlFor="test_secret_key">Test Secret Key *</Label>
                <Input
                  id="test_secret_key"
                  type="password"
                  value={formData.test_secret_key}
                  onChange={(e) =>
                    setFormData({ ...formData, test_secret_key: e.target.value })
                  }
                  placeholder="Введите новый тестовый секретный ключ (оставьте пустым, чтобы не менять)"
                  className="mt-1.5"
                  disabled={!formData.is_test_mode}
                />
                <p className="text-sm text-muted-foreground mt-1">
                  Оставьте пустым, если не хотите менять существующий ключ
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Дополнительные настройки */}
          <Card>
            <CardHeader>
              <CardTitle>Дополнительные настройки</CardTitle>
              <CardDescription>
                Необязательные параметры интеграции
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="webhook_url">Webhook URL</Label>
                <Input
                  id="webhook_url"
                  type="url"
                  value={formData.webhook_url}
                  onChange={(e) =>
                    setFormData({ ...formData, webhook_url: e.target.value })
                  }
                  placeholder="https://example.com/api/v1/payment-settings/yookassa/webhook"
                  className="mt-1.5"
                />
                <p className="text-sm text-muted-foreground mt-1">
                  URL для получения уведомлений о платежах
                </p>
              </div>

              <div>
                <Label htmlFor="description_template">Шаблон описания платежа</Label>
                <Input
                  id="description_template"
                  value={formData.description_template}
                  onChange={(e) =>
                    setFormData({ ...formData, description_template: e.target.value })
                  }
                  placeholder="Оплата заказа {order_id}"
                  className="mt-1.5"
                />
                <p className="text-sm text-muted-foreground mt-1">
                  Используйте {'{order_id}'} для подстановки номера заказа
                </p>
              </div>

              <div>
                <Label htmlFor="merchant_name">Название магазина для страницы оплаты</Label>
                <Input
                  id="merchant_name"
                  value={formData.merchant_name}
                  onChange={(e) =>
                    setFormData({ ...formData, merchant_name: e.target.value })
                  }
                  placeholder="ИП Ходжаян Артур Альбертович"
                  className="mt-1.5"
                />
                <p className="text-sm text-muted-foreground mt-1">
                  Название, которое будет отображаться на странице оплаты ЮKassa
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Тестирование */}
          <Card>
            <CardHeader>
              <CardTitle>Тестирование подключения</CardTitle>
              <CardDescription>
                Проверьте подключение к API ЮKassa
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <Button
                type="button"
                variant="outline"
                onClick={handleTest}
                disabled={isTesting || !formData.is_enabled}
              >
                {isTesting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Тестирование...
                  </>
                ) : (
                  'Тестировать подключение'
                )}
              </Button>

              {testResult && (
                <Alert variant={testResult.success ? 'default' : 'destructive'}>
                  <div className="flex items-center gap-2">
                    {testResult.success ? (
                      <Check className="h-4 w-4" />
                    ) : (
                      <X className="h-4 w-4" />
                    )}
                    <AlertDescription>
                      {testResult.message || (testResult.success ? 'Подключение успешно' : 'Ошибка подключения')}
                    </AlertDescription>
                  </div>
                </Alert>
              )}
            </CardContent>
          </Card>

          {/* Кнопка сохранения */}
          <div className="flex justify-end gap-4">
            <Button type="submit" disabled={isLoading}>
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Сохранение...
                </>
              ) : (
                'Сохранить настройки'
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}

