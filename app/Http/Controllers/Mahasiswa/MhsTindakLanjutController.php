<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\Mahasiswa\TindakLanjutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MhsTindakLanjutController extends Controller
{
    protected $tindakLanjutService;

    public function __construct(TindakLanjutService $tindakLanjutService)
    {
        $this->tindakLanjutService = $tindakLanjutService;
    }

    /**
     * Get list of follow-up tickets for the authenticated student.
     */
    public function index()
    {
        try {
            $mahasiswa = Auth::user()->mahasiswa;
            if (!$mahasiswa || !$mahasiswa->akademikmahasiswa) {
                return $this->errorResponse('Data akademik mahasiswa tidak ditemukan', 404);
            }

            $mahasiswaId = $mahasiswa->akademikmahasiswa->id;
            $history = $this->tindakLanjutService->getHistory($mahasiswaId);
            return $this->successResponse($history, 'Riwayat tindak lanjut berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'index');
        }
    }

    /**
     * Get summary cards for the authenticated student.
     */
    public function getCardSummary()
    {
        try {
            $mahasiswa = Auth::user()->mahasiswa;
            if (!$mahasiswa || !$mahasiswa->akademikmahasiswa) {
                return $this->errorResponse('Data akademik mahasiswa tidak ditemukan', 404);
            }

            $mahasiswaId = $mahasiswa->akademikmahasiswa->id;
            $summary = $this->tindakLanjutService->getCardSummary($mahasiswaId);
            return $this->successResponse($summary, 'Ringkasan tindak lanjut berhasil diambil');
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getCardSummary');
        }
    }

    /**
     * Submit a new follow-up ticket.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'kategori' => 'required|in:rekomitmen,pindah_prodi',
                'link' => 'required|url',
                'catatan' => 'nullable|string',
            ]);

            $mahasiswa = Auth::user()->mahasiswa;
            if (!$mahasiswa || !$mahasiswa->akademikmahasiswa) {
                return $this->errorResponse('Data akademik mahasiswa tidak ditemukan', 404);
            }

            $mahasiswaId = $mahasiswa->akademikmahasiswa->id;
            $result = $this->tindakLanjutService->submit($mahasiswaId, $request->only(['kategori', 'link', 'catatan']));

            if ($result['success']) {
                return $this->successResponse($result['data'], $result['message']);
            } else {
                return $this->errorResponse($result['message'], 400);
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'store');
        }
    }

    /**
     * Provide download link/template info.
     */
    public function getTemplate($kategori)
    {
        try {
            if (!in_array($kategori, ['rekomitmen', 'pindah_prodi'])) {
                return $this->errorResponse('Kategori tidak valid', 400);
            }

            // Placeholder for template logic
            // In a real scenario, this would return a static URL or binary file
            return $this->successResponse([
                'template_url' => asset("templates/template_{$kategori}.pdf")
            ], "Info template {$kategori} berhasil diambil");
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getTemplate');
        }
    }
}
