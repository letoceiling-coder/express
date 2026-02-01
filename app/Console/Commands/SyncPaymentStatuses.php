<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Services\Payment\YooKassaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPaymentStatuses extends Command
{
    protected $signature = 'payments:sync-statuses
                            {--dry-run : Показать изменения без сохранения}';

    protected $description = 'Синхронизирует статусы платежей ЮKassa с API (pending/processing → актуальный статус)';

    public function handle(): int
    {
        $settings = PaymentSetting::forProvider('yookassa');

        if (!$settings || !$settings->is_enabled) {
            $this->warn('ЮKassa отключена или не настроена');
            return 1;
        }

        $payments = Payment::where('payment_provider', 'yookassa')
            ->whereNotNull('transaction_id')
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
            ->get();

        if ($payments->isEmpty()) {
            $this->info('Нет платежей для синхронизации');
            return 0;
        }

        $this->info("Найдено платежей для проверки: {$payments->count()}");
        $dryRun = $this->option('dry-run');

        $yooKassaService = new YooKassaService($settings);
        $synced = 0;
        $errors = 0;

        foreach ($payments as $payment) {
            try {
                $yooKassaPayment = $yooKassaService->getPayment($payment->transaction_id);
                $yooKassaStatus = $yooKassaPayment['status'] ?? null;

                if (!$yooKassaStatus) {
                    $errors++;
                    continue;
                }

                $statusMap = [
                    'pending' => Payment::STATUS_PENDING,
                    'waiting_for_capture' => Payment::STATUS_PROCESSING,
                    'succeeded' => Payment::STATUS_SUCCEEDED,
                    'canceled' => Payment::STATUS_CANCELLED,
                ];
                $newStatus = $statusMap[$yooKassaStatus] ?? $payment->status;

                if ($newStatus === $payment->status) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("  Заказ #{$payment->order?->order_id}: {$payment->status} → {$newStatus}");
                    $synced++;
                    continue;
                }

                DB::beginTransaction();

                $payment->status = $newStatus;
                $payment->provider_response = $yooKassaPayment;
                if (isset($yooKassaPayment['paid_at'])) {
                    $payment->paid_at = \Carbon\Carbon::parse($yooKassaPayment['paid_at']);
                }
                if (isset($yooKassaPayment['captured_at'])) {
                    $payment->captured_at = \Carbon\Carbon::parse($yooKassaPayment['captured_at']);
                }
                $payment->save();

                if ($newStatus === Payment::STATUS_SUCCEEDED && $payment->order) {
                    $order = $payment->order;
                    $order->paid = true;
                    $order->payment_status = 'succeeded';
                    $order->payment_id = (string) $payment->id;
                    if ($order->status === 'new') {
                        $order->status = \App\Models\Order::STATUS_PAID;
                    }
                    $order->save();
                }

                DB::commit();
                $synced++;

                Log::info('payments:sync-statuses - обновлён', [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'new_status' => $newStatus,
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors++;
                Log::error('payments:sync-statuses - ошибка', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Готово. Обновлено: {$synced}, ошибок: {$errors}");
        return 0;
    }
}
