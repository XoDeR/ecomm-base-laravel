<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Currency;
use App\Models\PriceChangeBatch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bulk price updates that can be reverted as a whole.
 *
 * Modes:
 *  - percent:  change by X percent of the current price
 *  - fixed:    change by a flat amount in the store currency
 *  - currency: prices are currently entered in another currency; convert them to the
 *              active store currency using the rates from the currencies table
 */
class PriceBatchService
{
    public const MODES = ['percent', 'fixed', 'currency'];

    /**
     * Products matching the scope filters. An empty scope matches the whole catalog.
     *
     * @param  array{category_id?: int|null, include_subcategories?: bool, vendor?: string|null}  $scope
     * @return Builder<Product>
     */
    public function scope(array $scope): Builder
    {
        $query = Product::query();

        if (! empty($scope['category_id'])) {
            $category = Category::find($scope['category_id']);
            $ids = $category && ! empty($scope['include_subcategories']) ? $category->descendantIds() : [$scope['category_id']];
            $query->whereIn('category_id', $ids);
        }

        if (($scope['vendor'] ?? '') !== '') {
            $query->where('vendor', $scope['vendor']);
        }

        return $query;
    }

    /**
     * @param  array{category_id?: int|null, include_subcategories?: bool, vendor?: string|null}  $scope
     * @param  array{value?: float, direction?: string, from_currency?: string, round_price?: bool}  $params
     *
     * @throws RuntimeException when nothing can be changed
     */
    public function apply(array $scope, string $mode, array $params, ?int $adminId): PriceChangeBatch
    {
        $changes = [];
        $skipped = 0;

        foreach ($this->scope($scope)->get(['id', 'price']) as $product) {
            $new = $this->newPrice((float) $product->price, $mode, $params);

            if ($new === null) {
                $skipped++;

                continue;
            }

            // Unchanged prices are not worth a history record
            if (abs($new - (float) $product->price) >= 0.005) {
                $changes[$product->id] = ['old' => (float) $product->price, 'new' => $new];
            }
        }

        if ($changes === []) {
            throw new RuntimeException($skipped > 0
                ? 'No prices could be calculated. Check that the currency exists.'
                : 'No product prices would change.');
        }

        return DB::transaction(function () use ($scope, $mode, $params, $adminId, $changes, $skipped) {
            $batch = PriceChangeBatch::create([
                'admin_id' => $adminId,
                'mode' => $mode,
                'mode_params' => $params,
                'scope_category_id' => $scope['category_id'] ?? null,
                'scope_include_subcategories' => (bool) ($scope['include_subcategories'] ?? false),
                'scope_vendor' => ($scope['vendor'] ?? '') !== '' ? $scope['vendor'] : null,
                'affected_count' => count($changes),
                'skipped_count' => $skipped,
            ]);

            foreach ($changes as $productId => $change) {
                Product::whereKey($productId)->update(['price' => $change['new']]);
                $batch->items()->create(['product_id' => $productId, 'old_price' => $change['old'], 'new_price' => $change['new']]);
            }

            return $batch;
        });
    }

    /**
     * Restore the previous prices of every product in the batch. A batch can be reverted once.
     *
     * @throws RuntimeException
     */
    public function revert(PriceChangeBatch $batch): int
    {
        if ($batch->isReverted()) {
            throw new RuntimeException('This price change has already been reverted.');
        }

        return DB::transaction(function () use ($batch) {
            $items = $batch->items()->get();

            foreach ($items as $item) {
                Product::whereKey($item->product_id)->update(['price' => $item->old_price]);
            }

            $batch->update(['reverted_at' => now()]);

            return $items->count();
        });
    }

    /**
     * @param  array{value?: float, direction?: string, from_currency?: string, round_price?: bool}  $params
     */
    private function newPrice(float $old, string $mode, array $params): ?float
    {
        $decrease = ($params['direction'] ?? 'increase') === 'decrease';

        $new = match ($mode) {
            'percent' => $old * (1 + ($decrease ? -1 : 1) * abs((float) ($params['value'] ?? 0)) / 100),
            'fixed' => $old + ($decrease ? -1 : 1) * abs((float) ($params['value'] ?? 0)),
            'currency' => $this->convert($old, strtoupper((string) ($params['from_currency'] ?? ''))),
            default => null,
        };

        if ($new === null) {
            return null;
        }

        return max(0, round($new, ! empty($params['round_price']) ? 0 : 2));
    }

    private function convert(float $amount, string $fromCode): ?float
    {
        $from = Currency::where('code', $fromCode)->first();
        $main = Currency::active();

        if (! $from || ! $main || (float) $main->rate <= 0) {
            return null;
        }

        return $amount * ((float) $from->rate / (float) $main->rate);
    }
}
