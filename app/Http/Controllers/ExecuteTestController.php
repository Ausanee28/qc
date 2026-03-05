<?php

namespace App\Http\Controllers;

use App\Models\TransactionHeader;
use App\Models\TransactionDetail;
use App\Models\TestMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExecuteTestController extends Controller
{
    public function create()
    {
        $pendingJobs = TransactionHeader::whereNull('return_date')
            ->orderByDesc('receive_date')
            ->get()
            ->map(fn($j) => [
        'transaction_id' => $j->transaction_id,
        'dmc' => $j->dmc,
        'line' => $j->line,
        'detail' => $j->detail,
        ]);

        return Inertia::render('ExecuteTest/Create', [
            'pendingJobs' => $pendingJobs,
            'methods' => TestMethod::orderBy('method_name')->get(),
            'inspectors' => User::orderBy('name')->get(['user_id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:Transaction_Header,transaction_id',
            'method_id' => 'required|exists:Test_Methods,method_id',
            'internal_id' => 'required|exists:Internal_Users,user_id',
            'judgement' => 'required|in:' . \App\Models\TransactionDetail::JUDGEMENT_OK . ',' . \App\Models\TransactionDetail::JUDGEMENT_NG,
            'start_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_date' => 'nullable|date',
            'end_time' => 'nullable',
            'remark' => 'nullable|string|max:255',
        ]);

        $startDt = ($request->start_date && $request->start_time)
            ? $request->start_date . ' ' . $request->start_time . ':00' : null;
        $endDt = ($request->end_date && $request->end_time)
            ? $request->end_date . ' ' . $request->end_time . ':00' : null;

        $durationSec = null;
        if ($startDt && $endDt) {
            $durationSec = strtotime($endDt) - strtotime($startDt);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $startDt, $endDt, $durationSec) {
            TransactionDetail::create([
                'transaction_id' => $request->transaction_id,
                'method_id' => $request->method_id,
                'internal_id' => $request->internal_id,
                'start_time' => $startDt,
                'end_time' => $endDt,
                'duration_sec' => $durationSec,
                'judgement' => $request->judgement,
                'remark' => $request->remark,
            ]);

            TransactionHeader::where('transaction_id', $request->transaction_id)
                ->update(['return_date' => now()]);
        });

        return redirect()->route('execute-test.create')
            ->with('success', "Test result recorded for Job #{$request->transaction_id}!");
    }
}
