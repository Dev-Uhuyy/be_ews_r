# Role: Mahasiswa (Student)

## Deskripsi
Role Mahasiswa adalah untuk pengguna yang terdaftar sebagai mahasiswa dalam sistem Early Warning System (EWS). Mahasiswa dapat melihat informasi akademik mereka sendiri, status EWS, dan peringatan akademik.

## Hak Akses
- Middleware: `auth:sanctum`, `role:mahasiswa`
- Prefix Route: `/api/ews/mahasiswa`

## Fitur & Fungsi

### 1. Dashboard Mahasiswa
**Endpoint:** `GET /api/ews/mahasiswa/dashboard`

**Fungsi:**
Menampilkan ringkasan informasi akademik mahasiswa yang sedang login.

**Data yang Ditampilkan:**
- **Informasi Mahasiswa:**
  - ID, NIM, Nama, Email
  - Prodi
  - Status Mahasiswa (Aktif, Cuti, Mangkir, dll)

- **Informasi Akademik:**
  - IPK (Indeks Prestasi Kumulatif) saat ini
  - Perubahan IPK (naik/turun/tetap) dibandingkan semester sebelumnya
  - SKS Lulus (SKS yang sudah diselesaikan)
  - SKS Tempuh (total SKS yang pernah diambil)
  - SKS Now (SKS semester berjalan)
  - Semester Aktif
  - Tahun Masuk

- **Status EWS:**
  - Status current: Tepat Waktu, Normal, Perhatian, atau Kritis

- **Dosen Wali:**
  - Nama dosen wali yang membimbing

- **IPS Per Semester:**
  - Grafik/tabel IPS (Indeks Prestasi Semester) dari semester 1 hingga semester aktif

- **IPK Per Semester:**
  - Grafik/tabel IPK kumulatif dari semester 1 hingga semester aktif

**Logic:**
1. Mengambil data mahasiswa berdasarkan `user_id` yang sedang login
2. Menggabungkan data dari tabel: `mahasiswa`, `akademik_mahasiswa`, `ips_mahasiswa`, `early_warning_system`, `dosen`
3. Menghitung perubahan IPK dengan membandingkan IPS semester sekarang dengan semester sebelumnya
4. Menyusun data IPS dan IPK kumulatif per semester

---

### 2. Card Status Akademik
**Endpoint:** `GET /api/ews/mahasiswa/card-status-akademik`

**Fungsi:**
Menampilkan detail status akademik mahasiswa termasuk SKS per semester dan mata kuliah dengan nilai D/E.

**Data yang Ditampilkan:**
- **Status EWS:** Status terkini (Tepat Waktu/Normal/Perhatian/Kritis)
- **SKS Per Semester:** Jumlah SKS yang diambil di setiap semester
- **Mata Kuliah dengan Nilai D atau E:**
  - Kode Mata Kuliah
  - Nama Mata Kuliah
  - SKS
  - Nilai Huruf
  - Semester ambil

**Logic:**
1. Mengambil data mahasiswa dengan relasi `akademikmahasiswa`, `early_warning_systems`, `khskrsmahasiswa`
2. Menghitung jumlah SKS per semester dari KHS dengan status 'B' (Baru, bukan Ulang)
3. Memfilter mata kuliah yang memiliki nilai D atau E
4. Mengurutkan data berdasarkan semester

---

### 3. KHS/KRS Mahasiswa
**Endpoint:** `GET /api/ews/mahasiswa/khs-krs`

**Fungsi:**
Menampilkan daftar semua mata kuliah yang pernah diambil mahasiswa (KHS/KRS).

**Parameter Query:**
- `search` (optional): Pencarian berdasarkan nama/kode mata kuliah
- `semester` (optional): Filter berdasarkan semester
- `perPage` (optional): Jumlah data per halaman (default: 10)

**Data yang Ditampilkan:**
- ID KHS/KRS
- Kode Mata Kuliah
- Nama Mata Kuliah
- SKS
- Kelompok Mata Kuliah (Nasional/Fakultas/Prodi)
- Semester Ambil
- Nilai Akhir (Huruf)
- Status (B=Baru, U=Ulang)

**Logic:**
1. Query data dari tabel `khs_krs_mahasiswa` berdasarkan `mahasiswa_id`
2. Join dengan `mata_kuliahs` dan `kelompok_mata_kuliah`
3. Filter berdasarkan search dan semester jika ada
4. Pagination hasil
5. Urutkan berdasarkan semester dan kode mata kuliah

---

### 4. Detail KHS/KRS
**Endpoint:** `GET /api/ews/mahasiswa/khs-krs/{khsKrsId}`

**Fungsi:**
Menampilkan detail lengkap dari satu mata kuliah yang diambil.

**Data yang Ditampilkan:**
- Informasi mata kuliah lengkap
- Nilai komponen (UTS, UAS, Tugas, dll jika ada)
- Nilai akhir angka dan huruf
- Status pengambilan (Baru/Ulang)
- Semester pengambilan

---

### 5. Peringatan
**Endpoint:** `GET /api/ews/mahasiswa/peringatan`

**Fungsi:**
Menampilkan peringatan/warning akademik yang perlu diperhatikan mahasiswa.

**Data yang Ditampilkan:**
- Status EWS dan penjelasannya
- Peringatan jika status Perhatian atau Kritis
- Saran/rekomendasi tindakan
- Informasi penting terkait status kelulusan

**Logic:**
1. Mengambil status EWS terkini dari `early_warning_system`
2. Menentukan pesan peringatan berdasarkan status:
   - **Tepat Waktu (Biru):** Mahasiswa on track untuk lulus 4 tahun
   - **Normal (Hijau):** Mahasiswa dalam kondisi normal, berpotensi lulus 4 tahun
   - **Perhatian (Kuning):** Mahasiswa berisiko tidak lulus tepat waktu
   - **Kritis (Merah):** Mahasiswa berisiko tinggi DO atau tidak lulus 7 tahun
3. Memberikan saran sesuai kondisi

---

## Model & Relationship

### Mahasiswa Model
```
mahasiswa
├── user (1:1) - Data user/login
├── prodi (belongsTo) - Program studi
├── akademikmahasiswa (1:1) - Data akademik
│   ├── dosen_wali (belongsTo) - Dosen pembimbing akademik
│   └── early_warning_systems (1:1) - Status EWS
├── ipsmahasiswa (1:1) - IPS per semester
└── khskrsmahasiswa (hasMany) - KHS/KRS
    ├── mata_kuliah (belongsTo)
    └── kelompok_mata_kuliah (belongsTo)
```

---

## Flow Penggunaan

1. **Login** → Mahasiswa login dengan credentials
2. **Dashboard** → Melihat overview akademik dan status EWS
3. **Status Akademik** → Melihat detail SKS dan nilai D/E
4. **KHS/KRS** → Melihat history mata kuliah yang diambil
5. **Peringatan** → Membaca peringatan dan saran akademik

---

## Catatan Penting

- Mahasiswa **hanya bisa melihat data mereka sendiri**
- Data yang ditampilkan bersifat **read-only**, tidak ada fitur update
- Semua data diambil berdasarkan `user_id` yang sedang login
- Status EWS diupdate secara otomatis oleh sistem atau oleh coordinator
- Peringatan ditampilkan berdasarkan status EWS terkini
