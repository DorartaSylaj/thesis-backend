<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller
{
    // List all doctors
    public function index()
    {
        return response()->json(Doctor::all());
    }

    // Create doctor
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:doctors,email',
            'password' => 'required|string|min:6',
        ]);

        $doctor = Doctor::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Doctor created', 'doctor' => $doctor]);
    }

    // Update doctor
    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:doctors,email,' . $id,
            'password' => 'sometimes|string|min:6',
        ]);

        if ($request->has('name')) $doctor->name = $request->name;
        if ($request->has('email')) $doctor->email = $request->email;
        if ($request->has('password')) $doctor->password = Hash::make($request->password);

        $doctor->save();

        return response()->json(['message' => 'Doctor updated', 'doctor' => $doctor]);
    }

    // Delete doctor
    public function destroy($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json(['message' => 'Doctor deleted']);
    }
}
