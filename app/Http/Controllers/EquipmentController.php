<?php

namespace App\Http\Controllers;

use App\Events\DashboardDataChanged;
use App\Models\Equipment;
use App\Models\TestMethod;
use App\Support\DashboardCache;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    private const DEFAULT_EQUIPMENTS_CACHE_KEY = 'master_data.equipments.default.status_all.per_page_20';

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $perPage = (int) ($filters['per_page'] ?? 20);
        $currentPage = max(1, (int) $request->integer('page', 1));
        $hasIsActive = SchemaCapabilities::hasColumn('Equipments', 'is_active');

        if (!$hasIsActive) {
            $status = 'all';
        }

        return Inertia::render('MasterData/Equipments/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => (string) $perPage,
            ],
            'equipments' => fn () => $this->resolveEquipmentsPayload($search, $status, $perPage, $currentPage, $hasIsActive),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255|unique:Equipments,equipment_name',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'equipment_name' => $validated['equipment_name'],
        ];

        if (SchemaCapabilities::hasColumn('Equipments', 'is_active')) {
            $payload['is_active'] = (bool) ($validated['is_active'] ?? true);
        }

        Equipment::create($payload);
        $this->clearEquipmentCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', 'Equipment created successfully.');
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255|unique:Equipments,equipment_name,' . $equipment->equipment_id . ',equipment_id',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'equipment_name' => $validated['equipment_name'],
        ];

        if (SchemaCapabilities::hasColumn('Equipments', 'is_active') && array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        }

        $equipment->update($payload);
        $this->clearEquipmentCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', 'Equipment updated successfully.');
    }

    public function setActive(Request $request, $id)
    {
        if (!SchemaCapabilities::hasColumn('Equipments', 'is_active')) {
            return redirect()->back()->with('error', 'Status toggle is unavailable on this database.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $equipment = Equipment::findOrFail($id);
        $isActive = (bool) $validated['is_active'];
        $equipment->update(['is_active' => $isActive]);
        $this->clearEquipmentCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', $isActive
            ? 'Equipment activated successfully.'
            : 'Equipment deactivated successfully.');
    }

    public function destroy($id)
    {
        if (SchemaCapabilities::hasColumn('Equipments', 'is_active')) {
            return redirect()->back()->with('error', 'Delete is disabled. Please change status instead.');
        }

        $equipment = Equipment::findOrFail($id);

        // Check if used in any Test Method
        if (TestMethod::where('equipment_id', $equipment->equipment_id)->exists()) {
            return redirect()->back()->with('error', 'Cannot delete equipment that is assigned to test methods.');
        }

        $equipment->delete();
        $this->clearEquipmentCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', 'Equipment deleted successfully.');
    }

    private function resolveEquipmentsPayload(string $search, string $status, int $perPage, int $currentPage, bool $hasIsActive)
    {
        if ($this->shouldCacheDefaultEquipmentsPayload($search, $status, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_EQUIPMENTS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildEquipmentsPayload($search, $status, $perPage, $hasIsActive)
            );
        }

        return $this->buildEquipmentsPayload($search, $status, $perPage, $hasIsActive);
    }

    private function shouldCacheDefaultEquipmentsPayload(string $search, string $status, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $status === 'all' && $perPage === 20;
    }

    private function buildEquipmentsPayload(string $search, string $status, int $perPage, bool $hasIsActive)
    {
        return Equipment::query()
            ->select(['equipment_id', 'equipment_name'])
            ->when($hasIsActive, function ($query) use ($status) {
                $query->addSelect('is_active');

                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when(!$hasIsActive, fn ($query) => $query->selectRaw('1 as is_active'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where('equipment_name', 'like', "%{$search}%");
            })
            ->orderBy('equipment_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function clearEquipmentCaches(): void
    {
        Cache::forget(self::DEFAULT_EQUIPMENTS_CACHE_KEY);
        Cache::forget('master_data.test_methods.default.status_all.per_page_20');
    }

    private function refreshDashboardRealtime(): void
    {
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();
    }
}
