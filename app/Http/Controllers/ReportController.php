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
            'appointment_id' => 'required|exists:appointments,id',
            'content' => 'required|string',
        ]);

        $appointment = \App\Models\Appointment::with('patient')->findOrFail($request->appointment_id);

        // 1️⃣ If appointment has NO patient → create one now
        if (!$appointment->patient_id) {
            $nameParts = explode(' ', $appointment->patient_name, 2);

            $patient = \App\Models\Patient::create([
                'first_name' => $nameParts[0] ?? $appointment->patient_name,
                'last_name' => $nameParts[1] ?? '',
                'email' => null
            ]);

            // Attach new patient to appointment
            $appointment->patient_id = $patient->id;
            $appointment->save();
        } else {
            $patient = $appointment->patient;
        }

        // 2️⃣ Create report
        $report = Report::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'content' => $request->content,
        ]);

        // 3️⃣ Mark appointment as completed
        $appointment->status = 'done';
        $appointment->save();

        return response()->json([
            'message' => 'Raporti u ruajt me sukses',
            'report' => $report
        ]);
    }
}
