<?php

namespace App\Support;

use App\Models\SlugHistory;
use App\Models\UrlRedirect;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps old URLs alive: when a slug changes, remember it and redirect the old URL to the new one.
 */
class SlugRedirects
{
    /**
     * @param  string  $entityType  product|category|page
     */
    public static function record(string $entityType, Model $entity, string $oldSlug, string $newSlug, ?string $reason = null): void
    {
        $oldSlug = trim($oldSlug, '/');
        $newSlug = trim($newSlug, '/');

        if ($oldSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        SlugHistory::create([
            'entity_type' => $entityType,
            'entity_id' => $entity->getKey(),
            'old_slug' => $oldSlug,
            'new_slug' => $newSlug,
            'changed_by' => auth()->id(),
            'reason' => $reason,
        ]);

        // A redirect that now points at the old slug must follow the entity to its new URL
        UrlRedirect::query()
            ->where('entity_type', $entityType)
            ->where('new_slug', $oldSlug)
            ->update(['new_slug' => $newSlug]);

        // Moving back to a previously used slug makes its redirect pointless
        UrlRedirect::query()
            ->where('entity_type', $entityType)
            ->where('old_slug', $newSlug)
            ->delete();

        UrlRedirect::updateOrCreate(
            ['entity_type' => $entityType, 'old_slug' => $oldSlug],
            ['entity_id' => $entity->getKey(), 'new_slug' => $newSlug, 'status_code' => 301, 'is_active' => true],
        );
    }
}
