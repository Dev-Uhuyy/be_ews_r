<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Mahasiswa\ProfileController;
use App\Http\Controllers\Mahasiswa\ProfileExportController;
use App\Http\Controllers\SuperFakultas\DetailAngkatanController;
use App\Http\Controllers\SuperFakultas\DetailDashboardController;
use App\Http\Controllers\SuperFakultas\EwsController as SuperFakultasEwsController;
use App\Http\Controllers\SuperFakultas\MahasiswaListController;
use App\Http\Controllers\SuperFakultas\NilaiMahasiswaController;
use App\Http\Controllers\SuperFakultas\SuperFakultasCapaianMahasiswaController;
use App\Http\Controllers\SuperFakultas\SuperFakultasDashboardController;
use App\Http\Controllers\SuperFakultas\SuperFakultasExportController;
use App\Http\Controllers\SuperFakultas\SuperFakultasStatistikKelulusanController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\EwsController as AdminEwsController;
use App\Http\Controllers\Admin\AdminCapaianMahasiswaController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminMahasiswaController;
use Illuminate\Support\Facades\Route;

// ─── Public: Login ─────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-admin', [AuthController::class, 'loginAdmin']);
Route::post('/login-super-fakultas', [AuthController::class, 'loginSuperFakultas']);
Route::post('/login-mahasiswa', [AuthController::class, 'loginMahasiswa']);

// ─── Auth: Profile (semua role yang sudah login) ───────────────────────────────
// Menggunakan sti_api_token karena token dari sti-api
Route::middleware('sti_api_token')->get('/profile', [AuthController::class, 'profile']);

