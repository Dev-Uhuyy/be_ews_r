<?php

declare(strict_types=1);

namespace App\Services\Dekan;

use App\Services\EwsServiceBase;
use Illuminate\Support\Facades\Auth;

class EwsService extends EwsServiceBase
{
    protected function getProdiId(): ?int
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return $user->hasRole('super_fakultas') ? $user->prodi_id : null;
    }
}
