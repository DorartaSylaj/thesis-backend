<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;

class NurseDashboardController extends Controller
{
    public function index()
    {
        $nurseId = auth()->user()->id; // Make sure nurse login is working
        $appointments = Appointment::with('patient', 'doctor')
            ->where('nurse_id', $nurseId)
            ->orderBy('appointment_date', 'asc')
            ->get();

        return view('nurse.dashboard', compact('appointments'));
    }
}
