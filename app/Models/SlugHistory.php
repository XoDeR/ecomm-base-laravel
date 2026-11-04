<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string|null $old_slug
 * @property string $new_slug
 * @property int|null $changed_by
 * @property string|null $reason
 */
#[Fillable(['entity_type', 'entity_id', 'old_slug', 'new_slug', 'changed_by', 'reason'])]
class SlugHistory extends Model
{
    protected $table = 'slug_history';

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
