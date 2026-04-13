<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\MataKuliahPeminatan;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class MataKuliahSeeder extends Seeder
{
    /**
     * Seed mata kuliah untuk prodi Teknik Informatika (A11).
     * Gunakan updateOrCreate berdasarkan kode MK (unik).
     */
    public function run(): void
    {
        $prodi = Prodi::where('kode_prodi', 'A11')->first();

        if (!$prodi) {
            $this->command->error('✖ Prodi A11 tidak ditemukan. Jalankan ProdiSeeder terlebih dahulu.');
            return;
        }

        // Ambil peminatan ID
        $peminatanSC   = MataKuliahPeminatan::where('peminatan', 'SC')->where('prodi_id', $prodi->id)->first();
        $peminatanRPLD = MataKuliahPeminatan::where('peminatan', 'RPLD')->where('prodi_id', $prodi->id)->first();
        $peminatanSK3D = MataKuliahPeminatan::where('peminatan', 'SK3D')->where('prodi_id', $prodi->id)->first();

        $mataKuliahs = [
            // ── Semester 1 ──────────────────────────────────────
            ['kode' => 'A11.54101', 'name' => 'Dasar Pemrograman',              'sks' => 4, 'semester' => 1, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54102', 'name' => 'Matematika Diskrit',             'sks' => 3, 'semester' => 1, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54103', 'name' => 'Sistem Digital',                 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54104', 'name' => 'Dasar Sistem Komputer',          'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54105', 'name' => 'Bahasa Inggris',                 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'nasional'],
            ['kode' => 'A11.54106', 'name' => 'Pendidikan Pancasila',           'sks' => 2, 'semester' => 1, 'tipe_mk' => 'nasional'],
            ['kode' => 'A11.54107', 'name' => 'Pengantar Teknologi Informasi',  'sks' => 2, 'semester' => 1, 'tipe_mk' => 'fakultas'],

            // ── Semester 2 ──────────────────────────────────────
            ['kode' => 'A11.54201', 'name' => 'Algoritma dan Struktur Data',           'sks' => 4, 'semester' => 2, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54202', 'name' => 'Matematika Informatika',                'sks' => 3, 'semester' => 2, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54203', 'name' => 'Organisasi dan Arsitektur Komputer',    'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54204', 'name' => 'Aljabar Linier',                        'sks' => 3, 'semester' => 2, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54205', 'name' => 'Pemrograman Berorientasi Objek',        'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54206', 'name' => 'Pendidikan Kewarganegaraan',            'sks' => 2, 'semester' => 2, 'tipe_mk' => 'nasional'],

            // ── Semester 3 ──────────────────────────────────────
            ['kode' => 'A11.54301', 'name' => 'Basis Data',                  'sks' => 4, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54302', 'name' => 'Sistem Operasi',              'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54303', 'name' => 'Jaringan Komputer',           'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54304', 'name' => 'Statistika dan Probabilitas', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54305', 'name' => 'Pemrograman Web',             'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54306', 'name' => 'Pendidikan Agama',            'sks' => 2, 'semester' => 3, 'tipe_mk' => 'nasional'],

            // ── Semester 4 ──────────────────────────────────────
            ['kode' => 'A11.54401', 'name' => 'Desain dan Analisis Algoritma',    'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54402', 'name' => 'Rekayasa Perangkat Lunak',         'sks' => 4, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54403', 'name' => 'Interaksi Manusia dan Komputer',   'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54404', 'name' => 'Teori Bahasa dan Otomata',         'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54405', 'name' => 'Keamanan Sistem Informasi',        'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54406', 'name' => 'Metode Penelitian',                'sks' => 2, 'semester' => 4, 'tipe_mk' => 'fakultas'],

            // ── Semester 5 ──────────────────────────────────────
            ['kode' => 'A11.54501', 'name' => 'Machine Learning',        'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54502', 'name' => 'Pemrograman Mobile',      'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54503', 'name' => 'Data Mining',             'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54504', 'name' => 'Sistem Terdistribusi',    'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54505', 'name' => 'Grafika Komputer',        'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54506', 'name' => 'Manajemen Proyek TI',     'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],

            // ── Semester 6 ──────────────────────────────────────
            ['kode' => 'A11.54601', 'name' => 'Deep Learning',           'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54602', 'name' => 'Cloud Computing',         'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54603', 'name' => 'Kecerdasan Buatan',       'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54604', 'name' => 'Pemrosesan Citra Digital','sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54605', 'name' => 'Komputasi Paralel',       'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54606', 'name' => 'Etika Profesi',           'sks' => 2, 'semester' => 6, 'tipe_mk' => 'fakultas'],

            // ── Semester 7 ──────────────────────────────────────
            ['kode' => 'A11.54701', 'name' => 'Pengolahan Bahasa Alami', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54702', 'name' => 'Internet of Things',      'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54703', 'name' => 'Big Data',                'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54704', 'name' => 'Cyber Security',          'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54705', 'name' => 'Blockchain Technology',   'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54706', 'name' => 'Technopreneurship',       'sks' => 2, 'semester' => 7, 'tipe_mk' => 'fakultas'],

            // ── Semester 8 ──────────────────────────────────────
            ['kode' => 'A11.54801', 'name' => 'Kerja Praktik', 'sks' => 2, 'semester' => 8, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54802', 'name' => 'Tugas Akhir',   'sks' => 6, 'semester' => 8, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54803', 'name' => 'Seminar',        'sks' => 2, 'semester' => 8, 'tipe_mk' => 'prodi'],

            // ── Peminatan SC ─────────────────────────────────────
            ['kode' => 'A11.54901', 'name' => 'Advanced Machine Learning', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSC?->id],
            ['kode' => 'A11.54902', 'name' => 'Computer Vision',           'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSC?->id],
            ['kode' => 'A11.54903', 'name' => 'Neural Networks',           'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSC?->id],
            ['kode' => 'A11.54904', 'name' => 'Natural Language Processing','sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSC?->id],
            ['kode' => 'A11.54905', 'name' => 'Reinforcement Learning',    'sks' => 3, 'semester' => 7, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSC?->id],

            // ── Peminatan RPLD ───────────────────────────────────
            ['kode' => 'A11.54911', 'name' => 'Software Architecture', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanRPLD?->id],
            ['kode' => 'A11.54912', 'name' => 'Data Warehouse',        'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanRPLD?->id],
            ['kode' => 'A11.54913', 'name' => 'Software Testing',      'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanRPLD?->id],
            ['kode' => 'A11.54914', 'name' => 'Business Intelligence',  'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanRPLD?->id],
            ['kode' => 'A11.54915', 'name' => 'DevOps Engineering',     'sks' => 3, 'semester' => 7, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanRPLD?->id],

            // ── Peminatan SK3D ───────────────────────────────────
            ['kode' => 'A11.54921', 'name' => 'Network Security',          'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSK3D?->id],
            ['kode' => 'A11.54922', 'name' => 'Embedded Systems',          'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSK3D?->id],
            ['kode' => 'A11.54923', 'name' => 'Cryptography',              'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSK3D?->id],
            ['kode' => 'A11.54924', 'name' => 'High Performance Computing','sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSK3D?->id],
            ['kode' => 'A11.54925', 'name' => 'Penetration Testing',       'sks' => 3, 'semester' => 7, 'tipe_mk' => 'peminatan', 'peminatan_id' => $peminatanSK3D?->id],
        ];

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

        $this->command->info('✔ MataKuliahSeeder: ' . count($mataKuliahs) . ' mata kuliah di-seed untuk prodi ' . $prodi->nama . '.');
    }
}
