import { useState } from 'react';
import { Eye, Trash2, Search } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { mockOrders } from '@/data/mockData';
import { Order, OrderStatus, ORDER_STATUS_LABELS } from '@/types';
import { StatusBadge } from '@/components/ui/status-badge';
import { toast } from 'sonner';

const statusOptions: OrderStatus[] = [
  'new',
  'accepted',
  'preparing',
  'ready_for_delivery',
  'in_transit',
  'delivered',
  'cancelled',
];

export function AdminOrders() {
  const [orders, setOrders] = useState(mockOrders);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  const filteredOrders = orders.filter((order) => {
    const matchesSearch =
      order.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
      order.phone.includes(searchTerm);
    const matchesStatus =
      statusFilter === 'all' || order.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(date));
  };

  const handleStatusChange = (orderId: string, newStatus: OrderStatus) => {
    setOrders((prev) =>
      prev.map((o) =>
        o.id === orderId ? { ...o, status: newStatus, updatedAt: new Date() } : o
      )
    );
    toast.success('Статус заказа обновлён');
  };

  const handleDeleteOrder = (orderId: string) => {
    if (confirm('Вы уверены, что хотите удалить этот заказ?')) {
      setOrders((prev) => prev.filter((o) => o.id !== orderId));
      setSelectedOrder(null);
      toast.success('Заказ удалён');
    }
  };

  return (
    <div className="p-4 lg:p-8">
      <div className="mb-6 lg:mb-8">
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-800 dark:text-slate-100">Заказы</h1>
        <p className="mt-1 text-slate-500 dark:text-slate-400">
          Управление заказами клиентов
        </p>
      </div>

      <Card className="border-0 bg-white dark:bg-slate-800 shadow-sm">
        <CardHeader className="p-4 lg:p-6">
          <div className="flex flex-col gap-4">
            <CardTitle className="text-slate-800 dark:text-slate-100">Список заказов</CardTitle>
            <div className="flex flex-col gap-2 sm:flex-row">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <Input
                  placeholder="Поиск по номеру или телефону"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-9"
                />
              </div>
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-full sm:w-40">
                  <SelectValue placeholder="Статус" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Все статусы</SelectItem>
                  {statusOptions.map((status) => (
                    <SelectItem key={status} value={status}>
                      {ORDER_STATUS_LABELS[status]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-4 lg:p-6 pt-0">
          {/* Mobile Cards View */}
          <div className="lg:hidden space-y-3">
            {filteredOrders.map((order) => (
              <div
                key={order.id}
                className="p-4 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-700/50"
              >
                <div className="flex items-start justify-between mb-3">
                  <div>
                    <p className="font-medium text-slate-800 dark:text-slate-100">{order.orderId}</p>
                    <p className="text-sm text-slate-500 dark:text-slate-400">{formatDate(order.createdAt)}</p>
                  </div>
                  <StatusBadge status={order.status} size="sm" />
                </div>
                <p className="text-sm text-slate-600 dark:text-slate-300 mb-3 line-clamp-2">{order.deliveryAddress}</p>
                <div className="flex items-center justify-between">
                  <span className="font-semibold text-slate-800 dark:text-slate-100">
                    {order.totalAmount.toLocaleString('ru-RU')} ₽
                  </span>
                  <div className="flex gap-2">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-9 w-9"
                      onClick={() => setSelectedOrder(order)}
                    >
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="h-9 w-9 text-red-500 hover:text-red-600"
                      onClick={() => handleDeleteOrder(order.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Desktop Table View */}
          <div className="hidden lg:block">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Заказ</TableHead>
                  <TableHead>Дата</TableHead>
                  <TableHead>Статус</TableHead>
                  <TableHead>Адрес</TableHead>
                  <TableHead>Сумма</TableHead>
                  <TableHead className="text-right">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredOrders.map((order) => (
                  <TableRow key={order.id}>
                    <TableCell className="font-medium">{order.orderId}</TableCell>
                    <TableCell>{formatDate(order.createdAt)}</TableCell>
                    <TableCell>
                      <Select
                        value={order.status}
                        onValueChange={(value) =>
                          handleStatusChange(order.id, value as OrderStatus)
                        }
                      >
                        <SelectTrigger className="w-40">
                          <StatusBadge status={order.status} size="sm" />
                        </SelectTrigger>
                        <SelectContent>
                          {statusOptions.map((status) => (
                            <SelectItem key={status} value={status}>
                              {ORDER_STATUS_LABELS[status]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </TableCell>
                    <TableCell className="max-w-xs truncate">
                      {order.deliveryAddress}
                    </TableCell>
                    <TableCell>
                      {order.totalAmount.toLocaleString('ru-RU')} ₽
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => setSelectedOrder(order)}
                        >
                          <Eye className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-red-500 hover:text-red-600"
                          onClick={() => handleDeleteOrder(order.id)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          {filteredOrders.length === 0 && (
            <div className="py-8 text-center text-slate-400 dark:text-slate-500">
              Заказы не найдены
            </div>
          )}
        </CardContent>
      </Card>

      {/* Order Detail Dialog */}
      <Dialog open={!!selectedOrder} onOpenChange={() => setSelectedOrder(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Заказ {selectedOrder?.id}</DialogTitle>
          </DialogHeader>
          {selectedOrder && (
            <div className="space-y-6">
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <p className="text-sm text-muted-foreground">Телефон</p>
                  <p className="font-medium">{selectedOrder.phone}</p>
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Время доставки</p>
                  <p className="font-medium">{selectedOrder.deliveryTime}</p>
                </div>
                <div className="sm:col-span-2">
                  <p className="text-sm text-muted-foreground">Адрес</p>
                  <p className="font-medium">{selectedOrder.deliveryAddress}</p>
                </div>
                {selectedOrder.comment && (
                  <div className="sm:col-span-2">
                    <p className="text-sm text-muted-foreground">Комментарий</p>
                    <p className="font-medium">{selectedOrder.comment}</p>
                  </div>
                )}
              </div>

              <div>
                <p className="mb-2 text-sm font-medium">Товары</p>
                <div className="rounded-lg border border-border">
                  {selectedOrder.items.map((item, index) => (
                    <div
                      key={item.id}
                      className={`flex items-center gap-3 p-3 ${
                        index !== selectedOrder.items.length - 1 ? 'border-b border-border' : ''
                      }`}
                    >
                      <img
                        src={item.productImage}
                        alt={item.productName}
                        className="h-12 w-12 rounded-lg object-cover"
                      />
                      <div className="flex-1">
                        <p className="text-sm font-medium">{item.productName}</p>
                        <p className="text-sm text-muted-foreground">
                          {item.quantity} × {item.unitPrice.toLocaleString('ru-RU')} ₽
                        </p>
                      </div>
                      <span className="font-medium">
                        {item.total.toLocaleString('ru-RU')} ₽
                      </span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex items-center justify-between border-t border-border pt-4">
                <span className="text-lg font-semibold">Итого</span>
                <span className="text-xl font-bold">
                  {selectedOrder.totalAmount.toLocaleString('ru-RU')} ₽
                </span>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
