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

class SendStatementsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private PrivatApiService $privatApiService;
    private MonoApiService $monoApiService;
    private BotApi $bot;

    public function __construct(PrivatApiService $privatApiService, MonoApiService $monoApiService, BotApi $bot)
    {
        $this->privatApiService = $privatApiService;
        $this->monoApiService = $monoApiService;
        $this->bot = $bot;
    }

    public function handle(): void
    {
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
        $statements = $this->getStatementsByBankProvider($card);

        foreach ($statements as $statement) {
            if ($this->isValidEarning($card['type'], $statement)) {
                $earnings += $this->getEarningAmount($card['type'], $statement);
            }
        }

        return $earnings;
    }

    private function getStatementsByBankProvider(array $card): array
    {
        return match ($card['type']) {
            BankProvider::MONO => $this->getMonoStatements($card['id'], $card['token']),
            BankProvider::PRIVAT => $this->getPrivatStatements($card['id'], $card['token']),
            default => [],
        };
    }

    private function isValidEarning(BankProvider $bankProvider, array $statement): bool
    {
        return match ($bankProvider) {
            BankProvider::MONO => !str_starts_with($statement['operationAmount'], '-'),
            BankProvider::PRIVAT => $statement['TRANTYPE'] === 'C',
        };
    }

    private function getEarningAmount(BankProvider $type, array $statement): float
    {
        return match ($type) {
            BankProvider::MONO => intval($statement['operationAmount']) / 100,
            BankProvider::PRIVAT => floatval($statement['SUM_E']),
        };
    }

    private function sendEarningsToUsers(string $earnings, string $name): void
    {
        $authorizedUsers = User::authorizedUsers()->get();

        /** @var User $user */
        foreach ($authorizedUsers as $user) {
            try {
                $this->bot->sendMessage($user->chat_id, "$earnings $name " . Carbon::now()->subDay()->format('d.m.Y'));
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
}
