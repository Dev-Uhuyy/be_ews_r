<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class MataKuliahSeeder extends Seeder
{
    public function run(): void
    {
        $prodis = Prodi::all();

        if ($prodis->isEmpty()) {
            $this->command->error('✖ Tidak ada Prodi ditemukan. Jalankan ProdiSeeder terlebih dahulu.');
            return;
        }

        foreach ($prodis as $prodi) {
            $kode = $prodi->kode_prodi;
            $peminatans = MataKuliahPeminatan::where('prodi_id', $prodi->id)->get()->keyBy('peminatan');
            $mataKuliahs = [];

            if ($kode === 'A11') { // Teknik Informatika
                $p1 = $peminatans['SC']->id ?? null;
                $p2 = $peminatans['RPLD']->id ?? null;
                $p3 = $peminatans['SK3D']->id ?? null;
                
                $mataKuliahs = [
                    // Smt 1-4
                    ['kode' => "{$kode}.54101", 'name' => 'Dasar Pemrograman', 'sks' => 4, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54102", 'name' => 'Matematika Diskrit', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54103", 'name' => 'Sistem Digital', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54201", 'name' => 'Algoritma dan Struktur Data', 'sks' => 4, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54202", 'name' => 'Aljabar Linier', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54203", 'name' => 'Pemrograman Berorientasi Objek', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54301", 'name' => 'Basis Data', 'sks' => 4, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54302", 'name' => 'Sistem Operasi', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54303", 'name' => 'Statistika dan Probabilitas', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54304", 'name' => 'Pemrograman Web', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54401", 'name' => 'Desain dan Analisis Algoritma', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54402", 'name' => 'Rekayasa Perangkat Lunak', 'sks' => 4, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54403", 'name' => 'Interaksi Manusia & Komputer', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    // Smt 5-6
                    ['kode' => "{$kode}.54501", 'name' => 'Machine Learning', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54502", 'name' => 'Data Mining', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54503", 'name' => 'Manajemen Proyek TI', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54601", 'name' => 'Kecerdasan Buatan', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54602", 'name' => 'Deep Learning', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54603", 'name' => 'Cloud Computing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    // Peminatan
                    ['kode' => "{$kode}.54901", 'name' => 'Computer Vision', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54902", 'name' => 'Natural Language Processing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54911", 'name' => 'Software Architecture', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54912", 'name' => 'DevOps Engineering', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54921", 'name' => 'Network Security', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                    ['kode' => "{$kode}.54922", 'name' => 'Penetration Testing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                ];
            } elseif ($kode === 'A12') { // Sistem Informasi
                $p1 = $peminatans['EIS']->id ?? null;
                $p2 = $peminatans['EB']->id ?? null;
                $p3 = $peminatans['DATA']->id ?? null;
                $mataKuliahs = [
                    ['kode' => "{$kode}.54101", 'name' => 'Pengantar Sistem Informasi', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54102", 'name' => 'Logika dan Algoritma', 'sks' => 4, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54103", 'name' => 'Kalkulus Dasar', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54201", 'name' => 'Analisis dan Perancangan SI', 'sks' => 4, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54202", 'name' => 'Manajemen dan Organisasi', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54203", 'name' => 'Dasar Pemrograman Web', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54301", 'name' => 'Basis Data Berorientasi Objek', 'sks' => 4, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54302", 'name' => 'Proses Bisnis Terintegrasi', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54303", 'name' => 'Jaringan Komputer & Komunikasi', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54401", 'name' => 'Manajemen Layanan TI', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54402", 'name' => 'Tata Kelola Sistem Informasi', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54403", 'name' => 'Riset Operasi', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54501", 'name' => 'Audit Sistem Informasi', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54502", 'name' => 'Keamanan Aset Informasi', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54503", 'name' => 'Testing & Implementasi SI', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54601", 'name' => 'Manajemen Pengetahuan', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54602", 'name' => 'Sistem Pendukung Keputusan', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54603", 'name' => 'Arsitektur Enterprise', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54901", 'name' => 'Enterprise Resource Planning', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54902", 'name' => 'Manajemen Rantai Pasok', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54911", 'name' => 'E-Commerce & Digital Bisnis', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54912", 'name' => 'Customer Relationship Mgmt', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54921", 'name' => 'Data Warehouse & OLAP', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                    ['kode' => "{$kode}.54922", 'name' => 'Visualisasi Data Bisnis', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                ];
            } elseif ($kode === 'A14') { // Desain Komunikasi Visual
                $p1 = $peminatans['DG']->id ?? null;
                $p2 = $peminatans['MM']->id ?? null;
                $p3 = $peminatans['AN']->id ?? null;
                $mataKuliahs = [
                    ['kode' => "{$kode}.54101", 'name' => 'Nirmana Dwimatra', 'sks' => 4, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54102", 'name' => 'Tipografi Dasar', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54103", 'name' => 'Sejarah Seni Rupa', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54201", 'name' => 'Nirmana Trimatra', 'sks' => 4, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54202", 'name' => 'Menggambar Bentuk', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54203", 'name' => 'Estetika', 'sks' => 2, 'semester' => 2, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54301", 'name' => 'Komunikasi Visual', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54302", 'name' => 'Tipografi Lanjut', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54303", 'name' => 'Ilustrasi Dasar', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54401", 'name' => 'Tipografi Aplikatif', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54402", 'name' => 'Fotografi Desain', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54403", 'name' => 'Komputer Grafis Terapan', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54501", 'name' => 'Desain Identitas Visual', 'sks' => 4, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54502", 'name' => 'Metodologi Desain', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54503", 'name' => 'Sosiologi Desain', 'sks' => 2, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54601", 'name' => 'Desain Kemasan', 'sks' => 4, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54602", 'name' => 'Manajemen Desain', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54603", 'name' => 'Etika Profesi Desain', 'sks' => 2, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54901", 'name' => 'Desain Buku & Editorial', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54902", 'name' => 'Produksi Iklan Cetak', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54911", 'name' => 'Interactive Design', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54912", 'name' => 'Broadcasting & Audio Visual', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54921", 'name' => 'Animasi 2D & 3D Basic', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                    ['kode' => "{$kode}.54922", 'name' => 'Character Rigging', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                ];
            } elseif ($kode === 'A15') { // Ilmu Komunikasi
                $p1 = $peminatans['PR']->id ?? null;
                $p2 = $peminatans['JR']->id ?? null;
                $p3 = $peminatans['BROAD']->id ?? null;
                $mataKuliahs = [
                    ['kode' => "{$kode}.54101", 'name' => 'Pengantar Ilmu Komunikasi', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54102", 'name' => 'Dasar Psikologi', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54103", 'name' => 'Pengantar Sosiologi', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'fakultas'],
                    ['kode' => "{$kode}.54201", 'name' => 'Teori Komunikasi', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54202", 'name' => 'Komunikasi Antar Pribadi', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54203", 'name' => 'Filsafat Ilmu Komunikasi', 'sks' => 2, 'semester' => 2, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54301", 'name' => 'Komunikasi Massa', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54302", 'name' => 'Sosiologi Komunikasi', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54303", 'name' => 'Psikologi Komunikasi', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54401", 'name' => 'Komunikasi Organisasi', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54402", 'name' => 'Hukum dan Etika Media', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54403", 'name' => 'Public Speaking', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54501", 'name' => 'Riset Komunikasi Kuantitatif', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54502", 'name' => 'Komunikasi Politik', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54503", 'name' => 'Opini Publik', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54601", 'name' => 'Riset Komunikasi Kualitatif', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54602", 'name' => 'Komunikasi Antar Budaya', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54603", 'name' => 'Audit Komunikasi', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
                    ['kode' => "{$kode}.54901", 'name' => 'Public Relations Writing', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54902", 'name' => 'Kampanye Public Relations', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p1],
                    ['kode' => "{$kode}.54911", 'name' => 'Teknik Mencari dan Menulis Berita', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54912", 'name' => 'Jurnalisme Investigasi', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p2],
                    ['kode' => "{$kode}.54921", 'name' => 'Sistem Produksi Televisi', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                    ['kode' => "{$kode}.54922", 'name' => 'Penyutradaraan Non-Drama', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $p3],
                ];
            }

            // Fallback general courses applicable to all prodis to fill the rest of semesters
            $mataKuliahs = array_merge($mataKuliahs, [
                ['kode' => "{$kode}.11101", 'name' => 'Pendidikan Kebangsaan & Pancasila', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'nasional'],
                ['kode' => "{$kode}.11102", 'name' => 'Pendidikan Keagamaan', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'nasional'],
                ['kode' => "{$kode}.11103", 'name' => 'Bahasa Indonesia Komunikasi', 'sks' => 2, 'semester' => 2, 'tipe_mk' => 'nasional'],
                ['kode' => "{$kode}.11104", 'name' => 'English for Academic Purposes', 'sks' => 2, 'semester' => 2, 'tipe_mk' => 'nasional'],
                ['kode' => "{$kode}.11105", 'name' => 'Kewirausahaan Berbasis Teknologi', 'sks' => 2, 'semester' => 3, 'tipe_mk' => 'fakultas'],
                ['kode' => "{$kode}.11106", 'name' => 'Kuliah Kerja Nyata (KKN)', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
                ['kode' => "{$kode}.11107", 'name' => 'Kerja Praktik', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
                ['kode' => "{$kode}.11108", 'name' => 'Tugas Akhir / Skripsi', 'sks' => 6, 'semester' => 8, 'tipe_mk' => 'prodi'],
                ['kode' => "{$kode}.11109", 'name' => 'Seminar Proposal Skripsi', 'sks' => 2, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ]);

            foreach ($mataKuliahs as $mk) {
                MataKuliah::updateOrCreate(
                    ['kode' => $mk['kode']],
                    [
                        'prodi_id'    => $prodi->id,
                        'name'        => $mk['name'],
                        'sks'         => $mk['sks'],
                        'semester'    => $mk['semester'],
                        'tipe_mk'     => $mk['tipe_mk'],
                        'peminatan_id'=> $mk['peminatan_id'] ?? null,
                    ]
                );
            }

            $this->command->info("✔ MataKuliahSeeder: " . count($mataKuliahs) . " mata kuliah di-seed untuk prodi {$prodi->nama}.");
        }
    }
}
