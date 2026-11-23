<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RefreshInventorySummary implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $userId = null) {}

    public function handle(): void
    {
        $userIds = $this->userId === null
            ? User::query()->pluck('id')
            : collect([$this->userId]);

        foreach ($userIds as $userId) {
            $summary = InventoryItem::query()
                ->where('user_id', $userId)
                ->selectRaw('COUNT(*) AS total_items, COALESCE(SUM(quantity), 0) AS total_quantity, COALESCE(SUM(quantity * acquisition_cost), 0) AS total_value')
                ->first();

            Cache::put('inventory.summary.'.$userId, [
                'total_items' => (int) $summary->total_items,
                'total_quantity' => (int) $summary->total_quantity,
                'total_value' => (float) $summary->total_value,
                'refreshed_at' => now()->toIso8601String(),
            ], now()->addDay());
        }
    }
}
