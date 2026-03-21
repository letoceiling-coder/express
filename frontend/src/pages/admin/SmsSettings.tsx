import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, MessageSquare } from 'lucide-react';
import { toast } from 'sonner';
import { smsSettingsAPI } from '@/api';

export function SmsSettings() {
  const [isLoading, setIsLoading] = useState(false);
  const [isLoadingData, setIsLoadingData] = useState(true);
  const [formData, setFormData] = useState({
    login: '',
    password: '',
    sender: 'INFO',
    is_enabled: false,
  });

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      setIsLoadingData(true);
      const data = await smsSettingsAPI.getSettings();

      if (data) {
        setFormData((prev) => ({
          login: data.login ?? prev.login ?? '',
          password: '',
          sender: data.sender ?? prev.sender ?? 'INFO',
          is_enabled: data.is_enabled ?? prev.is_enabled ?? false,
        }));
      }
    } catch (error: any) {
      console.error('Error loading SMS settings:', error);
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
      const submitData: Record<string, any> = {
        login: formData.login || undefined,
        sender: formData.sender || undefined,
        is_enabled: formData.is_enabled,
      };
      if (formData.password) {
        submitData.password = formData.password;
      }

      const response = await smsSettingsAPI.updateSettings(submitData);

      if (response) {
        setFormData((prev) => ({
          ...prev,
          login: response.login ?? prev.login,
          sender: response.sender ?? prev.sender,
          is_enabled: response.is_enabled ?? prev.is_enabled,
          password: '',
        }));
      }

      toast.success('Настройки SMS успешно сохранены');
    } catch (error: any) {
      console.error('Error saving SMS settings:', error);
      toast.error(error?.response?.data?.message || 'Ошибка при сохранении настроек');
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoadingData) {
    return (
      <div className="p-4 lg:p-8 flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-foreground">Настройки SMS (IQSMS)</h1>
        <p className="text-muted-foreground mt-1">
          Управление отправкой SMS для авторизации по номеру телефона
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5" />
            IQSMS
          </CardTitle>
          <CardDescription>
            Если настройки не заданы в админке, используются переменные окружения (IQSMS_LOGIN, IQSMS_PASSWORD).
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label htmlFor="is_enabled">Включить SMS из настроек БД</Label>
                <p className="text-sm text-muted-foreground">
                  Использовать настройки из базы данных вместо ENV
                </p>
              </div>
              <Switch
                id="is_enabled"
                checked={formData.is_enabled}
                onCheckedChange={(checked) =>
                  setFormData((prev) => ({ ...prev, is_enabled: !!checked }))
                }
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="login">Логин</Label>
              <Input
                id="login"
                value={formData.login}
                onChange={(e) => setFormData((prev) => ({ ...prev, login: e.target.value }))}
                placeholder="IQSMS login"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password">Пароль</Label>
              <Input
                id="password"
                type="password"
                value={formData.password}
                onChange={(e) => setFormData((prev) => ({ ...prev, password: e.target.value }))}
                placeholder="Оставьте пустым, чтобы не менять"
                autoComplete="new-password"
              />
              <p className="text-xs text-muted-foreground">
                Хранится в зашифрованном виде. Заполните только при смене пароля.
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="sender">Подпись отправителя</Label>
              <Input
                id="sender"
                value={formData.sender}
                onChange={(e) => setFormData((prev) => ({ ...prev, sender: e.target.value }))}
                placeholder="INFO"
                maxLength={20}
              />
            </div>

            <Button type="submit" disabled={isLoading}>
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Сохранить
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
