<?php

namespace App\Http\Controllers;

use App\Models\TestMethod;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestMethodController extends Controller
{
    public function index()
    {
        $testMethods = TestMethod::with('equipment')->orderBy('method_name')->get();
        $equipments = Equipment::orderBy('equipment_name')->get();
        
        return Inertia::render('MasterData/TestMethods/Index', [
            'testMethods' => $testMethods,
            'equipments' => $equipments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'method_name' => 'required|string|max:255',
            'equipment_id' => 'required|integer|exists:Equipments,equipment_id',
        ]);

        TestMethod::create($validated);

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

        return redirect()->back()->with('success', 'Test Method updated successfully.');
    }

    public function destroy($id)
    {
        $method = TestMethod::findOrFail($id);
        
        if ($method->transactionDetails()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete test method that has been used in transactions.');
        }

        $method->delete();

        return redirect()->back()->with('success', 'Test Method deleted successfully.');
    }
}
