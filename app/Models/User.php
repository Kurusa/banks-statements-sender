<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'user_name',
        'first_name',
        'chat_id',
        'status',
    ];

    protected $casts = [
        'status' => UserStatus::class,
    ];

    public function scopeAuthorizedUsers(Builder $query): Builder
    {
        return $query->where('status', UserStatus::DONE);
    }

    public function isAuthorized(): bool
    {
        return $this->status === UserStatus::DONE;
    }
}
