<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Koor\DashboardController as KoorDashboardController;
use App\Http\Controllers\Koor\StatusMahasiswaController as KoorStatusMahasiswaController;
use App\Http\Controllers\Koor\CapaianMahasiswaController;
use App\Http\Controllers\Koor\StatistikKelulusanController as KoorStatistikKelulusanController;
use App\Http\Controllers\Koor\TindakLanjutProdiController;
use App\Http\Controllers\Koor\EwsController;
use App\Http\Controllers\Dosen\DashboardController as DosenDashboardController;
use App\Http\Controllers\Dosen\StatusMahasiswaController as DosenStatusMahasiswaController;
use App\Http\Controllers\Dosen\StatistikKelulusanController as DosenStatistikKelulusanController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\KhsKrsController;
use App\Http\Controllers\Mahasiswa\PeringatanController;
use App\Http\Controllers\Mahasiswa\TindakLanjutController as MahasiswaTindakLanjutController;

// Public route - Login
Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/profile', 'profile');
    });
});

Route::prefix('ews')->group(function () {
    Route::middleware(['auth:sanctum', 'role:koor'])->prefix('koor')->group(function () {
    // Dashboard Koor - Combined endpoint
    Route::get('dashboard', [KoorDashboardController::class, 'getDashboard']);
    Route::get('table-ringkasan-mahasiswa', [KoorDashboardController::class, 'getTableRingkasanMahasiswa']);
    Route::get('table-ringkasan-mahasiswa/export', [KoorDashboardController::class, 'exportTableRingkasanMahasiswaCsv']);

    //General
    Route::get('mahasiswa/detail/{mahasiswaId}', [KoorStatusMahasiswaController::class, 'getDetailMahasiswa']);
    Route::get('mahasiswa/detail-angkatan/{tahunMasuk}', [KoorStatusMahasiswaController::class, 'getDetailAngkatan']);
    Route::get('mahasiswa/all', [KoorStatusMahasiswaController::class, 'getMahasiswaAll']);
    Route::get('mahasiswa/all/export', [KoorStatusMahasiswaController::class, 'exportMahasiswaAllCsv']);

    //Status Mahasiswa
    Route::get('distribusi-status-ews', [EwsController::class, 'getDistribusiStatusEws']);
    Route::get('table-ringkasan-status', [KoorStatusMahasiswaController::class, 'getTableRingkasanStatus']);
    Route::get('table-ringkasan-status/export', [KoorStatusMahasiswaController::class, 'exportTableRingkasanStatusCsv']);

    //capaian mahasiswa
    Route::get('tren-ips/all', [CapaianMahasiswaController::class, 'getTrenIPSAll']);
    Route::get('tren-ips/all/export', [CapaianMahasiswaController::class, 'exportTrenIPSAll']);
    Route::get('card-capaian', [CapaianMahasiswaController::class, 'getCardCapaianMahasiswa']);
    Route::get('top-mk-gagal', [CapaianMahasiswaController::class, 'getTopTenMKGagalAll']);
    Route::get('mahasiswa/mk-gagal', [CapaianMahasiswaController::class, 'getMahasiswaMKGagal']);
    Route::get('mahasiswa/mk-gagal/export', [CapaianMahasiswaController::class, 'exportMahasiswaMKGagal']);

    // statistik kelulusan
    Route::get('statistik-kelulusan', [KoorStatistikKelulusanController::class, 'getCardStatistikKelulusan']);
    Route::get('table-statistik-kelulusan', [KoorStatistikKelulusanController::class, 'getTableStatistikKelulusan']);

    // Tindak Lanjut Prodi (Consolidated)
    Route::prefix('tindak-lanjut')->group(function () {
        Route::get('cards', [TindakLanjutProdiController::class, 'getCardSummary']);
        Route::get('/', [TindakLanjutProdiController::class, 'getTindakLanjut']);
        Route::get('export', [TindakLanjutProdiController::class, 'exportCsv']);
        Route::patch('{id}', [TindakLanjutProdiController::class, 'updateStatus']);
    });

    // Early Warning System
    Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [EwsController::class, 'recalculateMahasiswaStatus']);
    Route::post('recalculate-all-status', [EwsController::class, 'recalculateAllStatus']);
});

Route::middleware(['auth:sanctum', 'role:dosen'])->prefix('dosen')->group(function () {
    // Dashboard Dosen - Combined endpoint
    Route::get('dashboard', [DosenDashboardController::class, 'getDashboard']);
    Route::get('table-ringkasan-mahasiswa', [DosenDashboardController::class, 'getTableRingkasanMahasiswa']);
    Route::get('mahasiswa/detail/{mahasiswaId}', [DosenStatusMahasiswaController::class, 'getDetailMahasiswa']);

    //General
    Route::get('mahasiswa/detail-angkatan/{tahunMasuk}', [DosenStatusMahasiswaController::class, 'getDetailAngkatan']);
    Route::get('mahasiswa/all', [DosenStatusMahasiswaController::class, 'getMahasiswaAll']);

    //Status Mahasiswa
    Route::get('distribusi-status-ews', [DosenStatusMahasiswaController::class, 'getDistribusiStatusEws']);
    Route::get('table-ringkasan-status', [DosenStatusMahasiswaController::class, 'getTableRingkasanStatus']);

    //statistik kelulusan
    Route::get('statistik-kelulusan', [DosenStatistikKelulusanController::class, 'getCardStatistikKelulusan']);
    Route::get('table-statistik-kelulusan', [DosenStatistikKelulusanController::class, 'getTableStatistikKelulusan']);
});

Route::middleware(['auth:sanctum', 'role:mahasiswa'])->prefix('mahasiswa')->group(function () {
    // Dashboard Mahasiswa
    Route::get('dashboard', [MahasiswaDashboardController::class, 'getDashboardMahasiswa']);

    // Status Akademik
    Route::get('card-status-akademik', [MahasiswaDashboardController::class, 'getCardStatusAkademik']);
    Route::get('khs-krs', [KhsKrsController::class, 'getKhsKrsMahasiswa']);
    Route::get('khs-krs/{khsKrsId}', [KhsKrsController::class, 'getDetailKhsKrs']);

    // peringatan
    Route::get('peringatan', [PeringatanController::class, 'getPeringatan']);

    // Tindak Lanjut
    Route::prefix('tindak-lanjut')->group(function () {
        Route::get('cards', [MahasiswaTindakLanjutController::class, 'getCardSummary']);
        Route::get('/', [MahasiswaTindakLanjutController::class, 'index']);
        Route::post('/', [MahasiswaTindakLanjutController::class, 'store']);
        Route::get('template/{kategori}', [MahasiswaTindakLanjutController::class, 'getTemplate']);
    });
});
});
