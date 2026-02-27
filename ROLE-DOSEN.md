# Role: Dosen (Academic Advisor / Lecturer)

## Deskripsi
Role Dosen adalah untuk pengguna yang bertugas sebagai dosen wali atau pembimbing akademik. Dosen dapat memantau mahasiswa yang menjadi bimbingannya (mahasiswa wali), melihat status akademik mereka, dan menganalisis performa mahasiswa bimbingan.

## Hak Akses
- Middleware: `auth:sanctum`, `role:dosen`
- Prefix Route: `/api/ews/dosen`
- Scope: **Hanya mahasiswa yang diwali oleh dosen tersebut**

## Fitur & Fungsi

### 1. Dashboard Dosen

#### 1.1 Status Mahasiswa
**Endpoint:** `GET /api/ews/dosen/status-mahasiswa`

**Fungsi:**
Menampilkan ringkasan status mahasiswa bimbingan berdasarkan status akademik.

**Data yang Ditampilkan:**
- Total mahasiswa bimbingan (exclude Lulus & DO)
- Jumlah mahasiswa Aktif
- Jumlah mahasiswa Cuti
- Jumlah mahasiswa Mangkir
- Jumlah mahasiswa DO (informasi)
- Jumlah mahasiswa Lulus (informasi)

**Logic:**
1. Filter mahasiswa berdasarkan `dosen_wali_id` dari dosen yang login
2. Exclude mahasiswa dengan status "Lulus" dan "DO" dari total
3. Group by status mahasiswa dan hitung jumlahnya
4. Aggregasi data untuk dashboard

---

#### 1.2 Rata-rata IPK Per Angkatan
**Endpoint:** `GET /api/ews/dosen/rata-ipk-per-angkatan`

**Fungsi:**
Menampilkan rata-rata IPK mahasiswa bimbingan per tahun masuk (angkatan).

**Data yang Ditampilkan:**
- Tahun Masuk
- Rata-rata IPK
- Jumlah Mahasiswa

**Logic:**
1. Query tabel `akademik_mahasiswa` untuk mahasiswa dengan `dosen_wali_id` sesuai
2. Exclude mahasiswa yang sudah Lulus dan DO
3. Group by `tahun_masuk`
4. Hitung `AVG(ipk)` dan `COUNT(*)` per angkatan
5. Urutkan dari angkatan terbaru ke terlama

---

#### 1.3 Status Kelulusan
**Endpoint:** `GET /api/ews/dosen/status-kelulusan`

**Fungsi:**
Menampilkan distribusi status kelulusan mahasiswa bimbingan.

**Data yang Ditampilkan:**
- Total mahasiswa
- Jumlah eligible (memenuhi syarat lulus)
- Jumlah tidak eligible (belum memenuhi syarat lulus)

**Kriteria Eligible:**
- IPK > 2.0
- SKS Lulus >= 144
- Semua MK Wajib (Nasional, Fakultas, Prodi) selesai
- Tidak ada nilai E
- Nilai D tidak melebihi 5% dari SKS lulus

**Logic:**
1. Query `early_warning_system` untuk mahasiswa dengan dosen wali sesuai
2. Filter berdasarkan `status_kelulusan` (eligible/noneligible)
3. Exclude yang sudah Lulus dan DO
4. Hitung jumlah masing-masing status

---

#### 1.4 Tabel Ringkasan Mahasiswa Per Angkatan
**Endpoint:** `GET /api/ews/dosen/table-ringkasan-mahasiswa`

**Fungsi:**
Menampilkan ringkasan mahasiswa bimbingan per angkatan dengan distribusi status EWS.

**Parameter Query:**
- `perPage` (optional): Jumlah data per halaman (default: 10)

**Data yang Ditampilkan:**
- Tahun Masuk
- Total Mahasiswa
- Jumlah Status Tepat Waktu
- Jumlah Status Normal
- Jumlah Status Perhatian
- Jumlah Status Kritis

**Logic:**
1. Paginate tahun masuk untuk mahasiswa dengan dosen wali sesuai
2. Untuk setiap angkatan, hitung distribusi status EWS
3. Exclude mahasiswa Lulus dan DO
4. Urutkan dari angkatan terbaru

---

### 2. Data Mahasiswa

#### 2.1 Detail Angkatan
**Endpoint:** `GET /api/ews/dosen/mahasiswa/detail-angkatan/{tahunMasuk}`

**Fungsi:**
Menampilkan daftar detail mahasiswa bimbingan untuk satu angkatan tertentu.

**Parameter Query:**
- `search` (optional): Pencarian berdasarkan nama atau NIM
- `perPage` (optional): Jumlah data per halaman (default: 10)

**Data yang Ditampilkan:**
- **Data Per Mahasiswa:**
  - NIM, Nama, Dosen Wali
  - Semester Aktif, Tahun Masuk
  - IPK, SKS Lulus
  - Status MK Wajib (Nasional, Fakultas, Prodi)
  - Nilai D Melebihi Batas?, Ada Nilai E?
  - Status EWS dan Status Kelulusan
  - SPS1, SPS2, SPS3 (Surat Peringatan/Status)

- **Statistik Angkatan:**
  - Total Mahasiswa
  - Rata-rata IPS per semester (semester 1-14)

**Logic:**
1. Query mahasiswa dengan `tahun_masuk` dan `dosen_wali_id` sesuai
2. Join dengan `early_warning_system` untuk status
3. Filter pencarian jika ada (nama atau NIM)
4. Hitung rata-rata IPS per semester untuk angkatan tersebut
5. Exclude mahasiswa Lulus dan DO

---

