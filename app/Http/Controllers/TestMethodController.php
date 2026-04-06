<?php

namespace App\Http\Controllers;

use App\Models\TestMethod;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TestMethodController extends Controller
{
    private const DEFAULT_TEST_METHODS_CACHE_KEY = 'master_data.test_methods.default.per_page_20';

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 20);
        $currentPage = max(1, (int) $request->integer('page', 1));

        return Inertia::render('MasterData/TestMethods/Index', [
            'equipments' => fn () => Equipment::query()->select(['equipment_id', 'equipment_name'])->orderBy('equipment_name')->get(),
            'filters' => [
                'search' => $search,
                'per_page' => (string) $perPage,
            ],
            'testMethods' => fn () => $this->resolveTestMethodsPayload($search, $perPage, $currentPage),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'method_name' => 'required|string|max:255',
            'equipment_id' => 'required|integer|exists:Equipments,equipment_id',
        ]);

        TestMethod::create($validated);
        Cache::forget('execute_test.methods');
        Cache::forget(self::DEFAULT_TEST_METHODS_CACHE_KEY);

        return redirect()->back()->with('success', 'Test Method created successfully.');
    }

    public function update(Request $request, $id)
    {
        $method = TestMethod::findOrFail($id);

        $validated = $request->validate([
            'method_name' => 'required|string|max:255',
            'equipment_id' => 'required|integer|exists:Equipments,equipment_id',
        ]);

        $method->update($validated);
        Cache::forget('execute_test.methods');
        Cache::forget(self::DEFAULT_TEST_METHODS_CACHE_KEY);

        return redirect()->back()->with('success', 'Test Method updated successfully.');
    }

    public function destroy($id)
    {
        $method = TestMethod::findOrFail($id);
        
        if ($method->transactionDetails()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete test method that has been used in transactions.');
        }

        $method->delete();
        Cache::forget('execute_test.methods');
        Cache::forget(self::DEFAULT_TEST_METHODS_CACHE_KEY);

        return redirect()->back()->with('success', 'Test Method deleted successfully.');
    }

    private function resolveTestMethodsPayload(string $search, int $perPage, int $currentPage)
    {
        if ($this->shouldCacheDefaultTestMethodsPayload($search, $perPage, $currentPage)) {
            return Cache::remember(
                self::DEFAULT_TEST_METHODS_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildTestMethodsPayload($search, $perPage)
            );
        }

        return $this->buildTestMethodsPayload($search, $perPage);
    }

    private function shouldCacheDefaultTestMethodsPayload(string $search, int $perPage, int $currentPage): bool
    {
        return $currentPage === 1 && $search === '' && $perPage === 20;
    }

    private function buildTestMethodsPayload(string $search, int $perPage)
    {
        return TestMethod::query()
            ->select(['method_id', 'method_name', 'equipment_id'])
            ->with(['equipment:equipment_id,equipment_name'])
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
}
