# Role: Koor (Coordinator / Head of Study Program)

## Deskripsi
Role Koor adalah untuk pengguna yang bertugas sebagai koordinator atau kepala program studi. Koor memiliki akses penuh untuk memantau **semua mahasiswa** di program studi, melakukan analisis menyeluruh, mengelola status EWS, dan menindaklanjuti mahasiswa yang memerlukan perhatian khusus.

## Hak Akses
- Middleware: `auth:sanctum`, `role:koor`
- Prefix Route: `/api/ews/koor`
- Scope: **Semua mahasiswa dalam program studi**

---

## 1. DASHBOARD KOOR

### 1.1 Status Mahasiswa
**Endpoint:** `GET /api/ews/koor/status-mahasiswa`

**Fungsi:**
Menampilkan ringkasan status semua mahasiswa di program studi.

**Data yang Ditampilkan:**
- Total mahasiswa (exclude Lulus & DO)
- Jumlah mahasiswa Aktif
- Jumlah mahasiswa Cuti
- Jumlah mahasiswa Mangkir
- Jumlah mahasiswa DO (informasi)
- Jumlah mahasiswa Lulus (informasi)

**Logic:**
1. Query semua mahasiswa tanpa filter dosen wali
2. Exclude mahasiswa dengan status "Lulus" dan "DO" dari total
3. Group by status mahasiswa
4. Aggregasi dan hitung jumlah per status

---

### 1.2 Rata-rata IPK Per Angkatan
**Endpoint:** `GET /api/ews/koor/rata-ipk-per-angkatan`

**Fungsi:**
Menampilkan rata-rata IPK semua mahasiswa per tahun masuk.

**Data yang Ditampilkan:**
- Tahun Masuk
- Rata-rata IPK
- Jumlah Mahasiswa

**Logic:**
1. Query tabel `akademik_mahasiswa` untuk semua mahasiswa
2. Exclude mahasiswa Lulus dan DO
3. Filter IPK > 0 dan NOT NULL
4. Group by tahun_masuk
5. Hitung AVG(ipk) dan COUNT per angkatan
6. Urutkan dari angkatan terbaru

---

### 1.3 Status Kelulusan
**Endpoint:** `GET /api/ews/koor/status-kelulusan`

**Fungsi:**
Menampilkan distribusi status kelulusan semua mahasiswa.

**Data yang Ditampilkan:**
- Total mahasiswa
- Jumlah eligible (memenuhi syarat lulus)
- Jumlah tidak eligible

**Kriteria Eligible:**
- IPK > 2.0
- SKS Lulus >= 144
- Semua MK Wajib (Nasional, Fakultas, Prodi) selesai
- Tidak ada nilai E
- Nilai D tidak melebihi 5% dari SKS lulus

**Logic:**
1. Query `early_warning_system` untuk semua mahasiswa
2. Filter `status_kelulusan` (eligible/noneligible)
3. Exclude Lulus dan DO
4. Hitung jumlah per status

---

### 1.4 Tabel Ringkasan Mahasiswa Per Angkatan
**Endpoint:** `GET /api/ews/koor/table-ringkasan-mahasiswa`

**Fungsi:**
Menampilkan ringkasan statistik per angkatan dengan distribusi status.

**Parameter Query:**
- `perPage` (default: 10)

**Data yang Ditampilkan:**
- Tahun Masuk
- Jumlah Mahasiswa
- Status Akademik: Aktif, Cuti, Mangkir
- Rata-rata IPK
- Distribusi Status EWS: Tepat Waktu, Normal, Perhatian, Kritis

**Logic:**
1. Group mahasiswa by tahun_masuk
2. Hitung jumlah per status akademik
3. Hitung rata-rata IPK per angkatan
4. Hitung distribusi status EWS
5. Exclude Lulus dan DO
6. Paginate hasil

---

## 2. DATA MAHASISWA

### 2.1 Detail Angkatan
**Endpoint:** `GET /api/ews/koor/mahasiswa/detail-angkatan/{tahunMasuk}`

**Fungsi:**
Menampilkan daftar detail mahasiswa untuk satu angkatan tertentu.

**Parameter Query:**
- `search`: Pencarian nama atau NIM
- `perPage` (default: 10)

