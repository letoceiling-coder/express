import { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Bell, Plus, Trash2, Save, CheckCircle2 } from 'lucide-react';
import { toast } from 'sonner';
import { notificationSettingsAPI } from '@/api';

interface ButtonConfig {
  text: string;
  type: 'callback' | 'open_chat' | 'open_url';
  value: string;
}

interface NotificationSetting {
  id: number;
  event: string;
  enabled: boolean;
  message_template: string | null;
  buttons: ButtonConfig[][] | null;
  support_chat_id: string | null;
}

const EVENT_LABELS: Record<string, { title: string; description: string }> = {
  order_created_client: {
    title: 'Уведомление клиенту при создании заказа',
    description: 'Отправляется сразу после создания заказа (статус: new)',
  },
  order_created_admin: {
    title: 'Уведомление администратору при создании заказа',
    description: 'Отправляется администраторам при создании нового заказа',
  },
  order_accepted_client: {
    title: 'Уведомление клиенту при принятии заказа',
    description: 'Отправляется клиенту после того, как администратор принял заказ',
  },
};

export function NotificationSettings() {
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingData, setIsLoadingData] = useState(true);
  const [originalSettings, setOriginalSettings] = useState<NotificationSetting[]>([]);
  const [settings, setSettings] = useState<NotificationSetting[]>([]);
  const [errors, setErrors] = useState<Record<string, any>>({});
  const [hasChanges, setHasChanges] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const formRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    loadSettings();
  }, []);

  // Отслеживание изменений
  useEffect(() => {
    const changed = JSON.stringify(originalSettings) !== JSON.stringify(settings);
    setHasChanges(changed);
    if (changed) {
      setSaveSuccess(false);
    }
  }, [settings, originalSettings]);

  const loadSettings = async () => {
    try {
      setIsLoadingData(true);
      const data = await notificationSettingsAPI.getAll();
      const settingsData = data || [];
      setSettings(settingsData);
      setOriginalSettings(JSON.parse(JSON.stringify(settingsData))); // Deep copy
      setHasChanges(false);
      setSaveSuccess(false);
    } catch (error: any) {
      console.error('Error loading notification settings:', error);
      toast.error('Ошибка при загрузке настроек уведомлений');
    } finally {
      setIsLoadingData(false);
    }
  };

  const handleSaveAll = async () => {
    if (!hasChanges) {
      return;
    }

    try {
      setIsLoading(true);
      setErrors({});
      
      // Формируем payload для единого сохранения
      const notificationsPayload: Record<string, {
        enabled: boolean;
        message_template: string | null;
        buttons: ButtonConfig[][] | null;
        support_chat_id: string | null;
      }> = {};

      settings.forEach((setting) => {
        notificationsPayload[setting.event] = {
          enabled: setting.enabled,
          message_template: setting.message_template,
          buttons: setting.buttons,
          support_chat_id: setting.support_chat_id,
        };
      });

      // Сохраняем все настройки одним запросом
      await notificationSettingsAPI.updateAll(notificationsPayload);
      
      // Обновляем оригинальные данные
      setOriginalSettings(JSON.parse(JSON.stringify(settings)));
      setHasChanges(false);
      setSaveSuccess(true);
      
      toast.success('Все настройки успешно сохранены', {
        icon: <CheckCircle2 className="h-5 w-5 text-green-500" />,
        duration: 3000,
      });

      // Скрываем success state через 3 секунды
      setTimeout(() => setSaveSuccess(false), 3000);
    } catch (error: any) {
      console.error('Error saving notification settings:', error);
      const errorData = error?.response?.data;
      if (errorData?.errors) {
        // Обрабатываем ошибки валидации по событиям
        const eventErrors: Record<string, any> = {};
        Object.keys(errorData.errors).forEach((key) => {
          const match = key.match(/notifications\.(.+?)\./);
          if (match) {
            const event = match[1];
            if (!eventErrors[event]) {
              eventErrors[event] = {};
            }
            eventErrors[event][key.replace(`notifications.${event}.`, '')] = errorData.errors[key];
          }
        });
        setErrors(eventErrors);
      }
      toast.error(errorData?.message || 'Ошибка при сохранении настроек');
    } finally {
      setIsLoading(false);
    }
  };

  // Локальные обновления без сохранения
  const updateSettingLocal = (event: string, updates: Partial<NotificationSetting>) => {
    setSettings(prev => prev.map(s => 
      s.event === event ? { ...s, ...updates } : s
    ));
  };

  const addButton = (event: string, rowIndex: number) => {
    const setting = settings.find(s => s.event === event);
    if (!setting) return;

    const newButtons = setting.buttons ? [...setting.buttons] : [];
    if (!newButtons[rowIndex]) {
      newButtons[rowIndex] = [];
    }
    newButtons[rowIndex].push({
      text: '',
      type: 'callback',
      value: '',
    });

    updateSettingLocal(event, { buttons: newButtons });
  };

  const removeButton = (event: string, rowIndex: number, buttonIndex: number) => {
    const setting = settings.find(s => s.event === event);
    if (!setting || !setting.buttons) return;

    const newButtons = setting.buttons.map((row, idx) => 
      idx === rowIndex ? row.filter((_, btnIdx) => btnIdx !== buttonIndex) : row
    ).filter(row => row.length > 0);

    updateSettingLocal(event, { buttons: newButtons.length > 0 ? newButtons : null });
  };

  const updateButton = (event: string, rowIndex: number, buttonIndex: number, updates: Partial<ButtonConfig>) => {
    const setting = settings.find(s => s.event === event);
    if (!setting || !setting.buttons) return;

    const newButtons = setting.buttons.map((row, idx) => 
      idx === rowIndex 
        ? row.map((btn, btnIdx) => 
            btnIdx === buttonIndex ? { ...btn, ...updates } : btn
          )
        : row
    );

    updateSettingLocal(event, { buttons: newButtons });
  };

  const addButtonRow = (event: string) => {
    const setting = settings.find(s => s.event === event);
    if (!setting) return;

    const newButtons = setting.buttons ? [...setting.buttons, []] : [[]];
    updateSettingLocal(event, { buttons: newButtons });
  };

  const removeButtonRow = (event: string, rowIndex: number) => {
    const setting = settings.find(s => s.event === event);
    if (!setting || !setting.buttons) return;

    const newButtons = setting.buttons.filter((_, idx) => idx !== rowIndex);
    updateSettingLocal(event, { buttons: newButtons.length > 0 ? newButtons : null });
  };

  if (isLoadingData) {
    return (
      <div className="p-4 lg:p-8 flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8 pb-24" ref={formRef}>
      <div className="mb-6 lg:mb-8">
        <h1 className="text-2xl lg:text-3xl font-bold text-foreground flex items-center gap-2">
          <Bell className="h-6 w-6" />
          Центр управления уведомлениями
        </h1>
        <p className="mt-1 text-muted-foreground">
          Настройка уведомлений при создании и обработке заказов. Все изменения применяются после нажатия кнопки «Сохранить изменения».
        </p>
      </div>

      <div className="space-y-6">
        {settings.map((setting) => {
          const eventInfo = EVENT_LABELS[setting.event] || {
            title: setting.event,
            description: '',
          };

          return (
            <Card key={setting.event}>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>{eventInfo.title}</CardTitle>
                    <CardDescription className="mt-1">
                      {eventInfo.description}
                    </CardDescription>
                  </div>
                  <div className="flex items-center gap-2">
                    <Label htmlFor={`enabled-${setting.event}`} className="text-sm">
                      Включено
                    </Label>
                    <Switch
                      id={`enabled-${setting.event}`}
                      checked={setting.enabled}
                      onCheckedChange={(checked) => 
                        updateSettingLocal(setting.event, { enabled: checked })
                      }
                    />
                  </div>
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Шаблон сообщения */}
                <div>
                  <Label htmlFor={`template-${setting.event}`}>
                    Шаблон сообщения
                  </Label>
                  <Textarea
                    id={`template-${setting.event}`}
                    value={setting.message_template || ''}
                    onChange={(e) => 
                      updateSettingLocal(setting.event, { message_template: e.target.value || null })
                    }
                    placeholder="Используйте {order_id} для подстановки номера заказа"
                    className="mt-1 min-h-[100px]"
                  />
                  <p className="text-xs text-muted-foreground mt-1">
                    Плейсхолдеры: {'{order_id}'}, {'{amount}'} и другие
                  </p>
                </div>

                {/* Support Chat ID (только для order_accepted_client) */}
                {setting.event === 'order_accepted_client' && (
                  <div>
                    <Label htmlFor={`support-${setting.event}`}>
                      ID чата поддержки (Telegram ID администратора или username)
                    </Label>
                    <Input
                      id={`support-${setting.event}`}
                      value={setting.support_chat_id || ''}
                      onChange={(e) => 
                        updateSettingLocal(setting.event, { support_chat_id: e.target.value || null })
                      }
                      placeholder="Например: 123456789 или @username"
                      className="mt-1"
                    />
                    <p className="text-xs text-muted-foreground mt-1">
                      Если не указан, будет использован первый администратор бота
                    </p>
                  </div>
                )}

                {/* Кнопки */}
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <Label>Кнопки</Label>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => addButtonRow(setting.event)}
                    >
                      <Plus className="h-4 w-4 mr-1" />
                      Добавить строку
                    </Button>
                  </div>

                  {setting.buttons && setting.buttons.length > 0 ? (
                    <div className="space-y-3">
                      {setting.buttons.map((row, rowIndex) => (
                        <div key={rowIndex} className="border rounded-lg p-4 space-y-3">
                          <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-medium">Строка {rowIndex + 1}</span>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => removeButtonRow(setting.event, rowIndex)}
                            >
                              <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                          </div>
                          <div className="space-y-2">
                            {row.map((button, buttonIndex) => (
                              <div key={buttonIndex} className="flex gap-2 items-end">
                                <div className="flex-1">
                                  <Label className="text-xs">Текст кнопки</Label>
                                  <Input
                                    value={button.text}
                                    onChange={(e) => 
                                      updateButton(setting.event, rowIndex, buttonIndex, { text: e.target.value })
                                    }
                                    placeholder="Текст кнопки"
                                    className="mt-1"
                                  />
                                </div>
                                <div className="w-32">
                                  <Label className="text-xs">Тип</Label>
                                  <select
                                    value={button.type}
                                    onChange={(e) => 
                                      updateButton(setting.event, rowIndex, buttonIndex, { 
                                        type: e.target.value as ButtonConfig['type'] 
                                      })
                                    }
                                    className="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground mt-1"
                                  >
                                    <option value="callback">Callback</option>
                                    <option value="open_chat">Открыть чат</option>
                                    <option value="open_url">Открыть URL</option>
                                  </select>
                                </div>
                                <div className="flex-1">
                                  <Label className="text-xs">
                                    {button.type === 'callback' ? 'Callback Data' : 
                                     button.type === 'open_chat' ? 'Значение (support)' : 
                                     'URL'}
                                  </Label>
                                  <Input
                                    value={button.value}
                                    onChange={(e) => 
                                      updateButton(setting.event, rowIndex, buttonIndex, { value: e.target.value })
                                    }
                                    placeholder={
                                      button.type === 'callback' ? 'order_admin_action:{order_id}:accept' :
                                      button.type === 'open_chat' ? 'support' :
                                      'https://example.com'
                                    }
                                    className="mt-1"
                                  />
                                </div>
                                <Button
                                  type="button"
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => removeButton(setting.event, rowIndex, buttonIndex)}
                                >
                                  <Trash2 className="h-4 w-4 text-destructive" />
                                </Button>
                              </div>
                            ))}
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              onClick={() => addButton(setting.event, rowIndex)}
                              className="w-full"
                            >
                              <Plus className="h-4 w-4 mr-1" />
                              Добавить кнопку
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="border border-dashed rounded-lg p-4 text-center">
                      <p className="text-sm text-muted-foreground mb-2">
                        Кнопки не настроены
                      </p>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => addButtonRow(setting.event)}
                      >
                        <Plus className="h-4 w-4 mr-1" />
                        Добавить кнопки
                      </Button>
                    </div>
                  )}

                  {errors[setting.event] && (
                    <Alert variant="destructive" className="mt-2">
                      <AlertDescription>
                        {Object.values(errors[setting.event]).flat().join(', ')}
                      </AlertDescription>
                    </Alert>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Sticky Save Button */}
      <div className="fixed bottom-0 left-0 right-0 lg:left-64 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 shadow-lg z-40 p-4 safe-area-inset-bottom">
        <div className="max-w-7xl mx-auto flex items-center justify-between gap-4">
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            {hasChanges && (
              <span className="text-amber-600 dark:text-amber-400">
                Есть несохранённые изменения
              </span>
            )}
            {!hasChanges && !saveSuccess && (
              <span>Все изменения сохранены</span>
            )}
            {saveSuccess && (
              <span className="text-green-600 dark:text-green-400 flex items-center gap-2">
                <CheckCircle2 className="h-4 w-4" />
                Настройки успешно сохранены
              </span>
            )}
          </div>
          <div className="flex items-center gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={loadSettings}
              disabled={isLoading || !hasChanges}
            >
              Отменить
            </Button>
            <Button
              type="button"
              onClick={handleSaveAll}
              disabled={isLoading || !hasChanges}
              className="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white min-w-[180px]"
            >
              {isLoading ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Сохранение...
                </>
              ) : saveSuccess ? (
                <>
                  <CheckCircle2 className="h-4 w-4 mr-2" />
                  Сохранено
                </>
              ) : (
                <>
                  <Save className="h-4 w-4 mr-2" />
                  Сохранить изменения
                </>
              )}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}
