<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\PeringatanService;

/**
 * @tags Mahasiswa - Peringatan
 */
class PeringatanController extends Controller
{
    protected $peringatanService;

    public function __construct(PeringatanService $peringatanService)
    {
        $this->peringatanService = $peringatanService;
    }

    public function getPeringatan()
    {
        try {
            $user = request()->user();
            $peringatan = $this->peringatanService->getPeringatan($user->id);

            return $this->successResponse($peringatan, 'Peringatan berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getPeringatan');
        }
    }
}
