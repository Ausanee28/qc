<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\ExternalUser;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceiveJobController extends Controller
{
    public function create()
    {
        return Inertia::render('ReceiveJob/Create', [
            'externals' => ExternalUser::orderBy('external_name')->get(),
            'internals' => User::orderBy('name')->get(['user_id', 'name']),
            'equipments' => Equipment::orderBy('equipment_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'external_id' => 'required|exists:External_Users,external_id',
            'internal_id' => 'required|exists:Internal_Users,user_id',
            'equipment_id' => 'required|exists:Equipments,equipment_id',
            'dmc' => 'nullable|string',
            'line' => 'nullable|string',
        ]);

        $job = TransactionHeader::create([
            'external_id' => $request->external_id,
            'internal_id' => $request->internal_id,
            'equipment_id' => $request->equipment_id,
            'dmc' => $request->dmc,
            'line' => $request->line,
            'receive_date' => now(),
        ]);

        return redirect()->route('receive-job.create')
            ->with('success', "Job #{$job->transaction_id} created successfully!");
    }
}
