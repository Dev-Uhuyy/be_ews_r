<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdiUser extends Model
{
    protected $table = 'prodi_user';

    protected $fillable = [
        'user_id',
        'prodi_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id', 'id');
    }
}
