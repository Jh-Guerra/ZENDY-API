<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyHorario extends Model
{
    use HasFactory;

    protected $table = "companies_horarios";

    public $timestamps = false;

    protected $fillable = [
        'Dias',
        'MedioDia',
        'HorarioIngreso',
        'HorarioSalida',
        'HorarioIngresoMD',
        'HorarioSalidaMD'
    ];
}
