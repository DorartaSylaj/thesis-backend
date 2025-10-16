<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_name',
        'patient_email',
        'appointment_date',
        'type',
        'status',
        'nurse_id',
        'doctor_id',
        'report',
    ];
}
