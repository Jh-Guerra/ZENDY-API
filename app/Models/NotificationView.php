<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationView extends Model
{
    use HasFactory;

    protected $primaryKey = 'idNotificationView';

    public $timestamps = false;
}
