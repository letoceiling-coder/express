<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель претензии
 * 
 * @property int $id
 * @property int|null $order_id
 * @property string $complaint_number
 * @property string $type
 * @property string $priority
 * @property string $status
 * @property string $subject
 * @property string $description
 * @property string|null $customer_name
 * @property string|null $customer_phone
 * @property string|null $customer_email
 * @property array|null $attachments
 * @property int|null $assigned_to
 * @property string|null $resolution
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * 
 * @property-read Order|null $order
 * @property-read User|null $assignedTo
 * @property-read User|null $resolvedBy
 */
class Complaint extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     * 
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'complaint_number',
        'type',
        'priority',
        'status',
        'subject',
        'description',
        'customer_name',
        'customer_phone',
        'customer_email',
        'attachments',
        'assigned_to',
        'resolution',
        'resolved_at',
        'resolved_by',
        'closed_at',
    ];

    /**
     * Приведение типов
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'order_id' => 'integer',
        'attachments' => 'array',
        'assigned_to' => 'integer',
        'resolved_by' => 'integer',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Типы претензий
     */
    const TYPE_QUALITY = 'quality';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_SERVICE = 'service';
    const TYPE_PAYMENT = 'payment';
    const TYPE_OTHER = 'other';

    /**
     * Приоритеты претензий
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Статусы претензий
     */
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CLOSED = 'closed';

    /**
     * Boot метод для автогенерации complaint_number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($complaint) {
            if (empty($complaint->complaint_number)) {
                $now = now();
                $dateStr = $now->format('Ymd');
                $randomNum = rand(1, 9999);
                $complaint->complaint_number = "COMP-{$dateStr}-{$randomNum}";
            }
        });
    }

    /**
     * Связь с заказом
     * 
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Связь с назначенным сотрудником
     * 
     * @return BelongsTo
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    /**
     * Связь с сотрудником, который решил претензию
     * 
     * @return BelongsTo
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'id');
    }

    /**
     * Получить вложения (accessor для получения медиа)
     * 
     * @return \Illuminate\Database\Eloquent\Collection|Media[]
     */
    public function getAttachmentFilesAttribute()
    {
        if (empty($this->attachments)) {
            return collect([]);
        }

        return Media::whereIn('id', $this->attachments)->get();
    }

    /**
     * Scope для фильтрации по статусу
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для фильтрации по приоритету
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}
