<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderSetting;
use App\Services\Order\OrderNotificationService;
use App\Services\Order\OrderStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Автоматически отменяет неоплаченные заказы по истечении TTL';

    protected OrderStatusService $orderStatusService;
    protected OrderNotificationService $notificationService;

    public function __construct(
        OrderStatusService $orderStatusService,
        OrderNotificationService $notificationService
    ) {
        parent::__construct();
        $this->orderStatusService = $orderStatusService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = OrderSetting::getSettings();
        $ttlMinutes = $settings->payment_ttl_minutes;
        
        $this->info("Проверка неоплаченных заказов (TTL: {$ttlMinutes} минут)");

        // Находим заказы, которые:
        // 1. Имеют статус payment_status = 'pending'
        // 2. Статус заказа не 'cancelled' и не 'delivered'
        // 3. Созданы более TTL минут назад
        $expiredOrders = Order::where('payment_status', Order::PAYMENT_STATUS_PENDING)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED])
            ->where('created_at', '<=', now()->subMinutes($ttlMinutes))
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('Нет заказов для отмены');
            return 0;
        }

        $this->info("Найдено заказов для отмены: {$expiredOrders->count()}");

        $cancelledCount = 0;
        foreach ($expiredOrders as $order) {
            try {
                // Отменяем заказ
                $this->orderStatusService->changeStatus($order, Order::STATUS_CANCELLED, [
                    'role' => 'system',
                    'comment' => 'Не оплачен вовремя',
                ]);

                $order->refresh();

                // Отправляем уведомление клиенту, если включено
                if ($settings->notification_auto_cancel_enabled) {
                    $template = $settings->notification_auto_cancel_template 
                        ?? 'Заказ №{{orderId}} отменён, потому что не был оплачен.';
                    
                    $message = $settings->replaceTemplatePlaceholders($template, [
                        'orderId' => $order->order_id,
                        'amount' => number_format($order->total_amount, 2, '.', ' '),
                    ]);

                    $this->notificationService->notifyClientStatusChange($order, Order::STATUS_CANCELLED, [
                        'message' => $message,
                    ]);
                }

                $cancelledCount++;
                $this->line("  ✓ Заказ #{$order->order_id} отменен");

                Log::info('Unpaid order auto-cancelled', [
                    'order_id' => $order->id,
                    'order_order_id' => $order->order_id,
                    'created_at' => $order->created_at,
                    'ttl_minutes' => $ttlMinutes,
                ]);
            } catch (\Exception $e) {
                $this->error("  ✗ Ошибка при отмене заказа #{$order->order_id}: {$e->getMessage()}");
                Log::error('Error cancelling unpaid order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Отменено заказов: {$cancelledCount}");
        return 0;
    }
}
