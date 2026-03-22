<?php

namespace App\Console\Commands;

use App\Models\SmsCode;
use Illuminate\Console\Command;

class SmsCodesCleanup extends Command
{
    protected $signature = 'sms:cleanup-codes';

    protected $description = 'Delete expired sms_codes and used codes older than 1 day';

    public function handle(): int
    {
        $olderThanHours = config('sms.cleanup_used_older_than_hours', 24);
        $cutoff = now()->subHours($olderThanHours);

        $expired = SmsCode::where('expires_at', '<', now())->delete();
        $used = SmsCode::whereNotNull('used_at')->where('used_at', '<', $cutoff)->delete();

        $this->info("Deleted {$expired} expired codes, {$used} old used codes.");

        return self::SUCCESS;
    }
}
