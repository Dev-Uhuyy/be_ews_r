<?php

namespace Database\Seeders;

use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class MataKuliahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodi = Prodi::first();

        if (!$prodi) {
            $this->command->error('Prodi tidak ditemukan. Jalankan ProdiSeeder terlebih dahulu.');
            return;
        }

        $mataKuliahs = [
            // Semester 1
            ['kode' => 'A11.54101', 'nama' => 'Dasar Pemrograman', 'sks' => 4, 'semester' => 1, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54102', 'nama' => 'Matematika Diskrit', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54103', 'nama' => 'Sistem Digital', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54104', 'nama' => 'Dasar Sistem Komputer', 'sks' => 3, 'semester' => 1, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54105', 'nama' => 'Bahasa Inggris', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'nasional'],
            ['kode' => 'A11.54106', 'nama' => 'Pendidikan Pancasila', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'nasional'],
            ['kode' => 'A11.54107', 'nama' => 'Pengantar Teknologi Informasi', 'sks' => 2, 'semester' => 1, 'tipe_mk' => 'fakultas'],

            // Semester 2
            ['kode' => 'A11.54201', 'nama' => 'Algoritma dan Struktur Data', 'sks' => 4, 'semester' => 2, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54202', 'nama' => 'Matematika Informatika', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54203', 'nama' => 'Organisasi dan Arsitektur Komputer', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54204', 'nama' => 'Aljabar Linier', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54205', 'nama' => 'Pemrograman Berorientasi Objek', 'sks' => 3, 'semester' => 2, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54206', 'nama' => 'Pendidikan Kewarganegaraan', 'sks' => 2, 'semester' => 2, 'tipe_mk' => 'nasional'],

            // Semester 3
            ['kode' => 'A11.54301', 'nama' => 'Basis Data', 'sks' => 4, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54302', 'nama' => 'Sistem Operasi', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54303', 'nama' => 'Jaringan Komputer', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54304', 'nama' => 'Statistika dan Probabilitas', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'fakultas'],
            ['kode' => 'A11.54305', 'nama' => 'Pemrograman Web', 'sks' => 3, 'semester' => 3, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54306', 'nama' => 'Pendidikan Agama', 'sks' => 2, 'semester' => 3, 'tipe_mk' => 'nasional'],

            // Semester 4
            ['kode' => 'A11.54401', 'nama' => 'Desain dan Analisis Algoritma', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54402', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 4, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54403', 'nama' => 'Interaksi Manusia dan Komputer', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54404', 'nama' => 'Teori Bahasa dan Otomata', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54405', 'nama' => 'Keamanan Sistem Informasi', 'sks' => 3, 'semester' => 4, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54406', 'nama' => 'Metode Penelitian', 'sks' => 2, 'semester' => 4, 'tipe_mk' => 'fakultas'],

            // Semester 5
            ['kode' => 'A11.54501', 'nama' => 'Machine Learning', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54502', 'nama' => 'Pemrograman Mobile', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54503', 'nama' => 'Data Mining', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54504', 'nama' => 'Sistem Terdistribusi', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54505', 'nama' => 'Grafika Komputer', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54506', 'nama' => 'Manajemen Proyek TI', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'prodi'],

            // Semester 6
            ['kode' => 'A11.54601', 'nama' => 'Deep Learning', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54602', 'nama' => 'Cloud Computing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54603', 'nama' => 'Kecerdasan Buatan', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54604', 'nama' => 'Pemrosesan Citra Digital', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54605', 'nama' => 'Komputasi Paralel', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54606', 'nama' => 'Etika Profesi', 'sks' => 2, 'semester' => 6, 'tipe_mk' => 'fakultas'],

            // Semester 7
            ['kode' => 'A11.54701', 'nama' => 'Pengolahan Bahasa Alami', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54702', 'nama' => 'Internet of Things', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54703', 'nama' => 'Big Data', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54704', 'nama' => 'Cyber Security', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54705', 'nama' => 'Blockchain Technology', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54706', 'nama' => 'Technopreneurship', 'sks' => 2, 'semester' => 7, 'tipe_mk' => 'fakultas'],

            // Semester 8
            ['kode' => 'A11.54801', 'nama' => 'Kerja Praktik', 'sks' => 2, 'semester' => 8, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54802', 'nama' => 'Tugas Akhir', 'sks' => 6, 'semester' => 8, 'tipe_mk' => 'prodi'],
            ['kode' => 'A11.54803', 'nama' => 'Seminar', 'sks' => 2, 'semester' => 8, 'tipe_mk' => 'prodi'],

            // Mata Kuliah Peminatan - SC (Software Computing) - ID 1
            ['kode' => 'A11.54901', 'nama' => 'Advanced Machine Learning', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => 1],
            ['kode' => 'A11.54902', 'nama' => 'Computer Vision', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => 1],
            ['kode' => 'A11.54903', 'nama' => 'Neural Networks', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => 1],
            ['kode' => 'A11.54904', 'nama' => 'Natural Language Processing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => 1],
            ['kode' => 'A11.54905', 'nama' => 'Reinforcement Learning', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'peminatan', 'peminatan_id' => 1],

            // Mata Kuliah Peminatan - RPLD (Rekayasa Perangkat Lunak dan Data) - ID 2
            ['kode' => 'A11.54911', 'nama' => 'Software Architecture', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => 2],
            ['kode' => 'A11.54912', 'nama' => 'Data Warehouse', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => 2],
            ['kode' => 'A11.54913', 'nama' => 'Software Testing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => 2],
            ['kode' => 'A11.54914', 'nama' => 'Business Intelligence', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => 2],
            ['kode' => 'A11.54915', 'nama' => 'DevOps Engineering', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'peminatan', 'peminatan_id' => 2],

            // Mata Kuliah Peminatan - SKKKD (Sistem Komputer, Keamanan, Komputasi Data) - ID 3
            ['kode' => 'A11.54921', 'nama' => 'Network Security', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => 3],
            ['kode' => 'A11.54922', 'nama' => 'Embedded Systems', 'sks' => 3, 'semester' => 5, 'tipe_mk' => 'peminatan', 'peminatan_id' => 3],
            ['kode' => 'A11.54923', 'nama' => 'Cryptography', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => 3],
            ['kode' => 'A11.54924', 'nama' => 'High Performance Computing', 'sks' => 3, 'semester' => 6, 'tipe_mk' => 'peminatan', 'peminatan_id' => 3],
            ['kode' => 'A11.54925', 'nama' => 'Penetration Testing', 'sks' => 3, 'semester' => 7, 'tipe_mk' => 'peminatan', 'peminatan_id' => 3],
        ];

        foreach ($mataKuliahs as $mk) {
            MataKuliah::updateOrCreate(
                ['kode' => $mk['kode'], 'prodi_id' => $prodi->id],
                [
                    'name' => $mk['nama'],
                    'sks' => $mk['sks'],
                    'semester' => $mk['semester'],
                    'tipe_mk' => $mk['tipe_mk'],
                    'peminatan_id' => $mk['peminatan_id'] ?? null,
                ]
            );
        }

        $this->command->info('Berhasil membuat ' . count($mataKuliahs) . ' mata kuliah!');
    }
}
