<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'enabled',
        'message_template',
        'buttons',
        'support_chat_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'buttons' => 'array',
    ];

    /**
     * Получить настройку по событию
     */
    public static function getByEvent(string $event): ?self
    {
        return self::where('event', $event)->first();
    }

    /**
     * Получить настройку или создать дефолтную
     */
    public static function getOrCreate(string $event, array $defaults = []): self
    {
        $setting = self::where('event', $event)->first();
        
        if (!$setting) {
            $setting = self::create(array_merge([
                'event' => $event,
                'enabled' => true,
                'message_template' => null,
                'buttons' => null,
                'support_chat_id' => null,
            ], $defaults));
        }
        
        return $setting;
    }

    /**
     * Заменить плейсхолдеры в шаблоне
     */
    public function replacePlaceholders(array $data): string
    {
        if (!$this->message_template) {
            return '';
        }

        $result = $this->message_template;
        foreach ($data as $key => $value) {
            $result = str_replace('{' . $key . '}', $value, $result);
            $result = str_replace('{{' . $key . '}}', $value, $result);
        }
        
        return $result;
    }

    /**
     * Получить кнопки с заменой плейсхолдеров
     */
    public function getButtons(array $data = []): array
    {
        if (!$this->buttons || !is_array($this->buttons)) {
            return [];
        }

        $buttons = $this->buttons;
        
        // Заменяем плейсхолдеры в значениях кнопок
        foreach ($buttons as &$row) {
            if (is_array($row)) {
                foreach ($row as &$button) {
                    if (isset($button['value']) && is_string($button['value'])) {
                        foreach ($data as $key => $value) {
                            $button['value'] = str_replace('{' . $key . '}', $value, $button['value']);
                            $button['value'] = str_replace('{{' . $key . '}}', $value, $button['value']);
                        }
                    }
                }
            }
        }

        return $buttons;
    }

    /**
     * Преобразовать кнопки в формат Telegram API
     */
    public function formatButtonsForTelegram(array $data = []): array
    {
        $buttons = $this->getButtons($data);
        
        if (empty($buttons)) {
            return [];
        }

        $keyboard = ['inline_keyboard' => []];
        
        foreach ($buttons as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $keyboardRow = [];
            foreach ($row as $button) {
                if (!isset($button['text']) || !isset($button['type'])) {
                    continue;
                }

                $buttonData = [
                    'text' => $button['text'],
                ];

                switch ($button['type']) {
                    case 'callback':
                        $buttonData['callback_data'] = $button['value'] ?? '';
                        break;
                    case 'open_chat':
                        // Для open_chat используем URL напрямую, если указан support_chat_id
                        if ($this->support_chat_id) {
                            // Проверяем, является ли support_chat_id числом (user_id) или строкой (username)
                            if (is_numeric($this->support_chat_id)) {
                                $buttonData['url'] = "tg://user?id={$this->support_chat_id}";
                            } else {
                                // Если это username (начинается с @), используем resolve
                                $username = ltrim($this->support_chat_id, '@');
                                $buttonData['url'] = "tg://resolve?domain={$username}";
                            }
                        } else {
                            // Если support_chat_id не указан, используем callback для обработки в боте
                            $buttonData['callback_data'] = "open_support_chat:{$button['value']}";
                        }
                        break;
                    case 'open_url':
                        $buttonData['url'] = $button['value'] ?? '';
                        break;
                    default:
                        // По умолчанию callback
                        $buttonData['callback_data'] = $button['value'] ?? '';
                        break;
                }

                $keyboardRow[] = $buttonData;
            }
            
            if (!empty($keyboardRow)) {
                $keyboard['inline_keyboard'][] = $keyboardRow;
            }
        }

        return $keyboard;
    }
}
