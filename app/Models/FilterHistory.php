<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $category_id
 * @property array<string, mixed>|null $filters
 * @property int|null $user_id
 * @property string|null $ip_address
 */
#[Fillable(['category_id', 'filters', 'user_id', 'ip_address'])]
class FilterHistory extends Model
{
    protected $table = 'filter_history';

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
