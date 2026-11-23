<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([

    'code',
    'name',
    'category',
    'quantity',
    'unit',
    'condition',
    'location',
    'acquired_at',
    'acquisition_cost',
    'acquisition_source',
])]
class InventoryItem extends Model
{
    use SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'acquired_at' => 'date',
            'acquisition_cost' => 'decimal:2',
        ];
    }
}
