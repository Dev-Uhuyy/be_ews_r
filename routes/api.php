<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Kaprodi\EwsController as KaprodiEwsController;
use App\Http\Controllers\Kaprodi\DashboardController as KaprodiDashboardController;
use App\Http\Controllers\Dekan\EwsController as DekanEwsController;
use App\Http\Controllers\Dekan\DekanDashboardController;
use App\Http\Controllers\Dekan\DekanStatistikKelulusanController;
use App\Http\Controllers\Dekan\DetailAngkatanController;
use App\Http\Controllers\Dekan\DetailDashboardController;
use App\Http\Controllers\Dekan\MahasiswaListController;
use App\Http\Controllers\Dekan\NilaiMahasiswaController;
use App\Http\Controllers\Dekan\DekanExportController;
use App\Http\Controllers\Kaprodi\KaprodiExportController;
use App\Http\Controllers\Kaprodi\KaprodiMahasiswaController;
use App\Http\Controllers\Mahasiswa\ProfileExportController;
use App\Http\Controllers\Mahasiswa\ProfileController;

// ─── Public: Login ─────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-kaprodi', [AuthController::class, 'loginKaprodi']);
Route::post('/login-dekan', [AuthController::class, 'loginDekan']);
Route::post('/login-mahasiswa', [AuthController::class, 'loginMahasiswa']);

// ─── Auth: Profile (semua role yang sudah login) ───────────────────────────────
// Menggunakan sti_api_token karena token dari sti-api
Route::middleware('sti_api_token')->get('/profile', [AuthController::class, 'profile']);

// ══════════════════════════════════════════════════════════════════════════════
// EWS ROUTES
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('ews')->group(function () {

    // ── KAPRODI (Kepala Program Studi) ────────────────────────────────────────
    // Akses: recalculate EWS + Dashboard (hanya prodi sendiri)
    Route::middleware(['sti_api_token', 'role:kaprodi'])->prefix('kaprodi')->group(function () {
        Route::get('dashboard', [KaprodiDashboardController::class, 'getDashboard']);
        Route::get('dashboard/detail', [KaprodiDashboardController::class, 'getDetailDashboard']);
        Route::get('dashboard/mahasiswa', [KaprodiDashboardController::class, 'getMahasiswaListByCriteria']);
        Route::get('statistik-kelulusan', [KaprodiDashboardController::class, 'getStatistikKelulusan']);
        Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [KaprodiEwsController::class, 'recalculateMahasiswaStatus']);
        Route::post('recalculate-all-status',                     [KaprodiEwsController::class, 'recalculateAllStatus']);

        // Kaprodi Mahasiswa routes
        Route::get('mahasiswa/list', [KaprodiMahasiswaController::class, 'getMahasiswaList']);
        Route::get('mahasiswa/by-status', [KaprodiMahasiswaController::class, 'getMahasiswaByStatus']);
        Route::get('mahasiswa/nilai-detail', [KaprodiMahasiswaController::class, 'getNilaiMahasiswaList']);

        // Export routes
        Route::get('export/dashboard', [KaprodiExportController::class, 'exportDashboard']);
        Route::get('export/dashboard-detail', [KaprodiExportController::class, 'exportDashboardDetail']);
        Route::get('export/statistik-kelulusan', [KaprodiExportController::class, 'exportStatistikKelulusan']);
        Route::get('export/mahasiswa-list', [KaprodiExportController::class, 'exportMahasiswaList']);
        Route::get('export/mahasiswa-by-status', [KaprodiExportController::class, 'exportMahasiswaByStatus']);
    });

    // ── DEKAN (Dekan Fakultas) ────────────────────────────────────────────────
    // Akses: recalculate EWS + Dashboard
    Route::middleware(['sti_api_token', 'role:dekan'])->prefix('dekan')->group(function () {
        Route::get('dashboard', [DekanDashboardController::class, 'getDashboard']);
        Route::get('dashboard/detail', [DetailDashboardController::class, 'getDetailDashboard']);
        Route::get('dashboard/mahasiswa', [DetailDashboardController::class, 'getMahasiswaListByCriteria']);
        Route::get('statistik-kelulusan', [DekanStatistikKelulusanController::class, 'getTableStatistikKelulusan']);
        Route::get('detail-angkatan/{tahunMasuk}', [DetailAngkatanController::class, 'getDetailAngkatan']);
        Route::get('tahun-angkatan', [DetailAngkatanController::class, 'getTahunAngkatan']);
        Route::get('mahasiswa/list', [MahasiswaListController::class, 'getMahasiswaList']);
        Route::get('mahasiswa/kriteria', [MahasiswaListController::class, 'getAvailableKriteria']);
        Route::get('mahasiswa/by-status', [MahasiswaListController::class, 'getMahasiswaByStatus']);
        Route::get('mahasiswa/nilai-detail', [NilaiMahasiswaController::class, 'getNilaiMahasiswaList']);
        Route::get('mahasiswa/nilai-summary', [NilaiMahasiswaController::class, 'getNilaiMahasiswaSummary']);
        Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [DekanEwsController::class, 'recalculateMahasiswaStatus']);
        Route::post('recalculate-all-status',                     [DekanEwsController::class, 'recalculateAllStatus']);

        // Export routes
        Route::get('export/dashboard', [DekanExportController::class, 'exportDashboard']);
        Route::get('export/dashboard-detail', [DekanExportController::class, 'exportDashboardDetail']);
        Route::get('export/statistik-kelulusan', [DekanExportController::class, 'exportStatistikKelulusan']);
        Route::get('export/detail-angkatan/{tahunMasuk}', [DekanExportController::class, 'exportDetailAngkatan']);
        Route::get('export/mahasiswa-list', [DekanExportController::class, 'exportMahasiswaList']);
        Route::get('export/mahasiswa-by-status', [DekanExportController::class, 'exportMahasiswaByStatus']);
        Route::get('export/nilai-detail', [DekanExportController::class, 'exportNilaiDetail']);
        Route::get('export/nilai-summary', [DekanExportController::class, 'exportNilaiSummary']);
    });

    // ── MAHASISWA ─────────────────────────────────────────────────────────────
    Route::middleware(['sti_api_token', 'role:mahasiswa'])->prefix('mahasiswa')->group(function () {
        Route::get('profile', [ProfileController::class, 'getProfile']);
        Route::get('export/profile', [ProfileExportController::class, 'exportProfile']);
    });
});
