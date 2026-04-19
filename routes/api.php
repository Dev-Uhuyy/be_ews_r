<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ─── Kaprodi Controllers ──────────────────────────────────────────────────
use App\Http\Controllers\Kaprodi\DashboardController as KaprodiDashboardController;
use App\Http\Controllers\Kaprodi\StatusMahasiswaController as KaprodiStatusMahasiswaController;
use App\Http\Controllers\Kaprodi\CapaianMahasiswaController;
use App\Http\Controllers\Kaprodi\StatistikKelulusanController as KaprodiStatistikKelulusanController;
use App\Http\Controllers\Kaprodi\TindakLanjutProdiController;
use App\Http\Controllers\Kaprodi\EwsController;

// ─── Dekan Controllers ─────────────────────────────────────────
use App\Http\Controllers\Dekan\DashboardController as DekanDashboardController;
use App\Http\Controllers\Dekan\StatusMahasiswaController as DekanStatusMahasiswaController;
use App\Http\Controllers\Dekan\StatistikKelulusanController as DekanStatistikKelulusanController;

// ─── Mahasiswa Controllers ─────────────────────────────────────────────────────
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\KhsKrsController;
use App\Http\Controllers\Mahasiswa\PeringatanController;
use App\Http\Controllers\Mahasiswa\MhsTindakLanjutController;

// ─── Public: Login ─────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-kaprodi', [AuthController::class, 'loginKaprodi']);
Route::post('/login-dekan', [AuthController::class, 'loginDekan']);
Route::post('/login-mahasiswa', [AuthController::class, 'loginMahasiswa']);

// ─── Auth: Profile (semua role yang sudah login) ───────────────────────────────
Route::middleware('auth:sanctum')->get('/profile', [AuthController::class, 'profile']);

