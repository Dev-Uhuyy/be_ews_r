<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Services\Mahasiswa\ProfileService;
use App\Http\Controllers\Controller;

/**
 * @tags Mahasiswa - Profile
 */
class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get Profile Mahasiswa
     *
     * Data meliputi:
     * - Nama, NIM, Semester, IPK, SKS Lulus, SKS Tempuh
     * - Status EWS dan Eligible/Non-Eligible beserta alasannya
     * - Data IPS per semester
     * - List matakuliah dengan nilai D dan E
     * - Progress MK Nasional, Fakultas, Prodi
     */
    public function getProfile()
    {
        try {
            $data = $this->profileService->getProfile();

            return $this->successResponse(
                $data,
                'Profile mahasiswa berhasil diambil'
            );
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'getProfile');
        }
    }
}
