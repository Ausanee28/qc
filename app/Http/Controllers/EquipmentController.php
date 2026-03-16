<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\TestMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = Equipment::orderBy('equipment_name')->get();
        return Inertia::render('MasterData/Equipments/Index', [
            'equipments' => $equipments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255|unique:Equipments,equipment_name',
        ]);

        Equipment::create($validated);

        return redirect()->back()->with('success', 'Equipment created successfully.');
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);

        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255|unique:Equipments,equipment_name,' . $equipment->equipment_id . ',equipment_id',
        ]);

        $equipment->update($validated);

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

        return redirect()->back()->with('success', 'Equipment deleted successfully.');
    }
}
