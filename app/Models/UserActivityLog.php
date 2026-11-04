<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $event_type
 * @property string $description
 * @property array<string, mixed>|null $meta
 */
#[Fillable(['user_id', 'event_type', 'description', 'meta'])]
class UserActivityLog extends Model
{
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record something a signed-in customer did. Guests are not tracked.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function record(?int $userId, string $eventType, string $description, array $meta = []): void
    {
        if ($userId === null) {
            return;
        }

        static::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }
}