#### 2.2 Detail Mahasiswa
**Endpoint:** `GET /api/ews/dosen/mahasiswa/detail/{mahasiswaId}`

**Fungsi:**
Menampilkan detail lengkap satu mahasiswa bimbingan.

**Data yang Ditampilkan:**
- Informasi Dasar: NIM, Nama, Email, Prodi, Status
- Data Akademik: IPK, SKS, Semester Aktif, Tahun Masuk
- Dosen Wali
- Status EWS dan Status Kelulusan
- IPS per semester (1-14)
- IPK kumulatif per semester
- Peringatan dan rekomendasi

**Logic:**
1. Validasi mahasiswa adalah bimbingan dosen yang login
2. Query data lengkap dengan semua relasi
3. Hitung IPK kumulatif berdasarkan IPS
4. Susun rekomendasi berdasarkan status

---

#### 2.3 Semua Mahasiswa Bimbingan
**Endpoint:** `GET /api/ews/dosen/mahasiswa/all`

**Fungsi:**
Menampilkan daftar semua mahasiswa yang dibimbing.

**Parameter Query:**
- `search` (optional): Pencarian nama/NIM
- `status_ews` (optional): Filter status EWS
- `tahun_masuk` (optional): Filter angkatan
- `perPage` (optional): Pagination

**Data yang Ditampilkan:**
- NIM, Nama
- Tahun Masuk, Semester Aktif
- IPK
- Status EWS
- Status Kelulusan

---

### 3. Status Mahasiswa

#### 3.1 Distribusi Status EWS
**Endpoint:** `GET /api/ews/dosen/distribusi-status-ews`

**Fungsi:**
Menampilkan distribusi status EWS mahasiswa bimbingan.

**Data yang Ditampilkan:**
- Total mahasiswa per status:
  - Tepat Waktu
  - Normal
  - Perhatian
  - Kritis

**Logic:**
1. Query `early_warning_system` untuk mahasiswa dengan dosen wali sesuai
2. Group by status
3. Hitung jumlah per status
4. Exclude Lulus dan DO

---

#### 3.2 Tabel Ringkasan Status
**Endpoint:** `GET /api/ews/dosen/table-ringkasan-status`

**Fungsi:**
Menampilkan tabel ringkasan mahasiswa berdasarkan kombinasi status EWS dan angkatan.

**Parameter Query:**
- `status` (optional): Filter status EWS tertentu
- `perPage` (optional): Pagination

**Data yang Ditampilkan:**
- Status EWS
- Tahun Masuk
- Daftar mahasiswa dengan status tersebut

---

### 4. Statistik Kelulusan

#### 4.1 Card Statistik Kelulusan
**Endpoint:** `GET /api/ews/dosen/statistik-kelulusan`

**Fungsi:**
Menampilkan statistik kelulusan mahasiswa bimbingan.

**Data yang Ditampilkan:**
- Total mahasiswa per kategori:
  - Lulus Tepat Waktu (≤ 4 tahun)
  - Lulus Normal (4-5 tahun)
  - Lulus Terlambat (> 5 tahun)
  - Belum Lulus
- Persentase masing-masing

**Logic:**
1. Hitung durasi studi berdasarkan tahun masuk
2. Klasifikasikan lulus berdasarkan durasi
3. Hitung persentase

---

#### 4.2 Tabel Statistik Kelulusan
**Endpoint:** `GET /api/ews/dosen/table-statistik-kelulusan`

**Fungsi:**
Menampilkan tabel detail statistik kelulusan per angkatan.

**Parameter Query:**
- `perPage` (optional): Pagination

**Data yang Ditampilkan:**
- Per Angkatan:
  - Total mahasiswa
  - Lulus tepat waktu
  - Lulus normal
  - Lulus terlambat
  - Belum lulus
  - Rata-rata IPK lulusan

---

## Model & Relationship

### Dosen Model
```
dosen
├── user (1:1) - Data user/login
├── prodi (belongsTo) - Program studi
└── mahasiswa_wali (hasMany through akademik_mahasiswa)
    └── akademik_mahasiswa (where dosen_wali_id)
        ├── mahasiswa
        ├── early_warning_system
        └── ips_mahasiswa
```

---

## Business Logic

### Scope Filter
Dosen **hanya dapat melihat data mahasiswa yang dibimbing** (mahasiswa dengan `dosen_wali_id` sesuai dengan ID dosen yang login).

### Exclude Rules
Dalam semua perhitungan dashboard dan statistik:
- Mahasiswa dengan `status_mahasiswa = "Lulus"` **tidak** dihitung dalam total aktif
- Mahasiswa dengan `status_mahasiswa = "DO"` **tidak** dihitung dalam total aktif

### IPK Calculation
IPK rata-rata dihitung hanya untuk mahasiswa dengan:
- IPK > 0
- IPK NOT NULL

---

## Flow Penggunaan

1. **Login** → Dosen login dengan credentials
2. **Dashboard** → Melihat ringkasan mahasiswa bimbingan
3. **Detail Angkatan** → Melihat mahasiswa per angkatan
4. **Detail Mahasiswa** → Analisis mendalam satu mahasiswa
5. **Monitoring Status** → Memantau distribusi status EWS
6. **Statistik Kelulusan** → Evaluasi performa bimbingan

---

## Catatan Penting

- Dosen **hanya bisa melihat mahasiswa bimbingannya sendiri**
- Data bersifat **read-only**, dosen tidak dapat mengubah status atau data
- Rekomendasi dan peringatan ditampilkan berdasarkan status EWS
- Semua perhitungan mengecualikan mahasiswa Lulus dan DO
- `dosen_wali_id` diambil dari relasi user → dosen saat login
