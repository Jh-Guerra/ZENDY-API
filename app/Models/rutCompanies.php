<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rutCompanies extends Model
{
    use HasFactory;

    protected $table = "rut_companies";

    public $timestamps = false;
}
