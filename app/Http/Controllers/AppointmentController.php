<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // List all appointments (for nurse and doctor)
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'nurse') {
            // Show only appointments created by this nurse
            return Appointment::where('nurse_id', $user->id)->get();
        }

        if ($user->role === 'doctor') {
            // Show all appointments for doctor in next 5 days
            return Appointment::whereBetween('appointment_date', [
                now(),
                now()->addDays(5)
            ])->get();
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Show single appointment
    public function show($id)
    {
        $appointment = Appointment::findOrFail($id);
        return $appointment;
    }

    // Nurse creates appointment
    public function store(Request $request)
    {
        $request->validate([
            'patient_name' => 'required|string',
            'patient_email' => 'nullable|email',
            'appointment_date' => 'required|date',
            'type' => 'required|string'
        ]);

        $appointment = Appointment::create([
            'nurse_id' => Auth::id(), // nurse creating appointment
            'doctor_id' => $request->doctor_id ?? null,
            'patient_name' => $request->patient_name,
            'patient_email' => $request->patient_email,
            'appointment_date' => $request->appointment_date,
            'type' => $request->type,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment
        ]);
    }

    // Doctor updates appointment status and report
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,done',
            'report' => 'nullable|string'
        ]);

        $appointment = Appointment::findOrFail($id);

        if (Auth::user()->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->update([
            'status' => $request->status,
            'report' => $request->report ?? $appointment->report,
            'updated_by' => Auth::id(), // doctor updating
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }

    // List all done appointments for nurse
    public function doneAppointments()
    {
        $user = Auth::user();

        if ($user->role !== 'nurse') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return Appointment::where('nurse_id', $user->id)
            ->where('status', 'done')
            ->get();
    }

    // Clear done appointments for nurse (end of shift)
    public function clearDoneAppointments()
    {
        $user = Auth::user();

        if ($user->role !== 'nurse') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Appointment::where('nurse_id', $user->id)
            ->where('status', 'done')
            ->delete();

        return response()->json(['message' => 'Done appointments cleared']);
    }
}
