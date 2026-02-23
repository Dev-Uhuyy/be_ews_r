<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $table = 'error_logs';
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'message',
        'trace',
        'payload',
    ];

}
