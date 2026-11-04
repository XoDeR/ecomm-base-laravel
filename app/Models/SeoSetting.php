<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $keywords
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image
 * @property string|null $canonical_url
 * @property string|null $robots_meta
 */
#[Fillable([
    'entity_type', 'entity_id', 'title', 'description', 'keywords',
    'og_title', 'og_description', 'og_image', 'canonical_url', 'robots_meta',
])]
class SeoSetting extends Model
{
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
