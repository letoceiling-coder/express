<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use Illuminate\Http\JsonResponse;

class SupportSettingsController extends Controller
{
    /**
     * Получить настройки поддержки
     * 
     * GET /api/v1/settings/support
     * 
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $page = AboutPage::getPage();
        
        return response()->json([
            'data' => [
                'enabled' => $page->support_enabled ?? true,
                'label' => $page->support_label ?? 'Написать в поддержку',
                'telegram_url' => $page->support_telegram_url,
            ],
        ]);
    }
}
