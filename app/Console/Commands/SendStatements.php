<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendStatements extends Command
{
    protected $signature = 'statements:send';

    public function handle(): int
    {
        \App\Jobs\SendStatements::dispatch();

        return Command::SUCCESS;
    }
}
