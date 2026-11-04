<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $old_slug
 * @property string $new_slug
 * @property string $entity_type product|category|page
 * @property int|null $entity_id
 * @property int $status_code
 * @property bool $is_active
 */
#[Fillable(['old_slug', 'new_slug', 'entity_type', 'entity_id', 'status_code', 'is_active'])]
class UrlRedirect extends Model
{
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
