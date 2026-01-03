<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductHistory;
use Illuminate\Support\Facades\Auth;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        ProductHistory::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'field_name' => null,
            'old_value' => null,
            'new_value' => json_encode($product->toArray()),
            'changes' => $product->toArray(),
        ]);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $changes = [];
        $dirtyAttributes = $product->getDirty();

        if (empty($dirtyAttributes)) {
            return;
        }

        // Собираем все изменения
        foreach ($dirtyAttributes as $field => $newValue) {
            $oldValue = $product->getOriginal($field);
            
            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        // Создаем одну запись со всеми изменениями
        ProductHistory::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'field_name' => null, // null означает что изменилось несколько полей
            'old_value' => null,
            'new_value' => null,
            'changes' => $changes,
        ]);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        ProductHistory::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'field_name' => null,
            'old_value' => json_encode($product->toArray()),
            'new_value' => null,
            'changes' => null,
        ]);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        ProductHistory::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'action' => 'restored',
            'field_name' => null,
            'old_value' => null,
            'new_value' => json_encode($product->toArray()),
            'changes' => null,
        ]);
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        ProductHistory::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'action' => 'force_deleted',
            'field_name' => null,
            'old_value' => json_encode($product->toArray()),
            'new_value' => null,
            'changes' => null,
        ]);
    }

    /**
     * Форматировать значение для сохранения
     * 
     * @param mixed $value
     * @return string|null
     */
    private function formatValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
