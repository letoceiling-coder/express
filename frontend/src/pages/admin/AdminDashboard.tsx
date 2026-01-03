import { Link } from 'react-router-dom';
import { ClipboardList, Package, TrendingUp, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { mockOrders, mockProducts, mockCategories } from '@/data/mockData';
import { StatusBadge } from '@/components/ui/status-badge';

export function AdminDashboard() {
  const activeOrders = mockOrders.filter(
    (o) => !['delivered', 'cancelled'].includes(o.status)
  );

  const todayOrders = mockOrders.filter((o) => {
    const today = new Date();
    const orderDate = new Date(o.createdAt);
    return orderDate.toDateString() === today.toDateString();
  });

  const totalRevenue = mockOrders
    .filter((o) => o.status !== 'cancelled')
    .reduce((sum, o) => sum + o.totalAmount, 0);

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(date));
  };

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 lg:mb-8">
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-800 dark:text-slate-100">Панель управления</h1>
        <p className="mt-1 text-slate-500 dark:text-slate-400">
          Добро пожаловать в админ-панель
        </p>
      </div>

      {/* Stats Cards */}
      <div className="mb-6 lg:mb-8 grid gap-3 lg:gap-4 grid-cols-2 lg:grid-cols-4">
        <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 lg:p-6 lg:pb-2">
            <CardTitle className="text-xs lg:text-sm font-medium text-slate-500 dark:text-slate-400">
              Активные заказы
            </CardTitle>
            <div className="rounded-lg bg-amber-100 dark:bg-amber-900/30 p-1.5 lg:p-2">
              <Clock className="h-3 w-3 lg:h-4 lg:w-4 text-amber-600 dark:text-amber-400" />
            </div>
          </CardHeader>
          <CardContent className="p-3 lg:p-6 pt-0 lg:pt-0">
            <div className="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">{activeOrders.length}</div>
            <p className="text-[10px] lg:text-xs text-slate-400 dark:text-slate-500">Ожидают обработки</p>
          </CardContent>
        </Card>

        <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 lg:p-6 lg:pb-2">
            <CardTitle className="text-xs lg:text-sm font-medium text-slate-500 dark:text-slate-400">
              Заказов сегодня
            </CardTitle>
            <div className="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-1.5 lg:p-2">
              <ClipboardList className="h-3 w-3 lg:h-4 lg:w-4 text-blue-600 dark:text-blue-400" />
            </div>
          </CardHeader>
          <CardContent className="p-3 lg:p-6 pt-0 lg:pt-0">
            <div className="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">{todayOrders.length}</div>
            <p className="text-[10px] lg:text-xs text-slate-400 dark:text-slate-500">За текущий день</p>
          </CardContent>
        </Card>

        <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 lg:p-6 lg:pb-2">
            <CardTitle className="text-xs lg:text-sm font-medium text-slate-500 dark:text-slate-400">
              Товаров
            </CardTitle>
            <div className="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-1.5 lg:p-2">
              <Package className="h-3 w-3 lg:h-4 lg:w-4 text-purple-600 dark:text-purple-400" />
            </div>
          </CardHeader>
          <CardContent className="p-3 lg:p-6 pt-0 lg:pt-0">
            <div className="text-xl lg:text-2xl font-bold text-slate-800 dark:text-slate-100">{mockProducts.length}</div>
            <p className="text-[10px] lg:text-xs text-slate-400 dark:text-slate-500">
              В {mockCategories.length} категориях
            </p>
          </CardContent>
        </Card>

        <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between pb-2 p-3 lg:p-6 lg:pb-2">
            <CardTitle className="text-xs lg:text-sm font-medium text-slate-500 dark:text-slate-400">
              Общая выручка
            </CardTitle>
            <div className="rounded-lg bg-emerald-100 dark:bg-emerald-900/30 p-1.5 lg:p-2">
              <TrendingUp className="h-3 w-3 lg:h-4 lg:w-4 text-emerald-600 dark:text-emerald-400" />
            </div>
          </CardHeader>
          <CardContent className="p-3 lg:p-6 pt-0 lg:pt-0">
            <div className="text-xl lg:text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {totalRevenue.toLocaleString('ru-RU')} ₽
            </div>
            <p className="text-[10px] lg:text-xs text-slate-400 dark:text-slate-500">По всем заказам</p>
          </CardContent>
        </Card>
      </div>

      {/* Recent Orders */}
      <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
        <CardHeader className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 lg:p-6">
          <CardTitle className="text-slate-800 dark:text-slate-100">Последние заказы</CardTitle>
          <Link to="/admin/orders">
            <Button variant="outline" size="sm" className="w-full sm:w-auto border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-800 dark:hover:text-slate-100">
              Все заказы
            </Button>
          </Link>
        </CardHeader>
        <CardContent className="p-4 lg:p-6 pt-0">
          <div className="space-y-4">
            {mockOrders.slice(0, 5).map((order) => (
              <div
                key={order.id}
                className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-4 last:border-0 last:pb-0"
              >
                <div className="flex items-center gap-4">
                  <div>
                    <p className="font-medium text-slate-800 dark:text-slate-100">{order.orderId}</p>
                    <p className="text-sm text-slate-400 dark:text-slate-500">
                      {formatDate(order.createdAt)}
                    </p>
                  </div>
                </div>
                <div className="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                  <StatusBadge status={order.status} />
                  <span className="font-medium text-slate-700 dark:text-slate-200">
                    {order.totalAmount.toLocaleString('ru-RU')} ₽
                  </span>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
