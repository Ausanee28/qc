<?php

namespace App\Http\Controllers;

use App\Events\DashboardDataChanged;
use App\Models\TestMethod;
use App\Models\Equipment;
use App\Support\DashboardCache;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TestMethodController extends Controller
{
    private const DEFAULT_TEST_METHODS_CACHE_KEY = 'master_data.test_methods.default.status_all.per_page_20';

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
        $hasIsActive = SchemaCapabilities::hasColumn('Test_Methods', 'is_active');

        if (!$hasIsActive) {
            $status = 'all';
        }

        return Inertia::render('MasterData/TestMethods/Index', [
            'equipments' => fn () => Equipment::query()
                ->select(['equipment_id', 'equipment_name'])
                ->orderBy('equipment_name')
                ->get(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => (string) $perPage,
            ],
            'testMethods' => fn () => $this->resolveTestMethodsPayload($search, $status, $perPage, $currentPage, $hasIsActive),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'method_name' => 'required|string|max:255',
            'equipment_id' => 'required|integer|exists:Equipments,equipment_id',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'method_name' => $validated['method_name'],
            'equipment_id' => $validated['equipment_id'],
        ];

        if (SchemaCapabilities::hasColumn('Test_Methods', 'is_active')) {
            $payload['is_active'] = (bool) ($validated['is_active'] ?? true);
        }

        TestMethod::create($payload);
        $this->clearTestMethodCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', 'Test Method created successfully.');
    }

    public function update(Request $request, $id)
    {
        $method = TestMethod::findOrFail($id);

        $validated = $request->validate([
            'method_name' => 'required|string|max:255',
            'equipment_id' => 'required|integer|exists:Equipments,equipment_id',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'method_name' => $validated['method_name'],
            'equipment_id' => $validated['equipment_id'],
        ];

        if (SchemaCapabilities::hasColumn('Test_Methods', 'is_active') && array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        }

        $method->update($payload);
        $this->clearTestMethodCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', 'Test Method updated successfully.');
    }

    public function setActive(Request $request, $id)
    {
        if (!SchemaCapabilities::hasColumn('Test_Methods', 'is_active')) {
            return redirect()->back()->with('error', 'Status toggle is unavailable on this database.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $method = TestMethod::findOrFail($id);
        $isActive = (bool) $validated['is_active'];
        $method->update(['is_active' => $isActive]);
        $this->clearTestMethodCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', $isActive
            ? 'Test method activated successfully.'
            : 'Test method deactivated successfully.');
    }

    public function destroy($id)
    {
        if (SchemaCapabilities::hasColumn('Test_Methods', 'is_active')) {
            return redirect()->back()->with('error', 'Delete is disabled. Please change status instead.');
        }

        $method = TestMethod::findOrFail($id);
        
        if ($method->transactionDetails()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete test method that has been used in transactions.');
        }

        $method->delete();
        $this->clearTestMethodCaches();
        $this->refreshDashboardRealtime();

        return redirect()->back()->with('success', 'Test Method deleted successfully.');
    }

    private function resolveTestMethodsPayload(string $search, string $status, int $perPage, int $currentPage, bool $hasIsActive)
    {
        if ($this->shouldCacheDefaultTestMethodsPayload($search, $status, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_TEST_METHODS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildTestMethodsPayload($search, $status, $perPage, $hasIsActive)
            );
        }

        return $this->buildTestMethodsPayload($search, $status, $perPage, $hasIsActive);
    }

    private function shouldCacheDefaultTestMethodsPayload(string $search, string $status, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $status === 'all' && $perPage === 20;
    }

    private function buildTestMethodsPayload(string $search, string $status, int $perPage, bool $hasIsActive)
    {
        return TestMethod::query()
            ->select(['method_id', 'method_name', 'equipment_id'])
            ->with(['equipment:equipment_id,equipment_name'])
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
                $query->where(function ($methodQuery) use ($search) {
                    $methodQuery
                        ->where('method_name', 'like', "%{$search}%")
                        ->orWhereHas('equipment', function ($equipmentQuery) use ($search) {
                            $equipmentQuery->where('equipment_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('method_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function clearTestMethodCaches(): void
    {
        Cache::forget('execute_test.methods');
        Cache::forget(self::DEFAULT_TEST_METHODS_CACHE_KEY);
    }

    private function refreshDashboardRealtime(): void
    {
        DashboardCache::flush();
        DashboardDataChanged::dispatchSafely();
    }
}
