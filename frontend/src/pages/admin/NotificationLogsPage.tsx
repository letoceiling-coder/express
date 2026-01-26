import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2, Bell, Search, Filter, FileText } from 'lucide-react';
import { toast } from 'sonner';
import { orderNotificationLogsAPI } from '@/api';

interface OrderNotificationLog {
  id: number;
  order_id: number;
  telegram_user_id: number;
  message_id: number;
  chat_id: number;
  notification_type: string;
  status: string;
  expires_at: string | null;
  created_at: string;
  updated_at: string;
  order?: {
    id: number;
    order_id: string;
  };
  telegramUser?: {
    id: number;
    first_name: string;
    last_name?: string;
    username?: string;
    role: string;
  };
}

const NOTIFICATION_TYPE_LABELS: Record<string, string> = {
  admin_new: 'Новый заказ (админ)',
  admin_status: 'Изменение статуса (админ)',
  client_status: 'Изменение статуса (клиент)',
  kitchen_order: 'Заказ на кухне',
  courier_order: 'Заказ курьеру',
};

const STATUS_LABELS: Record<string, { label: string; color: string }> = {
  active: { label: 'Активно', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
  updated: { label: 'Обновлено', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
  deleted: { label: 'Удалено', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
};

export function NotificationLogsPage() {
  const [isLoading, setIsLoading] = useState(false);
  const [logs, setLogs] = useState<OrderNotificationLog[]>([]);
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
  });
  const [filters, setFilters] = useState({
    type: '',
    status: '',
    search: '',
  });
  const [currentPage, setCurrentPage] = useState(1);

  useEffect(() => {
    loadLogs();
  }, [currentPage, filters]);

  const loadLogs = async () => {
    try {
      setIsLoading(true);
      const response = await orderNotificationLogsAPI.getLogs({
        ...filters,
        page: currentPage,
        per_page: 50,
      });
      setLogs(response.data || []);
      setMeta(response.meta);
    } catch (error: any) {
      console.error('Error loading notification logs:', error);
      toast.error('Ошибка при загрузке истории уведомлений');
    } finally {
      setIsLoading(false);
    }
  };

  const handleFilterChange = (key: string, value: string) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setCurrentPage(1);
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date);
  };

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 lg:mb-8">
        <h1 className="text-2xl lg:text-3xl font-bold text-foreground flex items-center gap-2">
          <FileText className="h-6 w-6" />
          История уведомлений
        </h1>
        <p className="mt-1 text-muted-foreground">
          Просмотр всех отправленных уведомлений о заказах
        </p>
      </div>

      {/* Фильтры */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="h-5 w-5" />
            Фильтры
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label htmlFor="search">Поиск по номеру заказа</Label>
              <div className="relative mt-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  id="search"
                  placeholder="ORD-20260126-..."
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="pl-9"
                />
              </div>
            </div>
            <div>
              <Label htmlFor="type">Тип уведомления</Label>
              <select
                id="type"
                value={filters.type}
                onChange={(e) => handleFilterChange('type', e.target.value)}
                className="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground mt-1"
              >
                <option value="">Все типы</option>
                {Object.entries(NOTIFICATION_TYPE_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
            </div>
            <div>
              <Label htmlFor="status">Статус</Label>
              <select
                id="status"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                className="w-full h-10 px-3 rounded-lg border border-input bg-background text-foreground mt-1"
              >
                <option value="">Все статусы</option>
                {Object.entries(STATUS_LABELS).map(([value, { label }]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Таблица логов */}
      {isLoading ? (
        <div className="flex items-center justify-center py-20">
          <Loader2 className="h-8 w-8 animate-spin text-primary" />
        </div>
      ) : logs.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <p className="text-muted-foreground">История уведомлений пуста</p>
          </CardContent>
        </Card>
      ) : (
        <>
          <Card>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead className="bg-muted/50">
                    <tr>
                      <th className="px-4 py-3 text-left text-sm font-medium text-foreground">ID</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-foreground">Заказ</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-foreground">Получатель</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-foreground">Тип</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-foreground">Статус</th>
                      <th className="px-4 py-3 text-left text-sm font-medium text-foreground">Дата отправки</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {logs.map((log) => (
                      <tr key={log.id} className="hover:bg-muted/50">
                        <td className="px-4 py-3 text-sm text-foreground">#{log.id}</td>
                        <td className="px-4 py-3 text-sm">
                          {log.order ? (
                            <span className="font-medium text-primary">
                              {log.order.order_id}
                            </span>
                          ) : (
                            <span className="text-muted-foreground">Заказ #{log.order_id}</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-sm">
                          {log.telegramUser ? (
                            <div>
                              <div className="font-medium">
                                {log.telegramUser.first_name} {log.telegramUser.last_name || ''}
                              </div>
                              <div className="text-xs text-muted-foreground">
                                @{log.telegramUser.username || 'без username'} • {log.telegramUser.role}
                              </div>
                            </div>
                          ) : (
                            <span className="text-muted-foreground">Пользователь #{log.telegram_user_id}</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-sm">
                          <span className="px-2 py-1 rounded bg-accent/10 text-accent text-xs">
                            {NOTIFICATION_TYPE_LABELS[log.notification_type] || log.notification_type}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm">
                          <span className={`px-2 py-1 rounded text-xs ${STATUS_LABELS[log.status]?.color || 'bg-gray-100 text-gray-800'}`}>
                            {STATUS_LABELS[log.status]?.label || log.status}
                          </span>
                        </td>
                        <td className="px-4 py-3 text-sm text-muted-foreground">
                          {formatDate(log.created_at)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>

          {/* Пагинация */}
          {meta.last_page > 1 && (
            <div className="mt-4 flex items-center justify-between">
              <div className="text-sm text-muted-foreground">
                Показано {((meta.current_page - 1) * meta.per_page) + 1} - {Math.min(meta.current_page * meta.per_page, meta.total)} из {meta.total}
              </div>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                  disabled={meta.current_page === 1 || isLoading}
                >
                  Назад
                </Button>
                <span className="text-sm text-muted-foreground">
                  Страница {meta.current_page} из {meta.last_page}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setCurrentPage(prev => Math.min(meta.last_page, prev + 1))}
                  disabled={meta.current_page === meta.last_page || isLoading}
                >
                  Вперед
                </Button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}
