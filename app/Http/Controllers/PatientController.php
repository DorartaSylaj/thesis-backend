<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    // List all patients
    public function index()
    {
        $patients = Patient::all(); // Both nurses and doctors see all patients
        return response()->json(['data' => $patients]);
    }

    // Create a new patient
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'symptoms' => 'nullable|string',
            'recovery_days' => 'nullable|integer',
            'prescription' => 'nullable|string',
        ]);

        try {
            $patient = Patient::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'birth_date' => $validated['birth_date'],
                'symptoms' => $validated['symptoms'] ?? '',
                'recovery_days' => $validated['recovery_days'] ?? null,
                'prescription' => $validated['prescription'] ?? '',
            ]);

            return response()->json([
                'message' => 'Patient created successfully',
                'patient' => $patient,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('Failed to create patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    // Show a single patient
    public function show($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            return response()->json([
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'birth_date' => $patient->birth_date,
                'symptoms' => $patient->symptoms,
                'recovery_days' => $patient->recovery_days,
                'prescription' => $patient->prescription,
                'created_at' => $patient->created_at,
                'updated_at' => $patient->updated_at,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    // Update patient
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
                'prescription' => 'nullable|string',
            ]);

            $patient->update($validated);

            return response()->json([
                'message' => 'Patient updated successfully',
                'data' => $patient,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to update patient: ' . $e->getMessage());
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $patient = Patient::findOrFail($id);

            if ($patient->appointments()->count() > 0) {
                return response()->json([
                    'message' => 'Nuk mund të fshihet pacienti sepse ka termine të lidhura.'
                ], 400);
            }

            // Delete related reports
            $patient->reports()->delete();

            $patient->delete();

            return response()->json(['message' => 'Patient deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pacienti nuk u gjet'], 404);
        } catch (\Throwable $e) {
            \Log::error('Failed to delete patient: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
