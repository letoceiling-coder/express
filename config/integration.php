<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Токен интеграции (стороннее приложение / аналог витрины)
    |--------------------------------------------------------------------------
    |
    | Передаётся заголовком X-Integration-Token. При совпадении с этим
    | значением middleware добавляет CORS-заголовки для cross-origin запросов
    | из браузера (обход ограничений при отсутствии общего origin в CORS).
    |
    | Задаётся только через .env, не храните секрет в коде.
    |
    */

    'token' => env('API_INTEGRATION_TOKEN'),

    /*
    | Разрешённые Origin для ответа Access-Control-Allow-Origin.
    | Пустой массив — отражается Origin из запроса (если есть), иначе *.
    | Для production укажите явный список доменов партнёра.
    |
    | Пример: https://partner.example.com,https://app.example.com
    |
    */

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('API_INTEGRATION_ALLOWED_ORIGINS', ''))
    ))),

];
