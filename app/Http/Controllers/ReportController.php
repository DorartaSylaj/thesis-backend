<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'patient_id' => 'nullable|exists:patients,id',
            'content' => 'required|string',
        ]);

        $report = Report::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Report created successfully',
            'report' => $report
        ]);
    }
}
