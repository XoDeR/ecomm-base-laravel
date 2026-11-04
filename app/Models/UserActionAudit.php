<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $admin_id
 * @property string $action_type
 * @property string $reason
 * @property string|null $old_value
 * @property string|null $new_value
 */
#[Fillable(['user_id', 'admin_id', 'action_type', 'reason', 'old_value', 'new_value'])]
class UserActionAudit extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
