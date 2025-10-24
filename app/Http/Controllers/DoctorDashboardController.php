<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class DoctorDashboardController extends Controller
{
    // List all upcoming appointments
    public function upcomingAppointments()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Show all appointments created by the nurse (ignore doctor_id)
        $appointments = Appointment::where('status', '!=', 'done')
            ->with('patient', 'nurse')
            ->orderBy('appointment_date', 'asc')
            ->get();

        return response()->json($appointments);
    }

    // List all done appointments
    public function doneAppointments()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('status', 'done')
            ->with('patient', 'nurse')
            ->orderBy('appointment_date', 'asc')
            ->get();

        return response()->json($appointments);
    }
}
