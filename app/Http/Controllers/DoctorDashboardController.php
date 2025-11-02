<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class DoctorDashboardController extends Controller
{
    // List all upcoming appointments for this doctor
    public function upcomingAppointments()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('doctor_id', $user->id)
            ->where('status', '!=', 'done')
            ->with('patient', 'nurse')
            ->orderBy('appointment_date', 'asc')
            ->get()
            ->map(function ($appt) {
                // Map patient_name from patient relation
                $appt->patient_name = $appt->patient
                    ? $appt->patient->first_name . ' ' . $appt->patient->last_name
                    : ($appt->patient_name ?? 'Pa emër');
                return $appt;
            });

        return response()->json(['data' => $appointments]);
    }

    // List all done appointments for this doctor
    public function doneAppointments()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('doctor_id', $user->id)
            ->where('status', 'done')
            ->with('patient', 'nurse')
            ->orderBy('appointment_date', 'asc')
            ->get()
            ->map(function ($appt) {
                // Map patient_name from patient relation
                $appt->patient_name = $appt->patient
                    ? $appt->patient->first_name . ' ' . $appt->patient->last_name
                    : ($appt->patient_name ?? 'Pa emër');
                return $appt;
            });

        return response()->json(['data' => $appointments]);
    }
}