**Data yang Ditampilkan:**
- **Per Mahasiswa:**
  - NIM, Nama, Dosen Wali
  - Semester Aktif, Tahun Masuk
  - IPK, SKS Lulus
  - Status MK Wajib (Nasional, Fakultas, Prodi)
  - Nilai D Melebihi Batas?, Ada Nilai E?
  - Status EWS dan Status Kelulusan
  - SPS1, SPS2, SPS3

- **Statistik Angkatan:**
  - Total Mahasiswa
  - Rata-rata IPS per semester (1-14)
  - Distribusi Status EWS

**Logic:**
1. Query mahasiswa dengan tahun_masuk sesuai parameter
2. Join dengan early_warning_system, dosen, user
3. Filter pencarian jika ada
4. Hitung rata-rata IPS per semester untuk angkatan
5. Hitung distribusi status EWS
6. Paginate data mahasiswa

---

### 2.2 Detail Mahasiswa
**Endpoint:** `GET /api/ews/koor/mahasiswa/detail/{mahasiswaId}`

**Fungsi:**
Menampilkan detail lengkap satu mahasiswa.

**Data yang Ditampilkan:**
- **Informasi Dasar:**
  - ID, NIM, Nama, Status Mahasiswa

- **Dosen Wali:**
  - ID dan Nama Dosen Wali

- **Data Akademik:**
  - Semester Aktif, Tahun Masuk
  - IPK, SKS Tempuh, SKS Lulus
  - Status MK Wajib (Nasional, Fakultas, Prodi)
  - Nilai D Melebihi Batas?, Ada Nilai E?
  - Total SKS Nilai D
  - Maksimal SKS Nilai D yang diperbolehkan (5% dari SKS lulus)

- **Status EWS:**
  - Status current dan Status Kelulusan

- **IP Per Semester:**
  - IPS semester 1-14 (yang sudah terisi)

- **Mata Kuliah Nilai D:**
  - Kode, Nama, SKS, Nilai Huruf, Nilai Angka, Status
  - **Hanya nilai TERAKHIR per mata kuliah**

- **Mata Kuliah Nilai E:**
  - Kode, Nama, SKS, Nilai Huruf, Nilai Angka, Status
  - **Hanya nilai TERAKHIR per mata kuliah**

- **Riwayat SPS (Surat Peringatan Studi):**
  - SPS1: IPS semester 1 < 2.0
  - SPS2: IPS semester 2 < 2.0
  - SPS3: IPS semester 3 < 2.0 (Wajib rekomitmen)

**Logic:**
1. Query mahasiswa dengan relasi lengkap
2. Ambil IPS per semester dari `ips_mahasiswa`
3. Ambil mata kuliah D/E hanya yang nilai TERAKHIR (MAX id per matakuliah_id)
4. Compile riwayat SPS dari field SPS1, SPS2, SPS3 di EWS
5. Hitung total SKS nilai D dan batas maksimal

---

### 2.3 Semua Mahasiswa
**Endpoint:** `GET /api/ews/koor/mahasiswa/all`

**Fungsi:**
Menampilkan daftar semua mahasiswa dengan filter lengkap.

**Parameter Query:**
- `search`: Pencarian nama/NIM
- `mode`: 'simple' atau 'detailed'
- `perPage`: Pagination
- **Filters (mode simple):**
  - `status_mahasiswa`
  - `status_ews`
  - `status_kelulusan`
  - `tahun_masuk`
  - `semester_aktif`
  - `mk_nasional`, `mk_fakultas`, `mk_prodi`
  - `nilai_d_melebihi_batas`
  - `nilai_e`

**Data yang Ditampilkan:**
- **Mode Simple:** NIM, Nama, Dosen Wali (dengan filter)
- **Mode Detailed:** Semua field akademik (tanpa filter)

**Logic:**
1. Query mahasiswa sesuai mode
2. Apply filters hanya untuk mode simple
3. Exclude Lulus dan DO
4. Paginate hasil

---

## 3. STATUS MAHASISWA

### 3.1 Distribusi Status EWS
**Endpoint:** `GET /api/ews/koor/distribusi-status-ews`

