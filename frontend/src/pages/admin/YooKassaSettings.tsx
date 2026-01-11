import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Check, X, AlertCircle, Settings, CheckCircle2, XCircle } from 'lucide-react';
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
      
      console.log('Settings loaded from API:', data);
      
      if (data) {
        // Сохраняем текущие секретные ключи, если они были введены
        setFormData(prev => {
          const hasCurrentSecretKey = prev.secret_key && prev.secret_key.length > 0;
          const hasCurrentTestSecretKey = prev.test_secret_key && prev.test_secret_key.length > 0;
          
          const newFormData = {
            shop_id: data.shop_id ?? prev.shop_id ?? '',
            secret_key: hasCurrentSecretKey ? prev.secret_key : '', // Сохраняем только если был введен
            test_shop_id: data.test_shop_id ?? prev.test_shop_id ?? '',
            test_secret_key: hasCurrentTestSecretKey ? prev.test_secret_key : '', // Сохраняем только если был введен
            is_test_mode: data.is_test_mode !== undefined ? data.is_test_mode : (prev.is_test_mode ?? true),
            is_enabled: data.is_enabled !== undefined ? data.is_enabled : (prev.is_enabled ?? false),
            webhook_url: data.webhook_url ?? prev.webhook_url ?? '',
            description_template: data.description_template ?? prev.description_template ?? '',
            merchant_name: data.merchant_name ?? prev.merchant_name ?? '',
            auto_capture: data.auto_capture !== undefined ? data.auto_capture : (prev.auto_capture ?? true),
          };
          
          console.log('Form data updated:', newFormData);
          return newFormData;
        });
      } else {
        console.log('No settings found, keeping current form data');
        // Если данных нет, не сбрасываем форму - оставляем текущие значения
        // Это позволяет пользователю продолжать заполнять форму
      }
    } catch (error: any) {
      console.error('Error loading YooKassa settings:', error);
      // Не показываем ошибку, если настройки еще не созданы (это нормально)
      if (error?.response?.status !== 404 && error?.response?.status !== 200) {
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
      // Сохраняем текущие значения перед отправкой
      const currentFormData = { ...formData };
      const savedSecretKey = currentFormData.secret_key;
      const savedTestSecretKey = currentFormData.test_secret_key;
      
      console.log('Saving settings with formData:', {
        ...currentFormData,
        secret_key: savedSecretKey ? '***hidden***' : '',
        test_secret_key: savedTestSecretKey ? '***hidden***' : '',
      });
      
      const response = await paymentSettingsAPI.updateYooKassa(formData);
      toast.success('Настройки успешно сохранены');
      
      // updateYooKassa возвращает response.data, где response от apiRequest - это { data: {...}, message: '...' }
      // Так что response уже является объектом с настройками (без обертки data)
      const savedData = response;
      
      console.log('Settings saved, response from API:', savedData);
      
      // Всегда обновляем форму - либо из ответа API, либо перезагружаем
      if (savedData && typeof savedData === 'object' && (savedData.id || savedData.shop_id !== undefined || savedData.test_shop_id !== undefined || savedData.provider === 'yookassa')) {
        // Обновляем форму с сохраненными данными из ответа API
        const updatedFormData = {
          shop_id: savedData.shop_id ?? currentFormData.shop_id ?? '',
          secret_key: savedSecretKey || '', // Сохраняем введенный ключ
          test_shop_id: savedData.test_shop_id ?? currentFormData.test_shop_id ?? '',
          test_secret_key: savedTestSecretKey || '', // Сохраняем введенный ключ
          is_test_mode: savedData.is_test_mode !== undefined ? savedData.is_test_mode : (currentFormData.is_test_mode ?? true),
          is_enabled: savedData.is_enabled !== undefined ? savedData.is_enabled : (currentFormData.is_enabled ?? false),
          webhook_url: savedData.webhook_url ?? currentFormData.webhook_url ?? '',
          description_template: savedData.description_template ?? currentFormData.description_template ?? '',
          merchant_name: savedData.merchant_name ?? currentFormData.merchant_name ?? '',
          auto_capture: savedData.auto_capture !== undefined ? savedData.auto_capture : (currentFormData.auto_capture ?? true),
        };
        
        console.log('Updating form with saved data:', {
          ...updatedFormData,
          secret_key: updatedFormData.secret_key ? '***hidden***' : '',
          test_secret_key: updatedFormData.test_secret_key ? '***hidden***' : '',
        });
        
        setFormData(updatedFormData);
      } else {
        // Если ответ не содержит данных, перезагружаем настройки
        console.log('Response does not contain expected data, reloading settings...');
        
        // Небольшая задержка для гарантии сохранения на сервере
        setTimeout(async () => {
          await loadSettings();
          
          // Восстанавливаем введенные ключи после загрузки
          setFormData(prev => ({
            ...prev,
            secret_key: savedSecretKey || prev.secret_key || '',
            test_secret_key: savedTestSecretKey || prev.test_secret_key || '',
          }));
        }, 300);
      }
    } catch (error: any) {
      console.error('Error saving YooKassa settings:', error);
      const errorMessage = error?.response?.data?.message || error?.message || 'Ошибка при сохранении настроек';
      toast.error(errorMessage);
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

              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Label htmlFor="is_test_mode">Режим работы</Label>
                    <p className="text-sm text-muted-foreground">
                      {formData.is_test_mode 
                        ? 'Тестовый режим — используются тестовые ключи'
                        : 'Рабочий режим — используются реальные ключи для приема платежей'
                      }
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
                {formData.is_test_mode ? (
                  <div className="px-3 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
                    <p className="text-xs font-medium text-yellow-700 dark:text-yellow-400">
                      ⚠️ Тестовый режим активен. Платежи будут тестовыми.
                    </p>
                  </div>
                ) : (
                  <div className="px-3 py-2 bg-green-500/10 border border-green-500/20 rounded-lg">
                    <p className="text-xs font-medium text-green-700 dark:text-green-400">
                      ✓ Рабочий режим активен. Платежи будут реальными.
                    </p>
                  </div>
                )}
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
                Проверьте подключение к API ЮKassa ({formData.is_test_mode ? 'тестовый режим' : 'рабочий режим'})
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="rounded-lg border border-border bg-card p-4">
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Режим:</span>
                    <span className="font-medium text-foreground">
                      {formData.is_test_mode ? (
                        <span className="text-yellow-600 dark:text-yellow-400">Тестовый (Sandbox)</span>
                      ) : (
                        <span className="text-green-600 dark:text-green-400">Рабочий (Production)</span>
                      )}
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Shop ID:</span>
                    <span className="font-medium text-foreground">
                      {formData.is_test_mode 
                        ? (formData.test_shop_id || 'Не указан')
                        : (formData.shop_id || 'Не указан')
                      }
                    </span>
                  </div>
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">Secret Key:</span>
                    <span className="font-medium text-foreground">
                      {(formData.is_test_mode ? formData.test_secret_key : formData.secret_key) 
                        ? '✓ Указан' 
                        : '✗ Не указан'
                      }
                    </span>
                  </div>
                </div>
              </div>

              <Button
                type="button"
                variant="outline"
                onClick={handleTest}
                disabled={isTesting || !formData.is_enabled}
                className="w-full"
              >
                {isTesting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Тестирование подключения...
                  </>
                ) : (
                  <>
                    <Settings className="mr-2 h-4 w-4" />
                    Тестировать подключение ({formData.is_test_mode ? 'тестовый режим' : 'рабочий режим'})
                  </>
                )}
              </Button>

              {testResult && (
                <Alert variant={testResult.success ? 'default' : 'destructive'}>
                  <div className="flex items-start gap-2">
                    {testResult.success ? (
                      <CheckCircle2 className="h-4 w-4 mt-0.5" />
                    ) : (
                      <XCircle className="h-4 w-4 mt-0.5" />
                    )}
                    <div className="flex-1">
                      <AlertDescription className="font-medium">
                        {testResult.success ? 'Подключение успешно!' : 'Ошибка подключения'}
                      </AlertDescription>
                      <p className="text-sm mt-1">
                        {testResult.message || (testResult.success 
                          ? `API ЮKassa доступен. Режим: ${formData.is_test_mode ? 'тестовый' : 'рабочий'}` 
                          : 'Проверьте правильность Shop ID и Secret Key'
                        )}
                      </p>
                      {testResult.success && formData.is_test_mode && (
                        <p className="text-xs mt-2 text-muted-foreground">
                          💡 Подключение работает в тестовом режиме. Для переключения на рабочий режим отключите "Тестовый режим" выше и укажите реальные ключи.
                        </p>
                      )}
                      {testResult.success && !formData.is_test_mode && (
                        <p className="text-xs mt-2 text-muted-foreground">
                          ✅ Подключение работает в рабочем режиме. Платежи будут реальными.
                        </p>
                      )}
                    </div>
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

