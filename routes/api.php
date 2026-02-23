<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Koor\KoorController;
use App\Http\Controllers\Koor\EwsController;
use App\Http\Controllers\Dosen\DosenController;
use App\Http\Controllers\Mahsiswa\MahasiswaController;

// Public route - Login
Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
});

Route::prefix('ews')->group(function () {
    Route::middleware(['auth:sanctum', 'role:koor'])->prefix('koor')->group(function () {
        //Dashboard Koor
        Route::get('rata-ipk-per-angkatan', [KoorController::class, 'getRataIpkPerAngkatan']);
    Route::get('status-mahasiswa', [KoorController::class, 'getStatusMahasiswa']);
    Route::get('status-kelulusan', [KoorController::class, 'getStatusKelulusan']);
    Route::get('table-ringkasan-mahasiswa', [KoorController::class, 'getTableRingkasanMahasiswa']);
    Route::get('mahasiswa/detail/{mahasiswaId}', [KoorController::class, 'getDetailMahasiswa']);

    //General
    Route::get('mahasiswa/detail-angkatan/{tahunMasuk}', [KoorController::class, 'getDetailAngkatan']);
    Route::get('mahasiswa/all', [KoorController::class, 'getMahasiswaAll']);

    //Status Mahasiswa
    Route::get('distribusi-status-ews', [EwsController::class, 'getDistribusiStatusEws']);
    Route::get('table-ringkasan-status', [KoorController::class, 'getTableRingkasanStatus']);

    //capaian mahasiswa
    Route::get('tren-ips/all', [KoorController::class, 'getTrenIPSAll']);
    Route::get('card-capaian', [KoorController::class, 'getCardCapaianMahasiswa']);
    Route::get('top-mk-gagal', [KoorController::class, 'getTopTenMkGagalAll']);
    Route::get('mahasiswa/mk-gagal', [KoorController::class, 'getMahasiswaMkGagal']);

    //statistik kelulusan
    Route::get('statistik-kelulusan', [KoorController::class, 'getCardStatistikKelulusan']);
    Route::get('table-statistik-kelulusan', [KoorController::class, 'getTableStatistikKelulusan']);

    // Tindak Lanjut Prodi
    Route::get('surat-rekomitmen', [KoorController::class, 'getSuratRekomitmen']);
    Route::patch('surat-rekomitmen/{id_rekomitmen}', [KoorController::class, 'updateStatusRekomitmen']);

    // Early Warning System
    Route::post('mahasiswa/{mahasiswaId}/recalculate-status', [EwsController::class, 'recalculateMahasiswaStatus']);
    Route::post('recalculate-all-status', [EwsController::class, 'recalculateAllStatus']);
});

Route::middleware(['auth:sanctum', 'role:dosen'])->prefix('dosen')->group(function () {
    // Dashboard Dosen
    Route::get('rata-ipk-per-angkatan', [DosenController::class, 'getRataIpkPerAngkatan']);
    Route::get('status-mahasiswa', [DosenController::class, 'getStatusMahasiswa']);
    Route::get('status-kelulusan', [DosenController::class, 'getStatusKelulusan']);
    Route::get('table-ringkasan-mahasiswa', [DosenController::class, 'getTableRingkasanMahasiswa']);
    Route::get('mahasiswa/detail/{mahasiswaId}', [DosenController::class, 'getDetailMahasiswa']);

    //General
    Route::get('mahasiswa/detail-angkatan/{tahunMasuk}', [DosenController::class, 'getDetailAngkatan']);
    Route::get('mahasiswa/all', [DosenController::class, 'getMahasiswaAll']);

    //Status Mahasiswa
    Route::get('distribusi-status-ews', [DosenController::class, 'getDistribusiStatusEws']);
    Route::get('table-ringkasan-status', [DosenController::class, 'getTableRingkasanStatus']);

    //statistik kelulusan
    Route::get('statistik-kelulusan', [DosenController::class, 'getCardStatistikKelulusan']);
    Route::get('table-statistik-kelulusan', [DosenController::class, 'getTableStatistikKelulusan']);
});

Route::middleware(['auth:sanctum', 'role:mahasiswa'])->prefix('mahasiswa')->group(function () {
    // Dashboard Mahasiswa
    Route::get('dashboard', [MahasiswaController::class, 'getDashboardMahasiswa']);

    // Status Akademik
    Route::get('card-status-akademik', [MahasiswaController::class, 'getCardStatusAkademik']);
    Route::get('khs-krs', [MahasiswaController::class, 'getKhsKrsMahasiswa']);
    Route::get('khs-krs/{khsKrsId}', [MahasiswaController::class, 'getDetailKhsKrs']);

    // peringatan
    Route::get('peringatan', [MahasiswaController::class, 'getPeringatan']);
});
});
