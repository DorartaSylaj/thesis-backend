<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * List appointments for authenticated user.
     * Nurses see all their appointments (pending + done).
     * Doctors see all appointments assigned to them, sorted by closest date.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'nurse') {
            $appointments = Appointment::with('patient', 'doctor')
                ->where('nurse_id', $user->id)
                ->orderBy('appointment_date', 'asc')
                ->get()
                ->map(function ($appt) {
                    $appt->patient_name = $appt->patient
                        ? $appt->patient->first_name . ' ' . $appt->patient->last_name
                        : ($appt->patient_name ?? 'Pa emër');
                    return $appt;
                });
        } elseif ($user->role === 'doctor') {
            $appointments = Appointment::where('doctor_id', $user->id)
                ->with('patient', 'nurse')
                ->orderBy('appointment_date', 'asc')
                ->get()
                ->map(function ($appt) {
                    $appt->patient_name = $appt->patient
                        ? $appt->patient->first_name . ' ' . $appt->patient->last_name
                        : ($appt->patient_name ?? 'Pa emër');
                    return $appt;
                });
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $appointments]);
    }

    /**
     * List done appointments for authenticated user
     */
    public function doneAppointments()
    {
        try {
            $user = Auth::user();

            if ($user->role === 'nurse') {
                $appointments = Appointment::where('nurse_id', $user->id)
                    ->where('status', 'done')
                    ->with('patient', 'doctor')
                    ->orderBy('appointment_date', 'asc')
                    ->get()
                    ->map(function ($appt) {
                        $appt->patient_name = $appt->patient
                            ? $appt->patient->first_name . ' ' . $appt->patient->last_name
                            : ($appt->patient_name ?? 'Pa emër');
                        return $appt;
                    });
            } elseif ($user->role === 'doctor') {
                $appointments = Appointment::where('doctor_id', $user->id)
                    ->where('status', 'done')
                    ->with('patient', 'nurse')
                    ->orderBy('appointment_date', 'asc')
                    ->get()
                    ->map(function ($appt) {
                        $appt->patient_name = $appt->patient
                            ? $appt->patient->first_name . ' ' . $appt->patient->last_name
                            : ($appt->patient_name ?? 'Pa emër');
                        return $appt;
                    });
            } else {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json(['data' => $appointments]);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch done appointments: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Show a single appointment
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::with('patient', 'doctor', 'nurse')->findOrFail($id);
            if ($appointment->patient) {
                $appointment->patient_name = $appointment->patient->first_name . ' ' . $appointment->patient->last_name;
            } else {
                $appointment->patient_name = $appointment->patient_name ?? 'Pa emër';
            }
            return response()->json($appointment);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Nurse creates a new appointment
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_name' => 'required|string',
            'appointment_date' => 'required|date',
            'type' => 'required|string',
            'doctor_id' => 'nullable|exists:users,id',
            'patient_email' => 'nullable|email',
        ]);

        try {
            $user = Auth::user();
            if ($user->role !== 'nurse') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $appointment = Appointment::create([
                'nurse_id' => $user->id,
                'doctor_id' => $request->doctor_id ?? 3,
                'patient_name' => $request->patient_name,
                'patient_email' => $request->patient_email ?? null,
                'appointment_date' => $request->appointment_date,
                'type' => $request->type,
                'status' => 'pending',
                'created_by' => $user->id,
                'updated_by' => null,
            ]);

            // Ensure patient_name is consistent
            if ($appointment->patient) {
                $appointment->patient_name = $appointment->patient->first_name . ' ' . $appointment->patient->last_name;
            }

            return response()->json([
                'message' => 'Appointment created successfully',
                'appointment' => $appointment
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to create appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Update appointment status or notes
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $user = $request->user();

        if (!in_array($user->role, ['doctor', 'nurse'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role === 'doctor' && $appointment->doctor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->role === 'nurse' && $appointment->nurse_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'sometimes|string|in:pending,done,cancelled',
            'notes' => 'sometimes|string',
        ]);

        if ($request->has('status')) {
            $appointment->status = $request->status;
        }

        if ($request->has('notes')) {
            $appointment->notes = $request->notes;
        }

        $appointment->save();

        // Ensure patient_name is consistent
        if ($appointment->patient) {
            $appointment->patient_name = $appointment->patient->first_name . ' ' . $appointment->patient->last_name;
        }

        return response()->json([
            'message' => 'Appointment updated',
            'appointment' => $appointment
        ]);
    }

    /**
     * Clear done appointments (nurse only)
     */
    public function clearDoneAppointments()
    {
        try {
            $user = Auth::user();
            if ($user->role !== 'nurse') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            Appointment::where('nurse_id', $user->id)
                ->where('status', 'done')
                ->delete();

            return response()->json(['message' => 'Done appointments cleared']);
        } catch (\Throwable $e) {
            \Log::error('Failed to clear done appointments: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
