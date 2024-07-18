<?php

namespace App\Http\Controllers\Commands;

use App\Enums\UserStatus;

class MainMenu extends BaseCommand
{
    protected function processCommand(): void
    {
        if ($this->user->status === UserStatus::ASK_PASSWORD) {
            if ($this->checkPassword()) {
                $this->updateUserStatus(UserStatus::DONE);
                $this->notifyAboutCorrectPassword();
            } else {
                $this->notifyAboutWrongPassword();
            }
        } elseif (! $this->user->isAuthorized()) {
            $this->updateUserStatus(UserStatus::ASK_PASSWORD);
            $this->promptForPassword();
        }
    }

    private function checkPassword(): bool
    {
        return $this->update->getMessage()->getText() === config('telegram.password');
    }

    private function updateUserStatus(UserStatus $status): void
    {
        $this->user->update(['status' => $status]);
    }

    private function promptForPassword(): void
    {
        $this->getBot()->sendMessage(
            $this->user->chat_id,
            'Введіть пароль',
        );
    }

    private function notifyAboutWrongPassword(): void
    {
        $this->getBot()->sendMessage(
            $this->user->chat_id,
            'Неправильний пароль',
        );
    }

    private function notifyAboutCorrectPassword(): void
    {
        $this->getBot()->sendMessage(
            $this->user->chat_id,
            'Пароль прийнято',
        );
    }
}
