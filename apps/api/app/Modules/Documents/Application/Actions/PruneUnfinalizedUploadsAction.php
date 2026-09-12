<?php

declare(strict_types=1);

namespace App\Modules\Documents\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * PruneUnfinalizedUploadsAction (Architecture §5.6 (8), DoD TASK-057).
 * Cleans up unfinalized temporary upload files and intents older than 24 hours.
 */
final class PruneUnfinalizedUploadsAction
{
    /**
     * @return int Number of pruned upload intents
     */
    public function execute(): int
    {
        /** @var list<array{id: string, expires_at: int}> $tracker */
        $tracker = Cache::get('unfinalized_upload_intents', []);
        $now = CarbonImmutable::now()->timestamp;
        $prunedCount = 0;
        $remaining = [];

        foreach ($tracker as $item) {
            $uploadId = $item['id'];
            $expiresAt = $item['expires_at'];

            if ($expiresAt <= $now) {
                if (isset($item['temp_storage_key']) && is_string($item['temp_storage_key'])) {
                    Storage::disk('documents')->delete($item['temp_storage_key']);
                }

                Cache::forget("upload_intent:{$uploadId}");
                $prunedCount++;
            } else {
                $remaining[] = $item;
            }
        }

        Cache::forever('unfinalized_upload_intents', $remaining);

        return $prunedCount;
    }
}
