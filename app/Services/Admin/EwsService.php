<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Services\EwsServiceBase;
use Illuminate\Support\Facades\Auth;

class EwsService extends EwsServiceBase
{
    protected function getProdiId(): ?int
    {
        return Auth::user()?->prodi_id;
    }
}
