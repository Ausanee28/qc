<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use App\Support\SchemaCapabilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ProductionLineController extends Controller
{
    private const DEFAULT_LINES_CACHE_KEY = 'master_data.production_lines.default.status_all.per_page_20';
    public const RECEIVE_JOB_ACTIVE_LINES_CACHE_KEY = 'receive_job.lines.active';

    public function index(Request $request)
    {
        if (!SchemaCapabilities::hasTable('Production_Lines')) {
            $request->session()->flash('error', 'Production lines table is not ready. Please run database migrations.');

            return Inertia::render('MasterData/Lines/Index', [
                'filters' => [
                    'search' => '',
                    'status' => 'all',
                    'per_page' => '20',
                ],
                'lines' => [
                    'data' => [],
                    'links' => [],
                    'from' => 0,
                    'to' => 0,
                    'total' => 0,
                ],
            ]);
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $perPage = (int) ($filters['per_page'] ?? 20);
        $currentPage = max(1, (int) $request->integer('page', 1));

        return Inertia::render('MasterData/Lines/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => (string) $perPage,
            ],
            'lines' => fn () => $this->resolveLinesPayload($search, $status, $perPage, $currentPage),
        ]);
    }

    public function store(Request $request)
    {
        if (!SchemaCapabilities::hasTable('Production_Lines')) {
            return redirect()->back()->with('error', 'Production lines table is not ready. Please run database migrations.');
        }

        $validated = $request->validate([
            'line_name' => ['required', 'string', 'max:255', 'unique:Production_Lines,line_name'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ProductionLine::create([
            'line_name' => $validated['line_name'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);
        $this->clearLineCaches();

        return redirect()->back()->with('success', 'Line created successfully.');
    }

    public function update(Request $request, $id)
    {
        if (!SchemaCapabilities::hasTable('Production_Lines')) {
            return redirect()->back()->with('error', 'Production lines table is not ready. Please run database migrations.');
        }

        $line = ProductionLine::findOrFail($id);

        $validated = $request->validate([
            'line_name' => ['required', 'string', 'max:255', 'unique:Production_Lines,line_name,' . $line->line_id . ',line_id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'line_name' => $validated['line_name'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if (array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        }

        $line->update($payload);
        $this->clearLineCaches();

        return redirect()->back()->with('success', 'Line updated successfully.');
    }

    public function setActive(Request $request, $id)
    {
        if (!SchemaCapabilities::hasTable('Production_Lines')) {
            return redirect()->back()->with('error', 'Production lines table is not ready. Please run database migrations.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $line = ProductionLine::findOrFail($id);
        $isActive = (bool) $validated['is_active'];
        $line->update(['is_active' => $isActive]);
        $this->clearLineCaches();

        return redirect()->back()->with('success', $isActive
            ? 'Line activated successfully.'
            : 'Line deactivated successfully.');
    }

    private function resolveLinesPayload(string $search, string $status, int $perPage, int $currentPage)
    {
        if ($currentPage === 1 && $search === '' && $status === 'all' && $perPage === 20) {
            return Cache::remember(
                self::DEFAULT_LINES_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $this->buildLinesPayload($search, $status, $perPage)
            );
        }

        return $this->buildLinesPayload($search, $status, $perPage);
    }

    private function buildLinesPayload(string $search, string $status, int $perPage)
    {
        return ProductionLine::query()
            ->select(['line_id', 'line_name', 'sort_order', 'is_active'])
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($search !== '', fn ($query) => $query->where('line_name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('line_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function clearLineCaches(): void
    {
        Cache::forget(self::DEFAULT_LINES_CACHE_KEY);
        Cache::forget(self::RECEIVE_JOB_ACTIVE_LINES_CACHE_KEY);
    }
}
