<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель способа оплаты
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property bool $is_enabled
 * @property int $sort_order
 * @property string $discount_type
 * @property float|null $discount_value
 * @property float|null $min_cart_amount
 * @property bool $show_notification
 * @property string|null $notification_text
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_enabled',
        'sort_order',
        'discount_type',
        'discount_value',
        'min_cart_amount',
        'show_notification',
        'notification_text',
        'settings',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'discount_value' => 'decimal:2',
        'min_cart_amount' => 'decimal:2',
        'show_notification' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Типы скидок
     */
    const DISCOUNT_TYPE_NONE = 'none';
    const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    const DISCOUNT_TYPE_FIXED = 'fixed';

    /**
     * Получить активные способы оплаты
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_enabled', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Рассчитать скидку для суммы корзины
     * 
     * @param float $cartAmount
     * @return array ['discount' => float, 'final_amount' => float, 'applied' => bool]
     */
    public function calculateDiscount(float $cartAmount): array
    {
        $discount = 0;
        $applied = false;

        if ($this->discount_type === self::DISCOUNT_TYPE_NONE) {
            return [
                'discount' => 0,
                'final_amount' => $cartAmount,
                'applied' => false,
            ];
        }

        // Проверяем минимальную сумму корзины
        if ($this->min_cart_amount && $cartAmount < $this->min_cart_amount) {
            return [
                'discount' => 0,
                'final_amount' => $cartAmount,
                'applied' => false,
            ];
        }

        // Рассчитываем скидку
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE && $this->discount_value) {
            $discount = ($cartAmount * $this->discount_value) / 100;
            $applied = true;
        } elseif ($this->discount_type === self::DISCOUNT_TYPE_FIXED && $this->discount_value) {
            $discount = min($this->discount_value, $cartAmount); // Не больше суммы корзины
            $applied = true;
        }

        $finalAmount = max(0, $cartAmount - $discount);

        return [
            'discount' => round($discount, 2),
            'final_amount' => round($finalAmount, 2),
            'applied' => $applied,
        ];
    }

    /**
     * Получить текст уведомления (если нужно показывать)
     */
    public function getNotificationMessage(float $cartAmount): ?string
    {
        if (!$this->show_notification || !$this->notification_text) {
            return null;
        }

        $discountInfo = $this->calculateDiscount($cartAmount);
        
        if (!$discountInfo['applied']) {
            return null;
        }

        // Заменяем плейсхолдеры в тексте уведомления
        $message = $this->notification_text;
        $message = str_replace('{discount}', number_format($discountInfo['discount'], 2, '.', ' '), $message);
        $message = str_replace('{final_amount}', number_format($discountInfo['final_amount'], 2, '.', ' '), $message);
        $message = str_replace('{cart_amount}', number_format($cartAmount, 2, '.', ' '), $message);
        
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE && $this->discount_value) {
            $message = str_replace('{discount_percent}', number_format($this->discount_value, 0), $message);
        }

        return $message;
    }
}
