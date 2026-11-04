<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $doc_type
 * @property string|null $comment
 */
#[Fillable(['doc_type', 'comment'])]
class StockDocument extends Model {}
