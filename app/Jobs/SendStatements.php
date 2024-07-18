<?php

namespace App\Jobs;

use App\Enums\BankProvider;
use App\Models\User;
use App\Services\MonoApiService;
use App\Services\PrivatApiService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;

class SendStatements
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private PrivatApiService $privatApiService;

    private MonoApiService $monoApiService;

    protected $bot;

    public function handle(
        PrivatApiService $privatApiService,
        MonoApiService   $monoApiService
    ): void
    {
        $this->privatApiService = $privatApiService;
        $this->monoApiService = $monoApiService;

        if ($this->shouldSendStatements()) {
            foreach (config('cards') as $card) {
                $this->processStatements($card);
            }
        }
    }

    private function shouldSendStatements(): bool
    {
        $now = Carbon::now()->setTimezone('Europe/Kiev');

        return $now->hour === 8 && $now->minute === 0;
    }

    private function processStatements(array $card): void
    {
        $earnings = $this->calculateEarnings($card);

        if ($earnings > 0) {
            $earningsFormatted = number_format($earnings, 2, ',', '');
            $this->sendEarningsToUsers($earningsFormatted, $card['name']);
        }
    }

    private function calculateEarnings(array $card): float
    {
        $earnings = 0;
        $statements = [];

        switch ($card['type']) {
            case BankProvider::MONO:
                $statements = $this->getMonoStatements($card['id'], $card['token']);
                break;
            case BankProvider::PRIVAT:
                $statements = $this->getPrivatStatements($card['id'], $card['token']);
                break;
        }

        foreach ($statements as $statement) {
            if ($this->isValidEarning($card['type'], $statement)) {
                $earnings += $this->getEarningAmount($card['type'], $statement);
            }
        }

        return $earnings;
    }

    private function isValidEarning(BankProvider $bankProvider, array $statement): bool
    {
        if ($bankProvider === BankProvider::MONO) {
            return ! str_starts_with($statement['operationAmount'], '-');
        } elseif ($bankProvider === BankProvider::PRIVAT) {
            return $statement['TRANTYPE'] == 'C';
        }

        return false;
    }

    private function getEarningAmount(BankProvider $type, array $statement): float
    {
        if ($type === BankProvider::MONO) {
            return intval($statement['operationAmount']) / 100;
        } elseif ($type === BankProvider::PRIVAT) {
            return floatval($statement['SUM_E']);
        }

        return 0;
    }

    private function sendEarningsToUsers(string $earnings, string $name): void
    {
        /** @var User $user */
        foreach (User::authorizedUsers()->get() as $user) {
            try {
                $this->getBot()->sendMessage($user->chat_id, $earnings . ' ' . $name . ' ' . Carbon::now()->subDay()->format('d.m.Y'));
            } catch (Exception $e) {
            }
        }
    }

    private function getMonoStatements(string $id, string $token): array
    {
        return $this->monoApiService->getStatements($token, [
            $id,
            Carbon::now()->subDay()->startOfDay()->timestamp,
            Carbon::now()->subDay()->endOfDay()->timestamp,
        ]);
    }

    private function getPrivatStatements(string $id, string $token): array
    {
        $statements = $this->privatApiService->getStatements($token, [
            'acc' => $id,
            'startDate' => Carbon::now()->subDay()->format('d-m-Y'),
            'endDate' => Carbon::now()->subDay()->format('d-m-Y'),
            'limit' => 400,
        ]);

        return $statements['transactions'] ?? [];
    }

    public function getBot(): BotApi
    {
        if (! $this->bot) {
            $this->bot = new BotApi(config('telegram.token'));
        }

        return $this->bot;
    }
}
