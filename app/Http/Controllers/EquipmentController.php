<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\TestMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    private const DEFAULT_EQUIPMENTS_CACHE_KEY = 'master_data.equipments.default.per_page_20';

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 20);
        $currentPage = max(1, (int) $request->integer('page', 1));

        return Inertia::render('MasterData/Equipments/Index', [
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
            ],
            'equipments' => fn () => $this->resolveEquipmentsPayload($search, $perPage, $currentPage),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255|unique:Equipments,equipment_name',
        ]);

        Equipment::create($validated);
        Cache::forget(self::DEFAULT_EQUIPMENTS_CACHE_KEY);
        Cache::forget('master_data.test_methods.default.per_page_20');

        return redirect()->back()->with('success', 'Equipment created successfully.');
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255|unique:Equipments,equipment_name,' . $equipment->equipment_id . ',equipment_id',
        ]);

        $equipment->update($validated);
        Cache::forget(self::DEFAULT_EQUIPMENTS_CACHE_KEY);
        Cache::forget('master_data.test_methods.default.per_page_20');

        return redirect()->back()->with('success', 'Equipment updated successfully.');
    }

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);

        // Check if used in any Test Method
        if (TestMethod::where('equipment_id', $equipment->equipment_id)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete equipment that is assigned to test methods.');
        }

        $equipment->delete();
        Cache::forget(self::DEFAULT_EQUIPMENTS_CACHE_KEY);
        Cache::forget('master_data.test_methods.default.per_page_20');

        return redirect()->back()->with('success', 'Equipment deleted successfully.');
    }

    private function resolveEquipmentsPayload(string $search, int $perPage, int $currentPage)
    {
        if ($this->shouldCacheDefaultEquipmentsPayload($search, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_EQUIPMENTS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildEquipmentsPayload($search, $perPage)
            );
        }

        return $this->buildEquipmentsPayload($search, $perPage);
    }

    private function shouldCacheDefaultEquipmentsPayload(string $search, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $perPage === 20;
    }

    private function buildEquipmentsPayload(string $search, int $perPage)
    {
        return Equipment::query()
            ->select(['equipment_id', 'equipment_name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('equipment_name', 'like', "%{$search}%");
            })
            ->orderBy('equipment_name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
