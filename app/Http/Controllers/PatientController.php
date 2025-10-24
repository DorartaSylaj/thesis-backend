<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    /**
     * List all patients.
     * Nurses should see all patients.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'nurse') {
            $patients = Patient::all(); // Nurses see all patients
        } else {
            $patients = Patient::all(); // Doctors also see all patients (unchanged)
        }

        return response()->json(['data' => $patients]); // wrap in 'data' for consistency
    }

    /**
     * Store a new patient.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'symptoms' => 'required|string',
            'recovery_days' => 'nullable|integer',
        ]);

        try {
            $patient = Patient::create($validated);
            return response()->json([
                'message' => 'Patient created successfully',
                'patient' => $patient,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Failed to create patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Show a single patient.
     */
    public function show($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            return response()->json($patient);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Update patient data.
     */
    public function update(Request $request, $id)
    {
        try {
            $patient = Patient::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'birth_date' => 'sometimes|required|date',
                'symptoms' => 'sometimes|required|string',
                'recovery_days' => 'nullable|integer',
            ]);

            $patient->update($validated);

            return response()->json([
                'message' => 'Patient updated successfully',
                'patient' => $patient,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to update patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Delete a patient.
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient->delete();

            return response()->json(['message' => 'Patient deleted successfully']);
        } catch (\Throwable $e) {
            \Log::error('Failed to delete patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
