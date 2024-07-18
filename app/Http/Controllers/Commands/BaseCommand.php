<?php

namespace App\Http\Controllers\Commands;

use App\Models\User;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

abstract class BaseCommand
{
    protected User $user;

    protected $botUser;

    protected $bot;

    public function __construct(readonly protected Update $update)
    {
        if ($update->getCallbackQuery()) {
            $this->botUser = $update->getCallbackQuery()->getFrom();
        } elseif ($update->getMessage()) {
            $this->botUser = $update->getMessage()->getChat();
        } elseif ($update->getInlineQuery()) {
            $this->botUser = $update->getInlineQuery()->getFrom();
        } else {
            throw new \Exception('Cannot get telegram user data');
        }
    }

    public function handle(): void
    {
        $this->user = User::firstOrCreate(
            ['chat_id' => $this->botUser->getId()],
            [
                'user_name' => $this->botUser->getUsername(),
                'first_name' => $this->botUser->getFirstName(),
            ],
        );

        $this->processCommand();
    }

    public function getBot(): BotApi
    {
        if (! $this->bot) {
            $this->bot = new BotApi(config('telegram.token'));
        }

        return $this->bot;
    }

    abstract protected function processCommand(): void;
}
