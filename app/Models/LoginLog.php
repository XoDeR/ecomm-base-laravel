<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $login_time
 * @property Carbon|null $logout_time
 */
#[Fillable(['user_id', 'ip_address', 'user_agent', 'login_time', 'logout_time'])]
class LoginLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'login_time' => 'datetime',
            'logout_time' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
