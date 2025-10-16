<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // List all staff
    public function index()
    {
        $staff = User::all();
        return response()->json($staff);
    }

    // Add new staff
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6'
        ]);

        $staff = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Staff created successfully',
            'staff' => $staff
        ]);
    }

    // Update staff
    public function update(Request $request, $id)
    {
        $staff = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6'
        ]);

        if ($request->has('name')) $staff->name = $request->name;
        if ($request->has('email')) $staff->email = $request->email;
        if ($request->has('password')) $staff->password = Hash::make($request->password);

        $staff->save();

        return response()->json([
            'message' => 'Staff updated successfully',
            'staff' => $staff
        ]);
    }

    // Delete staff
    public function destroy($id)
    {
        $staff = User::findOrFail($id);
        $staff->delete();

        return response()->json([
            'message' => 'Staff deleted successfully'
        ]);
    }
}