**Fungsi:**
Menampilkan distribusi status EWS semua mahasiswa.

**Parameter Query:**
- `tahun_masuk`: Filter by angkatan (optional)

**Data yang Ditampilkan:**
- Total per status:
  - Tepat Waktu
  - Normal
  - Perhatian
  - Kritis

**Logic:**
1. Query `early_warning_system` dengan join mahasiswa
2. Filter by tahun_masuk jika ada
3. Exclude Lulus dan DO
4. Group by status
5. Count per status

---

### 3.2 Tabel Ringkasan Status
**Endpoint:** `GET /api/ews/koor/table-ringkasan-status`

**Fungsi:**
Menampilkan tabel ringkasan mahasiswa berdasarkan status per angkatan.

**Data yang Ditampilkan:**
- Tahun Masuk
- Jumlah Mahasiswa
- IPK < 2.0
- Mahasiswa Mangkir
- Mahasiswa Cuti
- Status Perhatian

**Logic:**
1. Group by tahun_masuk
2. Hitung jumlah per kondisi
3. Exclude Lulus dan DO
4. Urutkan dari angkatan terbaru

---

## 4. CAPAIAN MAHASISWA

### 4.1 Tren IPS All
**Endpoint:** `GET /api/ews/koor/tren-ips/all`

**Fungsi:**
Menampilkan tren IPS per angkatan (naik/turun/stabil).

**Parameter Query:**
- `tahun_masuk`: Filter angkatan (optional)

**Data yang Ditampilkan:**
- Tahun Masuk
- Jumlah Mahasiswa
- Tren IPS (naik/turun/stabil)
- Jumlah MK Gagal (nilai E)
- Jumlah MK Ulang (retake)

**Logic:**
1. Untuk setiap angkatan, ambil semester aktif mayoritas
2. Bandingkan rata-rata IPS semester (n-1) dengan (n-2)
3. Tentukan tren: naik jika IPS meningkat, turun jika menurun, stabil jika sama
4. Hitung jumlah mata kuliah gagal (nilai E)
5. Hitung jumlah mata kuliah yang diulang (muncul > 1x per mahasiswa)

---

### 4.2 Card Capaian Mahasiswa
**Endpoint:** `GET /api/ews/koor/card-capaian`

**Fungsi:**
Menampilkan card statistik capaian mahasiswa per angkatan.

**Parameter Query:**
- `tahun_masuk`: Filter angkatan (optional)

**Data yang Ditampilkan:**
- **Total:**
  - Total Mahasiswa
  - Total Turun IP
  - Total Naik IP

- **Per Angkatan:**
  - Tahun Masuk
  - Semester Aktif
  - Rata-rata IPS
  - Jumlah Mahasiswa
  - Mahasiswa Naik IP
  - Mahasiswa Turun IP
  - Mahasiswa Stabil IP

**Logic:**
1. Untuk setiap angkatan, ambil semester aktif mayoritas
2. Bandingkan IPS individual mahasiswa semester (n-1) vs (n-2)
3. Hitung jumlah naik, turun, stabil per mahasiswa
4. Aggregate untuk total

---

### 4.3 Top 10 Mata Kuliah Gagal
**Endpoint:** `GET /api/ews/koor/top-mk-gagal`

**Fungsi:**
Menampilkan 10 mata kuliah dengan jumlah mahasiswa gagal (nilai E) terbanyak.

**Data yang Ditampilkan:**
- Kode Mata Kuliah
- Nama Mata Kuliah
- Dosen Koordinator MK
- Jumlah Mahasiswa Gagal

**Logic:**
1. Query KHS dengan nilai E
2. Group by matakuliah_id
3. Count jumlah mahasiswa gagal
4. Join dengan Dosen untuk nama koordinator
5. Order by jumlah_gagal DESC
6. Limit 10

---

### 4.4 Mahasiswa dengan Mata Kuliah Gagal
**Endpoint:** `GET /api/ews/koor/mahasiswa/mk-gagal`

**Fungsi:**
Menampilkan daftar mahasiswa yang memiliki mata kuliah dengan nilai E (belum diperbaiki).

