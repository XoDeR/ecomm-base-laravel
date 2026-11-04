<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $symbol
 * @property string $rate
 * @property bool $is_active
 */
#[Fillable(['code', 'symbol', 'rate', 'is_active'])]
class Currency extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The currency currently displayed on the storefront.
     */
    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }

    public static function activeSymbol(): string
    {
        return static::active()?->symbol ?? '$';
    }

    /**
     * Make this currency the only active one.
     */
    public function activate(): void
    {
        static::query()->whereKeyNot($this->getKey())->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
