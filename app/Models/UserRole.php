<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
#[Fillable(['name', 'slug', 'description'])]
class UserRole extends Model
{
    public const ADMIN = 'admin';

    public const MODERATOR = 'moderator';

    public const CUSTOMER = 'customer';

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