**Parameter Query:**
- `search`: Pencarian nama
- `nama_matkul`: Filter nama mata kuliah
- `kode_kelompok`: Filter kelompok MK
- `perPage`: Pagination

**Data yang Ditampilkan:**
- Nama Mahasiswa
- NIM
- Nama Mata Kuliah
- Kode Mata Kuliah
- Kode Kelompok
- Presensi/Absen

**Logic:**
1. Query KHS dengan subquery untuk mendapatkan nilai TERAKHIR per mata kuliah
2. Filter mahasiswa yang nilai terakhir adalah E (belum retake atau retake masih E)
3. Exclude Lulus dan DO
4. Apply filters
5. Paginate hasil

**Catatan:** Jika mahasiswa sudah retake dan tidak E lagi, TIDAK masuk daftar ini.

---

## 5. STATISTIK KELULUSAN

### 5.1 Card Statistik Kelulusan
**Endpoint:** `GET /api/ews/koor/statistik-kelulusan`

**Fungsi:**
Menampilkan card statistik syarat kelulusan mahasiswa.

**Parameter Query:**
- `tahun_masuk`: Filter angkatan (optional)

**Data yang Ditampilkan:**
- **Status Kelulusan:**
  - Eligible
  - Non-eligible

- **Status Akademik:**
  - Aktif
  - Mangkir
  - Cuti

- **Sebaran IPK:**
  - IPK < 2.5
  - IPK 2.5 - 3.0
  - IPK > 3.0

- **Pemenuhan MK Wajib:**
  - Jumlah selesai MK Nasional
  - Jumlah selesai MK Fakultas
  - Jumlah selesai MK Prodi

**Logic:**
1. Query akademik_mahasiswa dengan early_warning_system
2. Filter by tahun_masuk jika ada
3. Exclude Lulus dan DO
4. Hitung jumlah per kategori dengan CASE WHEN
5. Return single aggregated result

---

### 5.2 Tabel Statistik Kelulusan
**Endpoint:** `GET /api/ews/koor/table-statistik-kelulusan`

**Fungsi:**
Menampilkan tabel detail statistik kelulusan per angkatan.

**Parameter Query:**
- `perPage` (default: 10)

**Data yang Ditampilkan:**
- Tahun Masuk
- Jumlah Mahasiswa
- IPK < 2.0
- SKS < 144
- Nilai D Melebihi Batas
- Ada Nilai E
- Eligible
- Non-eligible
- IPK Rata-rata

**Logic:**
1. Group by tahun_masuk
2. Exclude Lulus dan DO
3. Hitung jumlah per kondisi syarat kelulusan
4. Hitung rata-rata IPK per angkatan
5. Paginate hasil

---

## 6. TINDAK LANJUT PRODI

### 6.1 Surat Rekomitmen
**Endpoint:** `GET /api/ews/koor/surat-rekomitmen`

**Fungsi:**
Menampilkan daftar mahasiswa yang mengajukan surat rekomitmen (SPS3).

**Parameter Query:**
- `search`: Pencarian ID tiket
- `tahun_masuk`: Filter angkatan
- `status_rekomitmen`: Filter status (yes/no)
- `perPage`: Pagination

**Data yang Ditampilkan:**
- ID Tiket
- Nama Mahasiswa
- NIM
- Tanggal Pengajuan
- Dosen Wali
- Status Tindak Lanjut (yes/no)
- Link Rekomitmen

**Logic:**
1. Query `early_warning_system` WHERE id_rekomitmen NOT NULL
2. Join dengan mahasiswa, user, dosen wali
3. Apply filters
4. Exclude Lulus dan DO
5. Order by tanggal pengajuan DESC
6. Paginate hasil

**Catatan:** Hanya mahasiswa yang sudah mengajukan rekomitmen (memiliki id_rekomitmen) yang muncul.

---

### 6.2 Update Status Rekomitmen
**Endpoint:** `PATCH /api/ews/koor/surat-rekomitmen/{id_rekomitmen}`

**Fungsi:**
Update status tindak lanjut surat rekomitmen.

**Request Body:**
```json
{
  "status_rekomitmen": "yes" // or "no"
}
```

**Logic:**
1. Find record EWS by id_rekomitmen
2. Update field status_rekomitmen
3. Save changes
4. Return success/error message

