<?php

namespace App\Console\Commands;

use App\Jobs\SendStatementsJob as SendStatementsJob;
use Illuminate\Console\Command;

class SendStatementsCommand extends Command
{
    protected $signature = 'statements:send';

    public function handle(): int
    {
        SendStatementsJob::dispatch();

        return Command::SUCCESS;
    }
}
