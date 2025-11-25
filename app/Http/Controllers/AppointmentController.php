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

                    $translated = [
                        'pending' => 'Në pritje',
                        'done' => 'Përfunduar',
                        'cancelled' => 'Anuluar'
                    ];
                    $appt->status_label = $translated[$appt->status] ?? $appt->status;

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

                    $translated = [
                        'pending' => 'Në pritje',
                        'done' => 'Përfunduar',
                        'cancelled' => 'Anuluar'
                    ];
                    $appt->status_label = $translated[$appt->status] ?? $appt->status;

                    return $appt;
                });
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $appointments]);
    }

    /**
     * Show a single appointment.
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
     * Nurse creates a new appointment and links patient.
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
            $user = $request->user();

            if ($user->role !== 'nurse') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Link or create patient safely
            $patient = null;
            if ($request->filled('patient_email')) {
                $patient = Patient::firstOrCreate(
                    ['email' => $request->patient_email],
                    [
                        'first_name' => explode(' ', $request->patient_name)[0] ?? $request->patient_name,
                        'last_name' => explode(' ', $request->patient_name, 2)[1] ?? '',
                    ]
                );
            } elseif ($request->filled('patient_name')) {
                $nameParts = explode(' ', $request->patient_name, 2);
                $patient = Patient::firstOrCreate(
                    ['first_name' => $nameParts[0], 'last_name' => $nameParts[1] ?? ''],
                    ['email' => $request->patient_email ?? null]
                );
            }

            // Create appointment
            $appointment = Appointment::create([
                'nurse_id' => $user->id,
                'doctor_id' => $request->doctor_id ?? 3,
                'patient_name' => $request->patient_name,
                'patient_email' => $request->patient_email ?? null,
                'patient_id' => $patient->id ?? null,
                'appointment_date' => $request->appointment_date,
                'type' => $request->type,
                'status' => 'pending',
                'created_by' => $user->id,
                'updated_by' => null,
            ]);

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
     * Update appointment (doctor or nurse) and link patient.
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
            'patient_email' => 'sometimes|email|nullable',
            'appointment_date' => 'sometimes|date',   // ← added
            'type' => 'sometimes|string',            // ← added
        ]);

        // Update appointment fields
        if ($request->has('status')) $appointment->status = $request->status;
        if ($request->has('notes')) $appointment->notes = $request->notes;
        if ($request->has('appointment_date')) {
            $appointment->appointment_date = date('Y-m-d H:i:s', strtotime($request->appointment_date));
        }

        if ($request->has('type')) $appointment->type = $request->type;

        // Patient linking logic
        if ($request->filled('patient_name') || $request->filled('patient_email')) {
            $patient = null;

            if ($request->filled('patient_email')) {
                $patient = Patient::firstOrCreate(
                    ['email' => $request->patient_email],
                    [
                        'first_name' => explode(' ', $request->patient_name)[0] ?? $request->patient_name,
                        'last_name' => explode(' ', $request->patient_name, 2)[1] ?? ''
                    ]
                );
            } elseif ($request->filled('patient_name')) {
                $nameParts = explode(' ', $request->patient_name, 2);
                $patient = Patient::firstOrCreate(
                    ['first_name' => $nameParts[0], 'last_name' => $nameParts[1] ?? ''],
                    ['email' => $request->patient_email ?? null]
                );
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
     * Clear all non-pending appointments (nurse only).
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
    public function deleteAllAppointments()
    {
        try {
            // Only allow nurses to do this
            $user = Auth::user();
            if ($user->role !== 'nurse') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Disable foreign key checks temporarily
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Delete all appointments
            \App\Models\Appointment::truncate();

            // Re-enable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return response()->json(['message' => 'Të gjitha terminet janë fshirë me sukses!'], 200);
        } catch (\Exception $e) {
            \Log::error('Failed to delete all appointments: ' . $e->getMessage());
            return response()->json(['error' => 'Ndodhi një gabim gjatë fshirjes së termineve'], 500);
        }
    }
}
