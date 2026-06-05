<?php

declare(strict_types=1);

namespace App\Services\SuperFakultas;

use App\Services\EwsServiceBase;

class EwsService extends EwsServiceBase
{
    protected function getProdiId(): ?int
    {
        // Super fakultas mencakup seluruh prodi di fakultas → scope selalu null
        // (tanpa batas prodi), terlepas dari nilai prodi_id user.
        return null;
    }
}
