<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class IntegrationGenerateToken extends Command
{
    protected $signature = 'integration:generate-token';

    protected $description = 'Сгенерировать значение для API_INTEGRATION_TOKEN (добавьте в .env вручную)';

    public function handle(): int
    {
        $token = 'int_'.bin2hex(random_bytes(24)); // 48 hex символов после prefix

        $this->line($token);
        $this->newLine();
        $this->info('Добавьте в .env:');
        $this->line('API_INTEGRATION_TOKEN='.$token);

        return self::SUCCESS;
    }
}
