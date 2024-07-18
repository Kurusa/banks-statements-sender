<?php

namespace App\Enums;

enum UserStatus: string
{
    case ASK_PASSWORD = 'ask_password';
    case DONE = 'done';
}
