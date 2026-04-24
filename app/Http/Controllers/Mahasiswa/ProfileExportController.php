<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Services\Mahasiswa\Export\ProfileExportService;
use App\Http\Controllers\Controller;

/**
 * @tags Mahasiswa - Export
 */
class ProfileExportController extends Controller
{
    public function __construct(
        private ProfileExportService $profileExport
    ) {}

    /**
     * Export Mahasiswa Profile to XLSX
     *
     * @tags Mahasiswa - Export
     */
    public function exportProfile()
    {
        try {
            return $this->profileExport->exportProfile();
        } catch (\Exception $e) {
            return $this->exceptionError($e, 'exportProfile');
        }
    }
}