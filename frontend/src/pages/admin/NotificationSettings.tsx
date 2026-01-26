import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Bell, Plus, Trash2, Save } from 'lucide-react';
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
  const [settings, setSettings] = useState<NotificationSetting[]>([]);
  const [errors, setErrors] = useState<Record<string, any>>({});

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setIsLoadingData(true);
      const data = await notificationSettingsAPI.getAll();
      setSettings(data || []);
    } catch (error: any) {
      console.error('Error loading notification settings:', error);
      toast.error('Ошибка при загрузке настроек уведомлений');
    } finally {
      setIsLoadingData(false);
    }
  };

  const handleUpdate = async (event: string, updates: Partial<NotificationSetting>) => {
    try {
      setIsLoading(true);
      const updated = await notificationSettingsAPI.update(event, updates);
      
      setSettings(prev => prev.map(s => 
        s.event === event ? { ...s, ...updated } : s
      ));
      
      toast.success('Настройки успешно сохранены');
      setErrors({});
    } catch (error: any) {
      console.error('Error updating notification setting:', error);
      const errorData = error?.response?.data;
      if (errorData?.errors) {
        setErrors(prev => ({ ...prev, [event]: errorData.errors }));
      }
      toast.error(errorData?.message || 'Ошибка при сохранении настроек');
    } finally {
      setIsLoading(false);
    }
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

    handleUpdate(event, { buttons: newButtons });
  };

  const removeButton = (event: string, rowIndex: number, buttonIndex: number) => {
    const setting = settings.find(s => s.event === event);
    if (!setting || !setting.buttons) return;

    const newButtons = setting.buttons.map((row, idx) => 
      idx === rowIndex ? row.filter((_, btnIdx) => btnIdx !== buttonIndex) : row
    ).filter(row => row.length > 0);

    handleUpdate(event, { buttons: newButtons.length > 0 ? newButtons : null });
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

    handleUpdate(event, { buttons: newButtons });
  };

  const addButtonRow = (event: string) => {
    const setting = settings.find(s => s.event === event);
    if (!setting) return;

    const newButtons = setting.buttons ? [...setting.buttons, []] : [[]];
    handleUpdate(event, { buttons: newButtons });
  };

  const removeButtonRow = (event: string, rowIndex: number) => {
    const setting = settings.find(s => s.event === event);
    if (!setting || !setting.buttons) return;

    const newButtons = setting.buttons.filter((_, idx) => idx !== rowIndex);
    handleUpdate(event, { buttons: newButtons.length > 0 ? newButtons : null });
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
        <h1 className="text-2xl lg:text-3xl font-bold text-foreground flex items-center gap-2">
          <Bell className="h-6 w-6" />
          Настройки уведомлений заказов
        </h1>
        <p className="mt-1 text-muted-foreground">
          Управление уведомлениями при создании и обработке заказов
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
                        handleUpdate(setting.event, { enabled: checked })
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
                      handleUpdate(setting.event, { message_template: e.target.value || null })
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
                        handleUpdate(setting.event, { support_chat_id: e.target.value || null })
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
    </div>
  );
}
