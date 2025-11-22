<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * List appointments for authenticated user.
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
     * Show a single appointment
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::with('patient', 'doctor', 'nurse')->findOrFail($id);
            $appointment->patient_name = $appointment->patient
                ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name
                : ($appointment->patient_name ?? 'Pa emër');

            return response()->json($appointment);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Nurse creates a new appointment
     */
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
        ]);

        try {
            $user = $request->user();

            if ($user->role !== 'nurse') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $appointment = Appointment::create([
                'nurse_id' => $user->id,
                'doctor_id' => $request->doctor_id ?? 3,
                'patient_name' => $request->patient_name,
                'patient_id' => $request->patient_id ?? null,
                'appointment_date' => $request->appointment_date,
                'type' => $request->type,
                'status' => 'pending',
                'created_by' => $user->id,
                'updated_by' => null,
            ]);

            // Load patient relation if exists
            if ($appointment->patient_id) {
                $appointment->load('patient');
            }

            return response()->json([
                'message' => 'Termin u krijua me sukses',
                'appointment' => $appointment
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Failed to create appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }


    /**
     * Update appointment (status, notes, or patient info)
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
            'patient_name' => 'sometimes|string',
            'patient_email' => 'sometimes|email',
        ]);

        // Update status or notes
        if ($request->has('status')) {
            $appointment->status = $request->status;
        }
        if ($request->has('notes')) {
            $appointment->notes = $request->notes;
        }

        // Update or create patient if patient info changed
        if ($request->has('patient_name') || $request->has('patient_email')) {
            $patient = null;

            if ($request->patient_email) {
                $patient = Patient::where('email', $request->patient_email)->first();
            }

            if (!$patient && $request->has('patient_name')) {
                $nameParts = explode(' ', $request->patient_name, 2);
                $firstName = $nameParts[0] ?? $request->patient_name;
                $lastName = $nameParts[1] ?? '';
                $patient = Patient::where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->first();
            }

            if (!$patient && $request->has('patient_name')) {
                $nameParts = explode(' ', $request->patient_name, 2);
                $patient = Patient::create([
                    'first_name' => $nameParts[0] ?? $request->patient_name,
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $request->patient_email ?? null,
                ]);
            }

            if ($patient) {
                $appointment->patient_id = $patient->id;
                $appointment->patient_name = $patient->first_name . ' ' . $patient->last_name;
                $appointment->patient_email = $patient->email;
            }
        }

        $appointment->save();

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }

    /**
     * Clear all non-pending appointments (nurse only)
     */
    public function clearNonPendingAppointments()
    {
        try {
            $user = Auth::user();
            if ($user->role !== 'nurse') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            Appointment::where('nurse_id', $user->id)
                ->where('status', '!=', 'pending')
                ->delete();

            return response()->json(['message' => 'All non-pending appointments cleared']);
        } catch (\Throwable $e) {
            \Log::error('Failed to clear non-pending appointments: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