// ══════════════════════════════════════════════════════════════════════════════
// EWS ROUTES
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('ews')->group(function () {

    // ── KAPRODI (Kepala Program Studi) ────────────────────────────────────────
    // Akses: dashboard prodi, status mahasiswa, capaian, tindak lanjut
    Route::middleware(['auth:sanctum', 'role:kaprodi|dekan'])->prefix('kaprodi')->group(function () {

        // Dashboard
        Route::get('dashboard',                     [KaprodiDashboardController::class,     'getDashboard']);
        Route::get('table-ringkasan-mahasiswa',      [KaprodiDashboardController::class,     'getTableRingkasanMahasiswa']);
        Route::get('table-ringkasan-mahasiswa/export',[KaprodiDashboardController::class,    'exportTableRingkasanMahasiswaCsv']);

        // Status Mahasiswa (alias for mahasiswa/all)
        Route::get('status-mahasiswa',               [KaprodiStatusMahasiswaController::class, 'getMahasiswaAll']);

        // Mahasiswa
        Route::get('mahasiswa/detail/{mahasiswaId}',         [KaprodiStatusMahasiswaController::class, 'getDetailMahasiswa']);
        Route::get('mahasiswa/detail-angkatan/{tahunMasuk}', [KaprodiStatusMahasiswaController::class, 'getDetailAngkatan']);
        Route::get('mahasiswa/detail-angkatan/{tahunMasuk}/export', [KaprodiStatusMahasiswaController::class, 'exportDetailAngkatanCsv']);
        Route::get('mahasiswa/all',                          [KaprodiStatusMahasiswaController::class, 'getMahasiswaAll']);
        Route::get('mahasiswa/all/export',                   [KaprodiStatusMahasiswaController::class, 'exportMahasiswaAllCsv']);

        // Status EWS
        Route::get('distribusi-status-ews',          [EwsController::class,                 'getDistribusiStatusEws']);
        Route::get('table-ringkasan-status',          [KaprodiStatusMahasiswaController::class, 'getTableRingkasanStatus']);
        Route::get('table-ringkasan-status/export',   [KaprodiStatusMahasiswaController::class, 'exportTableRingkasanStatusCsv']);

        // Capaian Mahasiswa
        Route::get('tren-ips/all',               [CapaianMahasiswaController::class, 'getTrenIPSAll']);
        Route::get('tren-ips/all/export',         [CapaianMahasiswaController::class, 'exportTrenIPSAll']);
        Route::get('card-capaian',               [CapaianMahasiswaController::class, 'getCardCapaianMahasiswa']);
        Route::get('top-mk-gagal',               [CapaianMahasiswaController::class, 'getTopTenMKGagalAll']);
        Route::get('mahasiswa/mk-gagal',          [CapaianMahasiswaController::class, 'getMahasiswaMKGagal']);
        Route::get('mahasiswa/mk-gagal/export',   [CapaianMahasiswaController::class, 'exportMahasiswaMKGagal']);

        // Statistik Kelulusan
        Route::get('statistik-kelulusan',         [KaprodiStatistikKelulusanController::class, 'getCardStatistikKelulusan']);
        Route::get('table-statistik-kelulusan',   [KaprodiStatistikKelulusanController::class, 'getTableStatistikKelulusan']);

        // Tindak Lanjut Prodi
        Route::prefix('tindak-lanjut')->group(function () {
            Route::get('cards',          [TindakLanjutProdiController::class, 'getCardSummary']);
            Route::get('/',              [TindakLanjutProdiController::class, 'getTindakLanjut']);
            Route::get('export',         [TindakLanjutProdiController::class, 'exportCsv']);
            Route::patch('bulk-update',  [TindakLanjutProdiController::class, 'bulkUpdateStatus']);
            Route::patch('{id}',         [TindakLanjutProdiController::class, 'updateStatus']);
        });

        // Recalculate EWS Status
        Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [EwsController::class, 'recalculateMahasiswaStatus']);
        Route::post('recalculate-all-status',                     [EwsController::class, 'recalculateAllStatus']);
    });

    // ── DEKAN (Dekan Fakultas) ────────────────────────────────────────────────
    // Akses: dashboard level fakultas, statistik semua prodi
    Route::middleware(['auth:sanctum', 'role:dekan'])->prefix('dekan')->group(function () {

        // Dashboard Fakultas
        Route::get('dashboard',                  [DekanDashboardController::class,     'getDashboard']);
        Route::get('table-ringkasan-mahasiswa',   [DekanDashboardController::class,     'getTableRingkasanMahasiswa']);
        Route::get('table-ringkasan-mahasiswa/export', [DekanDashboardController::class, 'exportTableRingkasanMahasiswaCsv']);

        // Status Mahasiswa (alias for mahasiswa/all)
        Route::get('status-mahasiswa',            [DekanStatusMahasiswaController::class, 'getMahasiswaAll']);

        Route::get('mahasiswa/detail/{mahasiswaId}', [DekanStatusMahasiswaController::class, 'getDetailMahasiswa']);

        // Overview semua prodi
        Route::get('mahasiswa/detail-angkatan/{tahunMasuk}', [DekanStatusMahasiswaController::class, 'getDetailAngkatan']);
        Route::get('mahasiswa/detail-angkatan/{tahunMasuk}/export', [DekanStatusMahasiswaController::class, 'exportDetailAngkatanCsv']);
        Route::get('mahasiswa/all',                           [DekanStatusMahasiswaController::class, 'getMahasiswaAll']);
        Route::get('mahasiswa/all/export',                    [DekanStatusMahasiswaController::class, 'exportMahasiswaAllCsv']);

        // Status EWS & Management
        Route::get('distribusi-status-ews',     [\App\Http\Controllers\Dekan\EwsController::class, 'getDistribusiStatusEws']);
        Route::get('table-ringkasan-status',     [DekanStatusMahasiswaController::class, 'getTableRingkasanStatus']);
        Route::get('table-ringkasan-status/export', [DekanStatusMahasiswaController::class, 'exportTableRingkasanStatusCsv']);
        Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [\App\Http\Controllers\Dekan\EwsController::class, 'recalculateMahasiswaStatus']);
        Route::post('recalculate-all-status',                     [\App\Http\Controllers\Dekan\EwsController::class, 'recalculateAllStatus']);

        // Statistik Kelulusan
        Route::get('statistik-kelulusan',        [DekanStatistikKelulusanController::class, 'getCardStatistikKelulusan']);
        Route::get('table-statistik-kelulusan',  [DekanStatistikKelulusanController::class, 'getTableStatistikKelulusan']);

        // Capaian Mahasiswa
        Route::get('tren-ips/all',               [\App\Http\Controllers\Dekan\CapaianMahasiswaController::class, 'getTrenIPSAll']);
        Route::get('tren-ips/all/export',        [\App\Http\Controllers\Dekan\CapaianMahasiswaController::class, 'exportTrenIPSAll']);
        Route::get('card-capaian',               [\App\Http\Controllers\Dekan\CapaianMahasiswaController::class, 'getCardCapaianMahasiswa']);
        Route::get('top-mk-gagal',               [\App\Http\Controllers\Dekan\CapaianMahasiswaController::class, 'getTopTenMKGagalAll']);
        Route::get('mahasiswa/mk-gagal',          [\App\Http\Controllers\Dekan\CapaianMahasiswaController::class, 'getMahasiswaMKGagal']);
        Route::get('mahasiswa/mk-gagal/export',   [\App\Http\Controllers\Dekan\CapaianMahasiswaController::class, 'exportMahasiswaMKGagal']);

        // Tindak Lanjut Prodi
        Route::prefix('tindak-lanjut')->group(function () {
            Route::get('cards',          [\App\Http\Controllers\Dekan\TindakLanjutProdiController::class, 'getCardSummary']);
            Route::get('/',              [\App\Http\Controllers\Dekan\TindakLanjutProdiController::class, 'getTindakLanjut']);
            Route::get('export',         [\App\Http\Controllers\Dekan\TindakLanjutProdiController::class, 'exportCsv']);
            Route::patch('{id}',         [\App\Http\Controllers\Dekan\TindakLanjutProdiController::class, 'updateStatus']);
        });
    });

    // ── MAHASISWA ─────────────────────────────────────────────────────────────
    // Akses: dashboard pribadi, KHS/KRS, peringatan EWS, tindak lanjut
    Route::middleware(['auth:sanctum', 'role:mahasiswa'])->prefix('mahasiswa')->group(function () {

        // Dashboard
        Route::get('dashboard',          [MahasiswaDashboardController::class, 'getDashboardMahasiswa']);
        Route::get('card-status-akademik',[MahasiswaDashboardController::class, 'getCardStatusAkademik']);

        // KHS / KRS
        Route::get('khs-krs',            [KhsKrsController::class, 'getKhsKrsMahasiswa']);
        Route::get('khs-krs/export',     [KhsKrsController::class, 'exportKhsKrsCsv']);
        Route::get('khs-krs/{khsKrsId}', [KhsKrsController::class, 'getDetailKhsKrs']);

        // Peringatan EWS
        Route::get('peringatan',         [PeringatanController::class, 'getPeringatan']);

        // Tindak Lanjut Mahasiswa
        Route::prefix('tindak-lanjut')->group(function () {
            Route::get('cards',              [MhsTindakLanjutController::class, 'getCardSummary']);
            Route::get('/',                  [MhsTindakLanjutController::class, 'index']);
            Route::post('/',                 [MhsTindakLanjutController::class, 'store']);
            Route::get('template/{kategori}',[MhsTindakLanjutController::class, 'getTemplate']);
        });
    });
});