// ══════════════════════════════════════════════════════════════════════════════
// EWS ROUTES
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('ews')->group(function () {

    // ── ADMIN (Kepala Program Studi) ────────────────────────────────────────
    // Akses: recalculate EWS + Dashboard (hanya prodi sendiri)
    Route::middleware(['sti_api_token', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'getDashboard']);
        Route::get('dashboard/detail', [AdminDashboardController::class, 'getDetailDashboard']);
        Route::get('dashboard/mahasiswa', [AdminDashboardController::class, 'getMahasiswaListByCriteria']);
        Route::get('statistik-kelulusan', [AdminDashboardController::class, 'getStatistikKelulusan']);
        Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [AdminEwsController::class, 'recalculateMahasiswaStatus']);
        Route::post('recalculate-all-status', [AdminEwsController::class, 'recalculateAllStatus']);

        // Admin Mahasiswa routes
        Route::get('mahasiswa/list', [AdminMahasiswaController::class, 'getMahasiswaList']);
        Route::get('mahasiswa/by-status', [AdminMahasiswaController::class, 'getMahasiswaByStatus']);
        Route::get('mahasiswa/nilai-detail', [AdminMahasiswaController::class, 'getNilaiMahasiswaList']);

        // Admin Capaian Mahasiswa routes
        Route::get('capaian-mahasiswa/top-matakuliah-gagal', [AdminCapaianMahasiswaController::class, 'getTop10MatakuliahGagal']);
        Route::get('capaian-mahasiswa/rata-rata-ips', [AdminCapaianMahasiswaController::class, 'getRataRataIpsPerTahunProdi']);
        Route::get('capaian-mahasiswa/tabel-capaian', [AdminCapaianMahasiswaController::class, 'getTabelCapaianMahasiswa']);
        Route::get('capaian-mahasiswa/tabel-capaian/detail', [AdminCapaianMahasiswaController::class, 'getDetailTabelCapaianMahasiswa']);
        Route::get('capaian-mahasiswa/list-matakuliah', [AdminCapaianMahasiswaController::class, 'getListMataKuliahPerProdi']);
        Route::get('capaian-mahasiswa/mahasiswa-gagal', [AdminCapaianMahasiswaController::class, 'getListMahasiswaGagalPerMataKuliah']);
        Route::get('capaian-mahasiswa/mahasiswa-gagal-by-angkatan', [AdminCapaianMahasiswaController::class, 'getListMahasiswaGagalByAngkatan']);

        // Capaian Mahasiswa Export routes
        Route::get('capaian-mahasiswa/export/top-matakuliah-gagal', [AdminCapaianMahasiswaController::class, 'exportTopMatakuliahGagal']);
        Route::get('capaian-mahasiswa/export/rata-rata-ips', [AdminCapaianMahasiswaController::class, 'exportRataRataIps']);
        Route::get('capaian-mahasiswa/export/tabel-capaian', [AdminCapaianMahasiswaController::class, 'exportTabelCapaian']);
        Route::get('capaian-mahasiswa/export/tabel-capaian/detail', [AdminCapaianMahasiswaController::class, 'exportTabelCapaianDetail']);
        Route::get('capaian-mahasiswa/export/list-matakuliah', [AdminCapaianMahasiswaController::class, 'exportListMatakuliah']);
        Route::get('capaian-mahasiswa/export/mahasiswa-gagal', [AdminCapaianMahasiswaController::class, 'exportMahasiswaGagal']);

        // Export routes
        Route::get('export/dashboard', [AdminExportController::class, 'exportDashboard']);
        Route::get('export/dashboard/detail', [AdminExportController::class, 'exportDashboardDetail']);
        Route::get('export/statistik-kelulusan', [AdminExportController::class, 'exportStatistikKelulusan']);
        Route::get('export/mahasiswa-list', [AdminExportController::class, 'exportMahasiswaList']);
        Route::get('export/mahasiswa-by-status', [AdminExportController::class, 'exportMahasiswaByStatus']);
    });

    // ── SUPER FAKULTAS (Dekan Fakultas) ────────────────────────────────────────
    // Akses: recalculate EWS + Dashboard
    Route::middleware(['sti_api_token', 'role:super_fakultas'])->prefix('super-fakultas')->group(function () {
        Route::get('dashboard', [SuperFakultasDashboardController::class, 'getDashboard']);
        Route::get('dashboard/detail', [DetailDashboardController::class, 'getDetailDashboard']);
        Route::get('dashboard/mahasiswa', [DetailDashboardController::class, 'getMahasiswaListByCriteria']);
        Route::get('statistik-kelulusan', [SuperFakultasStatistikKelulusanController::class, 'getTableStatistikKelulusan']);
        Route::get('detail-angkatan/{tahunMasuk}', [DetailAngkatanController::class, 'getDetailAngkatan']);
        Route::get('tahun-angkatan', [DetailAngkatanController::class, 'getTahunAngkatan']);
        Route::get('mahasiswa/list', [MahasiswaListController::class, 'getMahasiswaList']);
        Route::get('mahasiswa/kriteria', [MahasiswaListController::class, 'getAvailableKriteria']);
        Route::get('mahasiswa/by-status', [MahasiswaListController::class, 'getMahasiswaByStatus']);
        Route::get('mahasiswa/nilai-detail', [NilaiMahasiswaController::class, 'getNilaiMahasiswaList']);
        Route::get('mahasiswa/nilai-summary', [NilaiMahasiswaController::class, 'getNilaiMahasiswaSummary']);
        Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [SuperFakultasEwsController::class, 'recalculateMahasiswaStatus']);
        Route::post('recalculate-all-status', [SuperFakultasEwsController::class, 'recalculateAllStatus']);

        // Capaian Mahasiswa routes
        Route::get('capaian-mahasiswa/top-matakuliah-gagal', [SuperFakultasCapaianMahasiswaController::class, 'getTop10MatakuliahGagal']);
        Route::get('capaian-mahasiswa/rata-rata-ips', [SuperFakultasCapaianMahasiswaController::class, 'getRataRataIpsPerTahunProdi']);
        Route::get('capaian-mahasiswa/tabel-capaian', [SuperFakultasCapaianMahasiswaController::class, 'getTabelCapaianMahasiswa']);
        Route::get('capaian-mahasiswa/tabel-capaian/detail', [SuperFakultasCapaianMahasiswaController::class, 'getDetailTabelCapaianMahasiswa']);

        // Export routes
        Route::get('export/dashboard', [SuperFakultasExportController::class, 'exportDashboard']);
        Route::get('export/dashboard/detail', [SuperFakultasExportController::class, 'exportDashboardDetail']);
        Route::get('export/statistik-kelulusan', [SuperFakultasExportController::class, 'exportStatistikKelulusan']);
        Route::get('export/detail-angkatan/{tahunMasuk}', [SuperFakultasExportController::class, 'exportDetailAngkatan']);
        Route::get('export/mahasiswa-list', [SuperFakultasExportController::class, 'exportMahasiswaList']);
        Route::get('export/mahasiswa-by-status', [SuperFakultasExportController::class, 'exportMahasiswaByStatus']);
        Route::get('export/nilai-detail', [SuperFakultasExportController::class, 'exportNilaiDetail']);
        Route::get('export/nilai-summary', [SuperFakultasExportController::class, 'exportNilaiSummary']);

        // List Mata Kuliah Gagal per Prodi
        Route::get('capaian-mahasiswa/list-matakuliah', [SuperFakultasCapaianMahasiswaController::class, 'getListMataKuliahPerProdi']);

        // List Mahasiswa Gagal per Mata Kuliah
        Route::get('capaian-mahasiswa/mahasiswa-gagal', [SuperFakultasCapaianMahasiswaController::class, 'getListMahasiswaGagalPerMataKuliah']);

        // Capaian Mahasiswa Export routes
        Route::get('capaian-mahasiswa/export/top-matakuliah-gagal', [SuperFakultasCapaianMahasiswaController::class, 'exportTopMatakuliahGagal']);
        Route::get('capaian-mahasiswa/export/rata-rata-ips', [SuperFakultasCapaianMahasiswaController::class, 'exportRataRataIps']);
        Route::get('capaian-mahasiswa/export/tabel-capaian', [SuperFakultasCapaianMahasiswaController::class, 'exportTabelCapaian']);
        Route::get('capaian-mahasiswa/export/tabel-capaian/detail', [SuperFakultasCapaianMahasiswaController::class, 'exportTabelCapaianDetail']);
        Route::get('capaian-mahasiswa/export/list-matakuliah', [SuperFakultasCapaianMahasiswaController::class, 'exportListMatakuliah']);
        Route::get('capaian-mahasiswa/export/mahasiswa-gagal', [SuperFakultasCapaianMahasiswaController::class, 'exportMahasiswaGagal']);
    });

    // ── MAHASISWA ─────────────────────────────────────────────────────────────
    Route::middleware(['sti_api_token', 'role:mahasiswa'])->prefix('mahasiswa')->group(function () {
        Route::get('profile', [ProfileController::class, 'getProfile']);
        Route::get('export/profile', [ProfileExportController::class, 'exportProfile']);
    });
});
