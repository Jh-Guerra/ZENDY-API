<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenSoftnet extends Model
{
    use HasFactory;

    protected $table = "token_softnet";

    public $timestamps = false;
}
