<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $table = 'error_logs';

    // Sama dengan parent (sti-api) — tidak ada perubahan
    protected $fillable = [
        'user_id',
        'method',
        'url',
        'message',
        'trace',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
