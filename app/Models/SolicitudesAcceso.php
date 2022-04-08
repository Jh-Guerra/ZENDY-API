<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudesAcceso extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_acceso';

    public $timestamps = false;
    
}
