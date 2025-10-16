<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nurse;
use Illuminate\Support\Facades\Hash;

class NurseController extends Controller
{
    public function index()
    {
        return response()->json(Nurse::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:nurses,email',
            'password' => 'required|string|min:6'
        ]);

        $nurse = Nurse::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Nurse created', 'nurse' => $nurse]);
    }

    public function update(Request $request, $id)
    {
        $nurse = Nurse::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:nurses,email,' . $id,
            'password' => 'sometimes|string|min:6'
        ]);

        if ($request->has('name')) $nurse->name = $request->name;
        if ($request->has('email')) $nurse->email = $request->email;
        if ($request->has('password')) $nurse->password = Hash::make($request->password);

        $nurse->save();

        return response()->json(['message' => 'Nurse updated', 'nurse' => $nurse]);
    }

    public function destroy($id)
    {
        $nurse = Nurse::findOrFail($id);
        $nurse->delete();

        return response()->json(['message' => 'Nurse deleted']);
    }
}
