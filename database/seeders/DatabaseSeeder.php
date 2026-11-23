<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ownerEmail = env('INVENTORY_DEMO_USER_EMAIL');

        if (! $ownerEmail) {
            return;
        }

        $owner = User::query()->where('email', $ownerEmail)->first();

        if (! $owner) {
            throw new RuntimeException('INVENTORY_DEMO_USER_EMAIL must match an existing account.');
        }

        foreach ([
            [
                'code' => 'INV-0001',
                'name' => 'Laptop Administrasi',
                'category' => 'Elektronik',
                'quantity' => 5,
                'unit' => 'unit',
                'condition' => 'baik',
                'location' => 'Ruang Administrasi',
                'acquired_at' => '2025-01-15',
                'acquisition_cost' => 8500000,
                'acquisition_source' => 'Pembelian',
            ],
            [
                'code' => 'INV-0002',
                'name' => 'Kertas A4',
                'category' => 'Alat Tulis Kantor',
                'quantity' => 20,
                'unit' => 'rim',
                'condition' => 'baik',
                'location' => 'Gudang ATK',
                'acquired_at' => '2025-02-01',
                'acquisition_cost' => 65000,
                'acquisition_source' => 'Pembelian',
            ],
            [
                'code' => 'INV-0003',
                'name' => 'Proyektor Ruang Rapat',
                'category' => 'Elektronik',
                'quantity' => 1,
                'unit' => 'unit',
                'condition' => 'rusak ringan',
                'location' => 'Ruang Rapat',
                'acquired_at' => '2024-08-12',
                'acquisition_cost' => 4200000,
                'acquisition_source' => 'Hibah',
            ],
        ] as $item) {
            $record = $owner->inventoryItems()->withTrashed()->updateOrCreate(
                ['code' => $item['code']],
                $item,
            );

            if ($record->trashed()) {
                $record->restore();
            }
        }

        Cache::forget('inventory.summary.'.$owner->id);
    }
}
