<?php

namespace App\Livewire\Inventory;

use App\Jobs\RefreshInventorySummary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $conditionFilter = '';

    public string $categoryFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $category = '';

    public string $quantity = '';

    public string $unit = '';

    public string $condition = 'baik';

    public string $location = '';

    public string $acquired_at = '';

    public string $acquisition_cost = '';

    public string $acquisition_source = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedConditionFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'conditionFilter', 'categoryFilter']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $item = $this->itemsQuery()->findOrFail($id);

        $this->editingId = $item->id;
        $this->code = $item->code;
        $this->name = $item->name;
        $this->category = $item->category;
        $this->quantity = (string) $item->quantity;
        $this->unit = $item->unit;
        $this->condition = $item->condition;
        $this->location = $item->location;
        $this->acquired_at = $item->acquired_at->format('Y-m-d');
        $this->acquisition_cost = (string) $item->acquisition_cost;
        $this->acquisition_source = $item->acquisition_source;
        $this->showForm = true;
    }

    public function save(): void
    {
        $user = auth()->user();
        $validated = $this->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('inventory_items', 'code')
                    ->where('user_id', $user->id)
                    ->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'unit' => ['required', 'string', 'max:40'],
            'condition' => ['required', Rule::in(['baik', 'rusak ringan', 'rusak berat'])],
            'location' => ['required', 'string', 'max:255'],
            'acquired_at' => ['required', 'date', 'before_or_equal:today'],
            'acquisition_cost' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'acquisition_source' => ['required', 'string', 'max:100'],
        ]);

        if ($this->editingId) {
            $this->itemsQuery()->findOrFail($this->editingId)->update($validated);
        } else {
            $user->inventoryItems()->create($validated);
        }

        $this->invalidateSummary();
        $this->resetForm();
        session()->flash('success', 'Data inventaris berhasil disimpan.');
    }

    public function delete(int $id): void
    {
        $this->itemsQuery()->findOrFail($id)->delete();
        $this->invalidateSummary();
        session()->flash('success', 'Barang dipindahkan ke daftar terhapus.');
    }

    public function restore(int $id): void
    {
        auth()->user()->inventoryItems()->onlyTrashed()->findOrFail($id)->restore();
        $this->invalidateSummary();
        session()->flash('success', 'Barang berhasil dipulihkan.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        $user = auth()->user();
        $summaryKey = 'inventory.summary.'.$user->id;
        $items = $this->itemsQuery()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%')
                        ->orWhere('category', 'like', '%'.$this->search.'%')
                        ->orWhere('location', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->conditionFilter !== '', fn ($query) => $query->where('condition', $this->conditionFilter))
            ->when($this->categoryFilter !== '', fn ($query) => $query->where('category', $this->categoryFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.inventory.index', [
            'items' => $items,
            'summary' => Cache::remember($summaryKey, now()->addMinutes(5), function (): array {
                $summary = $this->itemsQuery()
                    ->selectRaw('COUNT(*) AS total_items, COALESCE(SUM(quantity), 0) AS total_quantity, COALESCE(SUM(quantity * acquisition_cost), 0) AS total_value, COALESCE(SUM(CASE WHEN `condition` <> ? THEN 1 ELSE 0 END), 0) AS attention_items', ['baik'])
                    ->first();

                return [
                    'total_items' => (int) $summary->total_items,
                    'total_quantity' => (int) $summary->total_quantity,
                    'total_value' => (float) $summary->total_value,
                    'attention_items' => (int) $summary->attention_items,
                ];
            }),
            'categories' => $user->inventoryItems()->select('category')->distinct()->orderBy('category')->pluck('category'),
            'hasFilters' => $this->search !== '' || $this->conditionFilter !== '' || $this->categoryFilter !== '',
            'deletedItems' => $user->inventoryItems()->onlyTrashed()->latest('deleted_at')->limit(10)->get(),
        ]);
    }

    private function itemsQuery()
    {
        return auth()->user()->inventoryItems();
    }

    private function invalidateSummary(): void
    {
        $userId = auth()->id();
        Cache::forget('inventory.summary.'.$userId);
        RefreshInventorySummary::dispatch($userId);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'code', 'name', 'category', 'quantity', 'unit',
            'location', 'acquired_at', 'acquisition_cost', 'acquisition_source',
        ]);
        $this->condition = 'baik';
        $this->showForm = false;
        $this->resetValidation();
    }
}
