<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A bulk price update that can be reverted as a whole.
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string $mode percent|fixed|currency
 * @property array<string, mixed>|null $mode_params
 * @property int|null $scope_category_id
 * @property bool $scope_include_subcategories
 * @property string|null $scope_vendor
 * @property int $affected_count
 * @property int $skipped_count
 * @property Carbon|null $reverted_at
 */
#[Fillable([
    'admin_id', 'mode', 'mode_params', 'scope_category_id', 'scope_include_subcategories',
    'scope_vendor', 'affected_count', 'skipped_count', 'reverted_at',
])]
class PriceChangeBatch extends Model
{
    protected function casts(): array
    {
        return [
            'mode_params' => 'array',
            'scope_include_subcategories' => 'boolean',
            'reverted_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceChangeBatchItem::class, 'batch_id');
    }

    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }
}
