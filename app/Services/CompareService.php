<?php

namespace App\Services;

use App\Models\CompareItem;
use App\Models\Product;
use App\Support\Shopper;
use Illuminate\Http\Request;

class CompareService
{
    public const MAX_ITEMS = 4;

    public function __construct(private readonly Request $request) {}

    /**
     * @return array<int, int> product ids currently in the comparison
     */
    public function ids(): array
    {
        return $this->query()->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function add(Product $product): array
    {
        if ($this->query()->where('product_id', $product->id)->exists()) {
            return ['success' => true, 'message' => 'Product is already in the comparison.'];
        }

        if ($this->query()->count() >= self::MAX_ITEMS) {
            return ['success' => false, 'message' => 'You can compare up to '.self::MAX_ITEMS.' products at a time.'];
        }

        CompareItem::create([
            'user_id' => Shopper::userId($this->request),
            'session_id' => Shopper::userId($this->request) ? null : Shopper::token($this->request),
            'product_id' => $product->id,
        ]);

        return ['success' => true, 'message' => 'Product added to comparison.'];
    }

    public function remove(int $productId): void
    {
        $this->query()->where('product_id', $productId)->delete();
    }

    public function clear(): void
    {
        $this->query()->delete();
    }

    private function query()
    {
        return CompareItem::query()->ownedBy(Shopper::userId($this->request), Shopper::token($this->request));
    }
}
