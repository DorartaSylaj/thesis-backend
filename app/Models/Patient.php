<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Report;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'symptoms',
        'recovery_days',
        'prescription', // ← ADD THIS
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
