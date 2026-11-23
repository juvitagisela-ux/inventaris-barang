<?php

namespace Tests\Feature;

use App\Jobs\RefreshInventorySummary;
use App\Livewire\Inventory\Index;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_page_requires_authentication(): void
    {
        $this->get('/inventaris')->assertRedirect(route('login'));
    }

    public function test_newly_registered_user_starts_with_empty_inventory(): void
    {
        $this->post('/register', [
            'name' => 'Akun Baru',
            'email' => 'new-owner@example.test',
            'password' => 'Correct-Horse-42!',
            'password_confirmation' => 'Correct-Horse-42!',
        ]);

        Livewire::test(Index::class)
            ->assertSee('Belum ada barang')
            ->assertSee('Tambah barang pertama');

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_seeder_without_demo_owner_does_not_add_items_to_users(): void
    {
        $user = User::factory()->create();

        app(DatabaseSeeder::class)->run();

        $this->assertCount(0, $user->inventoryItems);
    }

    public function test_registration_hashes_password_and_authenticates_user(): void
    {
        $response = $this->post('/register', [
            'name' => 'Inventaris Admin',
            'email' => 'admin@example.test',
            'password' => 'Correct-Horse-42!',
            'password_confirmation' => 'Correct-Horse-42!',
        ]);

        $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

        $response->assertRedirect(route('inventory.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('Correct-Horse-42!', $user->password));
        $this->assertNotSame('Correct-Horse-42!', $user->password);
    }

    public function test_login_rejects_invalid_credentials_and_accepts_valid_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Horse-42!')]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Correct-Horse-42!',
        ])->assertRedirect(route('inventory.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_only_see_and_mutate_their_own_items(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownerItem = $owner->inventoryItems()->create($this->validItem());
        $otherItem = $otherUser->inventoryItems()->create([...$this->validItem(), 'code' => 'INV-OTHER-1', 'name' => 'Barang privat']);

        $this->actingAs($owner);

        Livewire::test(Index::class)
            ->assertSee('Meja kerja')
            ->assertDontSee('Barang privat')
            ->call('delete', $otherItem->id);

        $this->assertNotSoftDeleted('inventory_items', ['id' => $otherItem->id]);
        $this->assertDatabaseHas('inventory_items', ['id' => $ownerItem->id, 'user_id' => $owner->id]);
    }

    public function test_inventory_crud_soft_delete_and_restore(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Queue::fake();

        $data = $this->validItem();
        Livewire::test(Index::class)
            ->set('code', $data['code'])
            ->set('name', $data['name'])
            ->set('category', $data['category'])
            ->set('quantity', (string) $data['quantity'])
            ->set('unit', $data['unit'])
            ->set('condition', $data['condition'])
            ->set('location', $data['location'])
            ->set('acquired_at', $data['acquired_at'])
            ->set('acquisition_cost', (string) $data['acquisition_cost'])
            ->set('acquisition_source', $data['acquisition_source'])
            ->call('save')
            ->assertHasNoErrors();

        $item = $user->inventoryItems()->where('code', 'INV-TEST-1')->firstOrFail();
        $this->assertSame('Meja kerja', $item->name);
        Queue::assertPushed(RefreshInventorySummary::class, fn (RefreshInventorySummary $job) => $job->userId === $user->id);

        Livewire::test(Index::class)
            ->call('edit', $item->id)
            ->set('name', 'Meja kerja besar')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Meja kerja besar', $item->fresh()->name);

        Livewire::test(Index::class)->call('delete', $item->id);
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);

        Livewire::test(Index::class)->call('restore', $item->id);
        $this->assertNotSoftDeleted('inventory_items', ['id' => $item->id]);
    }

    public function test_inventory_validation_rejects_duplicate_code_invalid_quantity_and_future_date(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->inventoryItems()->create($this->validItem());
        $duplicate = $this->validItem();
        $duplicate['name'] = 'Barang kedua';

        Livewire::test(Index::class)
            ->set('code', $duplicate['code'])
            ->set('name', $duplicate['name'])
            ->set('category', $duplicate['category'])
            ->set('quantity', '-1')
            ->set('unit', $duplicate['unit'])
            ->set('condition', $duplicate['condition'])
            ->set('location', $duplicate['location'])
            ->set('acquired_at', now()->addDay()->toDateString())
            ->set('acquisition_cost', (string) $duplicate['acquisition_cost'])
            ->set('acquisition_source', $duplicate['acquisition_source'])
            ->call('save')
            ->assertHasErrors(['code', 'quantity', 'acquired_at']);

        $this->assertDatabaseCount('inventory_items', 1);
    }

    public function test_inventory_summary_cache_and_job_are_scoped_to_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $this->actingAs($user);
        $cacheKey = 'inventory.summary.'.$user->id;
        $otherCacheKey = 'inventory.summary.'.$anotherUser->id;
        Cache::put($cacheKey, ['total_items' => 99, 'total_quantity' => 99, 'total_value' => 9900, 'attention_items' => 0], now()->addHour());
        Cache::put($otherCacheKey, ['total_items' => 7, 'total_quantity' => 7, 'total_value' => 700, 'attention_items' => 0], now()->addHour());

        Livewire::test(Index::class)->assertSee('99');

        Queue::fake();
        $item = $user->inventoryItems()->create($this->validItem());
        Livewire::test(Index::class)->call('delete', $item->id);

        $this->assertSame(0, Cache::get($cacheKey)['total_items']);
        $this->assertSame(7, Cache::get($otherCacheKey)['total_items']);
        Queue::assertPushed(RefreshInventorySummary::class, fn (RefreshInventorySummary $job) => $job->userId === $user->id);
    }

    public function test_summary_job_refreshes_each_users_cache_independently(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $first->inventoryItems()->create($this->validItem());
        $second->inventoryItems()->create([...$this->validItem(), 'code' => 'INV-SECOND-1']);

        (new RefreshInventorySummary)->handle();

        $this->assertSame(1, Cache::get('inventory.summary.'.$first->id)['total_items']);
        $this->assertSame(1, Cache::get('inventory.summary.'.$second->id)['total_items']);
    }

    private function validItem(): array
    {
        return [
            'code' => 'INV-TEST-1',
            'name' => 'Meja kerja',
            'category' => 'Furnitur',
            'quantity' => 4,
            'unit' => 'unit',
            'condition' => 'baik',
            'location' => 'Ruang kerja',
            'acquired_at' => '2025-01-10',
            'acquisition_cost' => 1500000,
            'acquisition_source' => 'Pembelian',
        ];
    }
}
