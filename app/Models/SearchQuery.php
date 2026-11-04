<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $query
 * @property int $results_count
 * @property int $search_count
 * @property Carbon $last_searched
 */
#[Fillable(['query', 'results_count', 'search_count', 'last_searched'])]
class SearchQuery extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['last_searched' => 'datetime'];
    }

    /**
     * Record a search so popular queries can be suggested later.
     */
    public static function record(string $query, int $resultsCount): void
    {
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return;
        }

        $row = static::query()->firstOrNew(['query' => $query]);
        $row->results_count = $resultsCount;
        $row->search_count = $row->exists ? $row->search_count + 1 : 1;
        $row->last_searched = now();
        $row->save();
    }
}
