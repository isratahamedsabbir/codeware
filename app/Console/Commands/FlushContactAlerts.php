<?php

namespace App\Console\Commands;

use App\Support\ContactAlerts;
use Illuminate\Console\Command;

class FlushContactAlerts extends Command
{
    protected $signature = 'contacts:flush-alerts';

    protected $description = 'Send the batched admin summary of new contact messages';

    public function handle(): int
    {
        ContactAlerts::flushDue();

        return self::SUCCESS;
    }
}