---

## 7. EARLY WARNING SYSTEM

### 7.1 Recalculate Status Satu Mahasiswa
**Endpoint:** `POST /api/ews/koor/mahasiswa/{mahasiswaId}/recalculate-status`

**Fungsi:**
Trigger recalculation status EWS untuk satu mahasiswa tertentu.

**Logic:**
1. Find akademik_mahasiswa by mahasiswaId
2. Call EwsService->updateStatus()
3. Update nilai_d_melebihi_batas dan nilai_e
4. Hitung status EWS berdasarkan logic
5. Hitung status_kelulusan
6. Update atau create record di early_warning_system
7. Return status baru

---

### 7.2 Recalculate Status Semua Mahasiswa
**Endpoint:** `POST /api/ews/koor/recalculate-all-status`

**Fungsi:**
Trigger recalculation status EWS untuk **semua mahasiswa**.

**Logic:**
1. Dispatch job `RecalculateAllEwsJob` ke queue
2. Job akan process semua akademik_mahasiswa
3. Exclude mahasiswa Lulus dan DO
4. Call EwsService->updateStatus() untuk setiap mahasiswa
5. Process dalam chunk 100 untuk efisiensi
6. Log error jika ada
7. Return total processed dan updated

**Catatan:** Operasi ini berjalan di background job untuk performa.

---

## Business Logic

### Scope
- Koor dapat melihat **semua mahasiswa** di program studi
- Tidak ada filter dosen_wali_id

### Exclude Rules
- Mahasiswa dengan `status_mahasiswa = "Lulus"` tidak dihitung dalam total aktif
- Mahasiswa dengan `status_mahasiswa = "DO"` tidak dihitung dalam total aktif
- Keduanya tetap ada di database tapi di-exclude dari perhitungan

### Nilai Terakhir
Untuk mata kuliah dengan nilai D/E yang ditampilkan adalah **nilai TERAKHIR** (MAX id per matakuliah_id), bukan semua history. Ini penting untuk mengetahui apakah mahasiswa sudah memperbaiki nilai atau belum.

### Tren Calculation
Tren IPS dihitung dengan membandingkan:
- Semester (n-1): Semester terakhir yang selesai
- Semester (n-2): Semester sebelumnya
- Naik: IPS (n-1) > IPS (n-2)
- Turun: IPS (n-1) < IPS (n-2)
- Stabil: IPS (n-1) = IPS (n-2)

---

## Model & Relationship

```
mahasiswa
├── user (1:1)
├── prodi (belongsTo)
├── akademik_mahasiswa (1:1)
│   ├── dosen_wali (belongsTo)
│   └── early_warning_system (1:1)
│       ├── status (tepat_waktu/normal/perhatian/kritis)
│       ├── status_kelulusan (eligible/noneligible)
│       ├── SPS1, SPS2, SPS3
│       ├── id_rekomitmen
│       ├── tanggal_pengajuan_rekomitmen
│       ├── status_rekomitmen
│       └── link_rekomitmen
├── ips_mahasiswa (1:1)
│   └── ips_1 to ips_14
└── khs_krs_mahasiswa (hasMany)
    ├── mata_kuliah (belongsTo)
    └── kelompok_mata_kuliah (belongsTo)
```

---

## Flow Penggunaan

1. **Login** → Koor login dengan credentials
2. **Dashboard** → Overview semua mahasiswa
3. **Analisis Angkatan** → Drill down per angkatan
4. **Detail Mahasiswa** → Analisis individual mendalam
5. **Monitoring Capaian** → Tren IPS dan MK gagal
6. **Statistik Kelulusan** → Evaluasi syarat kelulusan
7. **Tindak Lanjut** → Manage surat rekomitmen
8. **Recalculate Status** → Update status EWS

---

## Catatan Penting

- Koor memiliki **akses penuh** ke semua data mahasiswa
- Dapat **mengupdate status** melalui recalculation
- Dapat **mengelola tindak lanjut** surat rekomitmen
- Semua statistik **exclude mahasiswa Lulus dan DO**
- Background job untuk recalculate all untuk performa
- Mata kuliah D/E yang ditampilkan adalah **nilai terakhir** saja
