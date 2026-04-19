# SISTEM EARLY WARNING SYSTEM (EWS)
## Fakultas Ilmu Komputer — Universitas Dian Nuswantoro

---

**Versi Dokumen:** 1.0  
**Tanggal:** April 2026  
**Proyek:** Pengembangan Sistem Early Warning System untuk Top FIK  
**Backend Repository:** `be_ews_r`

---

## DAFTAR ISI

1. [PENDAHULUAN](#1-pendahuluan)
2. [ARSITEKTUR SISTEM](#2-arsitektur-sistem)
3. [ENTITAS DAN DATA](#3-entitas-dan-data)
4. [ROLE DAN AKSES](#4-role-dan-akses)
5. [ALUR DATA (DATA FLOW)](#5-alur-data-data-flow)
6. [STATUS EWS (EARLY WARNING SYSTEM)](#6-status-ews-early-warning-system)
7. [WORKFLOW TINDAK LANJUT](#7-workflow-tindak-lanjut)
8. [ATURAN BUSINESS LOGIC](#8-aturan-business-logic)
9. [USER INTERFACE OVERVIEW](#9-user-interface-overview)
10. [KEAMANAN DAN AKSES](#10-keamanan-dan-akses)
11. [ENDPOINT REFERENCE (NON-TECHNICAL)](#11-endpoint-reference-non-technical)
12. [KESIMPULAN](#12-kesimpulan)

---

## 1. PENDAHULUAN

### 1.1 Latar Belakang

Fakultas Ilmu Komputer (FIK) Universitas Dian Nuswantoro mengelola ribuan mahasiswa yang tersebar di empat program studi. Dengan jumlah mahasiswa yang besar, dosen pengajar dan pimpinan fakultas menghadapi tantangan dalam memantau dan mengidentifikasi mahasiswa yang berisiko mengalami kesulitan akademik secara tepat waktu.

Permasalahan utama yang sering dihadapi antara lain:

- **Terlambatnya deteksi** — Masalah akademik mahasiswa sering baru diketahui setelah terlambat, ketika sudah berada di semester akhir.
- **Tidak ada sistem monitoring terpusat** — Data akademik tersebar di berbagai sistem dan tidak terintegrasi dengan baik.
- **Kesulitan identifikasi cepat** — Tanpa sistem otomatis, sulit untuk membedakan mahasiswa yang sehat secara akademik dari yang membutuhkan perhatian khusus.
- **Tidak ada mekanisme预警** — Dosen wali dan pimpinan prodi tidak memiliki alat bantu untuk secara proaktif memberikan干预 sebelum situasi menjadi kritis.

Berdasarkan permasalahan tersebut, FIK membutuhkan sebuah sistem yang mampu memberikan **peringatan dini (early warning)** kepada dosen dan pimpinan fakultas mengenai mahasiswa mana saja yang berpotensi tidak lulus tepat waktu, sehingga intervensi dapat dilakukan lebih awal.

### 1.2 Tujuan Sistem

Sistem Early Warning System (EWS) dirancang untuk:

1. **Mendeteksi secara otomatis** mahasiswa yang berisiko tidak lulus tepat waktu berdasarkan indikator akademik terkini.
2. **Memberikan status peringatan** yang jelas dan terukur kepada dosen wali, Kaprodi, dan Dekan.
3. **Mendukung proses干预 akademik** melalui workflow tindak lanjut yang terstruktur.
4. **Menyediakan data dan laporan** yang dibutuhkan oleh pimpinan fakultas untuk pengambilan keputusan strategis.
5. **Memberdayakan mahasiswa** untuk memantau status akademik mereka sendiri secara mandiri.

### 1.3 Ruang Lingkup

Sistem EWS FIK UDINUS mencakup:

- **4 Program Studi** di lingkungan FIK: Teknik Informatika (A11), Sistem Informasi (A12), Desain Komunikasi Visual (A14), dan Animasi (A15).
- **3 Role Pengguna**: Dekan (fakultas), Kaprodi/Ketua Program Studi (prodi), dan Mahasiswa (pribadi).
- **14 semester akademik** sebagai batas maksimal masa studi.
- **Kelulusan tepat waktu**定义为 8 semester (4 tahun), dengan toleransi hingga 10 semester (5 tahun) untuk status Normal.
- **Data akademik**: IPS per semester, IPK kumulatif, SKS tempuh dan lulus, nilai mata kuliah, MK nasional/fakultas/prodi.

> **Catatan:** Sistem ini adalah sistem backend (API). Frontend/Tampilan antarmuka dikembangkan secara terpisah oleh tim frontend.

---

## 2. ARSITEKTUR SISTEM

### 2.1 Overview Diagram

```
┌──────────────────────────────────────────────────────────────────────┐
│                         EWS SYSTEM ARCHITECTURE                       │
│                    Fakultas Ilmu Komputer — UDINUS                    │
└──────────────────────────────────────────────────────────────────────┘

  ┌──────────────┐       ┌──────────────────┐       ┌─────────────────┐
  │   DEKAN      │       │     KAPRODI      │       │   MAHASISWA     │
  │  (Frontend)  │       │   (Frontend)    │       │   (Frontend)    │
  └──────┬───────┘       └──────┬───────────┘       └──────┬──────────┘
         │                      │                         │
         │  HTTPS/REST API      │                         │
         ▼                      ▼                         ▼
  ┌──────────────────────────────────────────────────────────────────┐
  │                    LARAVEL API GATEWAY                          │
  │  ┌─────────────┐  ┌──────────────┐  ┌────────────────────┐     │
  │  │ Auth        │  │ Role Middleware│  │ Scope Middleware   │     │
  │  │ Middleware   │  │               │  │                    │     │
  │  └─────────────┘  └──────────────┘  └────────────────────┘     │
  └──────────────────────────────────────────────────────────────────┘
         │                      │                         │
         ▼                      ▼                         ▼
  ┌────────────┐    ┌────────────────┐    ┌────────────────────┐
  │  Dekan     │    │   Kaprodi      │    │   Mahasiswa        │
  │  Controllers│   │   Controllers  │    │   Controllers       │
  └────────────┘    └────────────────┘    └────────────────────┘
         │                      │                         │
         ▼                      ▼                         ▼
  ┌──────────────────────────────────────────────────────────────────┐
  │                       EWS SERVICE LAYER                         │
  │  ┌─────────────┐  ┌──────────────┐  ┌────────────────────┐      │
  │  │ EwsService  │  │ ExportService │  │ NotificationService│      │
  │  │ (Core Logic) │  │ (Excel/PDF)   │  │ (Telegram/Firebase)│     │
  │  └─────────────┘  └──────────────┘  └────────────────────┘      │
  └──────────────────────────────────────────────────────────────────┘
         │                      │                         │
         ▼                      ▼                         ▼
  ┌──────────────────────────────────────────────────────────────────┐
  │                    LARAVEL ORM — ELOQUENT                        │
  │                                                                   │
  │  ┌────────────┐  ┌──────────────┐  ┌────────────┐  ┌─────────┐ │
  │  │ akademik_  │  │ early_       │  │ ips_       │  │ khs_krs │ │
  │  │ mahasiswa  │  │ warning_     │  │ mahasiswa  │  │ mahasiswa│ │
  │  │            │  │ system       │  │            │  │         │ │
  │  └────────────┘  └──────────────┘  └────────────┘  └─────────┘ │
  └──────────────────────────────────────────────────────────────────┘
         │
         ▼
  ┌──────────────────────────────────────────────────────────────────┐
  │                    DATABASE — MySQL / MariaDB                   │
  └──────────────────────────────────────────────────────────────────┘
```

### 2.2 Komponen Utama

| Komponen | Teknologi | Fungsi |
|----------|-----------|--------|
| **API Backend** | Laravel 10+ (PHP 8.2+) | Mengelola semua logika bisnis, autentikasi, dan otorisasi |
| **Database** | MySQL / MariaDB | Menyimpan seluruh data akademik, user, dan status EWS |
| **Authentication** | Laravel Sanctum (JWT-like) | Mengeluarkan dan memvalidasi token akses |
| **Export Engine** | PhpSpreadsheet (Excel) | Menghasilkan laporan dalam format .xlsx |
| **Queue Worker** | Laravel Queue (Redis/Database) | Menjalankan job perhitungan EWS secara batch |
| **Observer Pattern** | Laravel Observer | Auto-trigger recalculation saat data akademik berubah |

### 2.3 Database yang Digunakan

Sistem menggunakan **MySQL/MariaDB** sebagai database relational. Berikut tabel-tabel utama yang digunakan:

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Akun pengguna sistem (email, password, role) |
| `prodis` | Master Program Studi (A11, A12, A14, A15) |
| `mahasiswa` | Data mahasiswa (NIM, prodi, status mahasiswa) |
| `dosen` | Data dosen pengajar dan dosen wali |
| `akademik_mahasiswa` | Rekap akademik per mahasiswa (IPK, SKS tempuh/lulus, semester aktif) |
| `ips_mahasiswa` | IPS per semester (IPS-1 s/d IPS-14) |
| `khs_krs_mahasiswa` | Riwayat nilai per mata kuliah (KHS/KRS) |
| `mata_kuliahs` | Master mata kuliah (kode, nama, SKS, tipe MK) |
| `kelompok_mata_kuliah` | Kelas per mata kuliah (grup kelas) |
| `early_warning_system` | Status EWS per mahasiswa (Tepat Waktu/Normal/Perhatian/Kritis) |
| `tindak_lanjuts` | Data tindak lanjut mahasiswa (rekomitmen/pindah prodi) |

### 2.4 Struktur API

API EWS menggunakan pola **RESTful** dengan struktur prefix berikut:

```
POST   /api/login              → Login (public)
POST   /api/login-kaprodi      → Login khusus Kaprodi
POST   /api/login-dekan       → Login khusus Dekan
POST   /api/login-mahasiswa    → Login khusus Mahasiswa
GET    /api/profile            → Ambil data profil user yang login

EWS (Kaprodi & Dekan):
GET    /api/ews/kaprodi/dashboard           → Dashboard prodi
GET    /api/ews/dekan/dashboard            → Dashboard fakultas
GET    /api/ews/{role}/mahasiswa/all       → Daftar mahasiswa
GET    /api/ews/{role}/mahasiswa/detail/{id} → Detail mahasiswa
GET    /api/ews/{role}/distribusi-status-ews → Distribusi status EWS
GET    /api/ews/{role}/statistik-kelulusan  → Statistik kelulusan
GET    /api/ews/{role}/tren-ips/all        → Tren IPS per angkatan
GET    /api/ews/{role}/mahasiswa/mk-gagal   → Mahasiswa dengan MK gagal
POST   /api/ews/{role}/recalculate-all-status → Recalculate semua status
POST   /api/ews/{role}/mahasiswa/{id}/recalculate-status → Recalculate satu mahasiswa

EWS (Mahasiswa):
GET    /api/ews/mahasiswa/dashboard          → Dashboard pribadi
GET    /api/ews/mahasiswa/khs-krs            → Riwayat KHS/KRS
GET    /api/ews/mahasiswa/peringatan          → Status peringatan pribadi
GET    /api/ews/mahasiswa/tindak-lanjut      → Status tindak lanjut pribadi
POST   /api/ews/mahasiswa/tindak-lanjut      → Ajukan tindak lanjut
GET    /api/ews/mahasiswa/tindak-lanjut/template/{kategori} → Download template
```

---

## 3. ENTITAS DAN DATA

### 3.1 Mahasiswa

Mahasiswa adalah entitas utama dalam sistem EWS. Setiap mahasiswa memiliki:

**Data Pribadi:**
- NIM (Nomor Induk Mahasiswa) — unik per mahasiswa
- Nama lengkap
- Prodi/Jurusan
- Semester aktif saat ini
- Tahun masuk (angkatan)
- Minat/Peminatan (jika ada)
- Status mahasiswa: Aktif, Lulus, Cuti, Mangkir, DO (Drop Out), Tidak Aktif

**Data Akademik:**
- **IPK** (Indeks Prestasi Kumulatif) — rata-rata seluruh IPS
- **IPS-1 s/d IPS-14** — IPS per semester
- **SKS Tempuh** — total SKS yang pernah diambil (termasuk yang gagal)
- **SKS Lulus** — total SKS yang sudah lulus/tidak mengulang
- **SKS Gagal** — total SKS yang belum lulus (nilai D atau E)
- **Nilai E** — ada/tidak ada nilai E (ya/tidak)
- **Nilai D Melebihi Batas** — apakah total SKS nilai D melebihi 7.2 SKS (ya/tidak)
- **MK Nasional Selesai** — apakah semua MK nasional sudah lulus
- **MK Fakultas Selesai** — apakah semua MK fakultas sudah lulus
- **MK Prodi Selesai** — apakah semua MK prodi sudah lulus

**Relasi ke Tabel Lain:**

```
mahasiswa
  ├── user_id          → users (akun login mahasiswa)
  ├── prodi_id         → prodis (program studi)
  ├── akademik_mahasiswa (1:1) — rekap akademik
  ├── ips_mahasiswa   (1:1) — IPS per semester
  ├── khs_krs_mahasiswa (1:N) — riwayat nilai per MK
  └── early_warning_system (1:1) — status EWS
```

### 3.2 Dosen

Dosen di sistem EWS berperan dalam dua konteks:

**Sebagai Dosen Wali (Dosen Pembimbing Akademik):**
- Setiap mahasiswa memiliki satu dosen wali
- Dosen wali bertanggung jawab memantau dan memberikan bimbingan akademik kepada mahasiswa bimbingannya
- Dosen wali dapat melihat status EWS dan peringatan mahasiswa bimbingannya
- Dosen wali yang memiliki mahasiswa dengan status Kritis akan mendapatkan notifikasi

**Relasi dengan Mahasiswa:**

```
dosen
  ├── user_id              → users (akun login dosen)
  ├── prodi_id             → prodis
  └── akademik_mahasiswa (1:N) — mahasiswa bimbingan
```

### 3.3 Prodi (Program Studi)

FIK memiliki **4 Program Studi**:

| Kode Prodi | Nama Program Studi |
|------------|-------------------|
| A11 | Teknik Informatika |
| A12 | Sistem Informasi |
| A14 | Desain Komunikasi Visual |
| A15 | Animasi |

Setiap prodi memiliki:
- Nama dan kode prodi
- Daftar mahasiswa
- Daftar dosen pengajar
- Kurikulum/Mata kuliah
- Seorang Kaprodi (Ketua Program Studi) yang mengelola prodi tersebut

### 3.4 Mata Kuliah

Mata kuliah dikategorikan dalam **4 tipe**:

| Tipe MK | Keterangan | Contoh |
|---------|-----------|--------|
| **Nasional** | Mata kuliah wajib nasional (misalnya: Bahasa Indonesia, Pancasila) | Wajib untuk semua mahasiswa Indonesia |
| **Fakultas** | Mata kuliah wajib tingkat fakultas (FIK) | Dasar-dasar ilmu komputer, Matematika |
| **Prodi** | Mata kuliah wajib tingkat program studi | Algorithm & Programming, Database |
| **Peminatan** | Mata kuliah pilihan/peminatan khusus | Peminatan Jaringan, Peminatan AI |

**Relasi dengan Mahasiswa:**

```
mata_kuliahs
  ├── prodi_id             → prodis
  ├── koordinator_mk       → dosen (koordinator mata kuliah)
  ├── tipe_mk              → enum('nasional','fakultas','prodi','peminatan')
  └── kelompok_mata_kuliah (1:N)
        └── khs_krs_mahasiswa (1:N) → diambil oleh mahasiswa
```

**Keterangan Penting:** MK nasional, fakultas, dan prodi harus **selesai semua** (lulus) sebelum mahasiswa bisa dinyatakan eligible untuk lulus. Ini menjadi salah satu indikator dalam sistem EWS.

---

## 4. ROLE DAN AKSES

Sistem EWS menggunakan model **Role-Based Access Control (RBAC)** dengan 3 role utama. Setiap role memiliki scope akses yang berbeda.

### 4.1 Dekan

**Akses:** Seluruh fakultas (semua prodi)

Dekan memiliki visibility tertinggi dalam sistem EWS. Dekan dapat melihat dan menganalisis data seluruh fakultas, bukan hanya satu prodi.

**Fitur yang Dapat Diakses:**

**1. Dashboard Dekan**
- Ringkasan status mahasiswa seluruh fakultas
- Rata-rata IPK per angkatan
- Statistik kelulusan fakultas
- Distribusi status EWS per prodi (card statistik)

**2. Distribusi Status EWS**
- Melihat proporsi status EWS per prodi
- Kategori: Tepat Waktu, Normal, Perhatian, Kritis
- Perbandingan antar prodi

**3. Data Mahasiswa**
- Semua mahasiswa seluruh fakultas (bisa difilter berdasarkan prodi, angkatan, status EWS)
- Detail per mahasiswa
- Daftar mahasiswa dengan MK gagal
- Export data ke Excel

**4. Capaian Mahasiswa**
- Tren IPS per angkatan (grafik/chart)
- Card capaian per prodi
- Top 10 MK yang paling banyak gagal
- Export laporan Excel

**5. Statistik Kelulusan**
- Data kelulusan per prodi
- Perbandingan Eligible vs Non-Eligible
- Analisis IPK dan SKS

**6. Tindak Lanjut**
- Monitoring seluruh mahasiswa yang butuh intervensi
- Card statistik tindak lanjut seluruh fakultas
- Update status verifikasi tindak lanjut

**7. EWS Recalculation**
- Recalculate satu mahasiswa
- Recalculate semua mahasiswa (batch)

---

### 4.2 Kaprodi (Ketua Program Studi)

**Akses:** Prodi spesifik saja

Kaprodi mengelola dan memantau mahasiswa di program studi yang mereka pimpin. Kaprodi hanya dapat mengakses data mahasiswa yang berasal dari prodi mereka sendiri.

**Fitur yang Dapat Diakses:**

**1. Dashboard Prodi**
- Ringkasan status mahasiswa prodi sendiri
- Rata-rata IPK prodi
- Card statistik EWS prodi

**2. EWS Monitoring**
- Distribusi status EWS di prodi sendiri
- Daftar mahasiswa per status EWS
- Detail status mahasiswa

**3. Data Mahasiswa**
- Mahasiswa prodi sendiri (filterable)
- Mahasiswa dengan MK gagal di prodi sendiri
- Export data Excel

**4. Capaian & Statistik**
- Tren IPS prodi sendiri
- Statistik kelulusan prodi sendiri
- Top MK gagal di prodi

**5. Tindak Lanjut**
- Card statistik tindak lanjut prodi
- Daftar tindak lanjut mahasiswa prodi
- Update status verifikasi

**6. EWS Recalculation**
- Recalculate satu mahasiswa prodi
- Recalculate semua mahasiswa prodi

---

### 4.3 Mahasiswa

**Akses:** Data pribadi sendiri

Mahasiswa adalah pengguna paling sederhana dalam sistem ini. Mereka hanya dapat melihat dan mengelola data akademik mereka sendiri.

**Fitur yang Dapat Diakses:**

**1. Dashboard Pribadi**
- Status akademik pribadi (IPK, SKS tempuh/lulus)
- Semester aktif saat ini
- Status EWS pribadi
- Status kelulusan (Eligible/Non-Eligible)

**2. KHS-KRS**
- Riwayat nilai per mata kuliah (semester demi semester)
- Detail KHS per semester
- Export transkrip akademik ke Excel

**3. Peringatan Akademik**
- Melihat apakah ada peringatan akademik
- Kategori: SPS1, SPS2, SPS3 (Surat Peringatan Studi)
- Detail kondisi yang menyebabkan peringatan

**4. Tindak Lanjut**
- Status tindak lanjut pribadi
- Download template surat (rekomitmen/pindah prodi)
- Mengajukan tindak lanjut

---

### 4.4 Perbandingan Akses per Role

| Fitur | Dekan | Kaprodi | Mahasiswa |
|-------|-------|---------|-----------|
| Dashboard lintas prodi | ✅ | ❌ | ❌ |
| Dashboard prodi sendiri | ✅ | ✅ | ❌ |
| Dashboard pribadi | ❌ | ❌ | ✅ |
| Data mahasiswa lintas prodi | ✅ | ❌ | ❌ |
| Data mahasiswa prodi sendiri | ✅ | ✅ | ❌ |
| Data mahasiswa pribadi | ❌ | ❌ | ✅ |
| Distribusi status EWS | ✅ | ✅ | ❌ |
| Statistik kelulusan | ✅ | ✅ | ❌ |
| Capaian mahasiswa | ✅ | ✅ | ❌ |
| Tindak lanjut | ✅ | ✅ | ✅ |
| Recalculate EWS | ✅ | ✅ | ❌ |
| Export Excel | ✅ | ✅ | ❌ |
| KHS/KRS | ❌ | ❌ | ✅ |
| Download template surat | ❌ | ❌ | ✅ |

---

## 5. ALUR DATA (DATA FLOW)

### 5.1 Alur Login

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   User      │         │   Auth       │         │   Sistem    │
│ (Mahasiswa/ │ ──────▶ │   Controller │ ──────▶ │   (API)     │
│  Kaprodi/   │         │              │         │             │
│  Dekan)     │         │              │         │             │
└─────────────┘         └──────┬───────┘         └─────────────┘
                              │
                              ▼
                      ┌───────────────┐
                      │ Validasi      │
                      │ Email +       │
                      │ Password       │
                      └───────┬───────┘
                              │
                   ┌──────────┴──────────┐
                   │                     │
                   ▼                     ▼
            ┌────────────┐        ┌────────────┐
            │  INVALID   │        │   VALID    │
            │  Login     │        │  Login     │
            └─────┬──────┘        └─────┬──────┘
                  │                    │
                  ▼                    ▼
            ┌────────────┐      ┌─────────────────┐
            │  Error     │      │ Generate JWT    │
            │  Response  │      │ Token           │
            └────────────┘      └───────┬─────────┘
                                         │
                                         ▼
                               ┌─────────────────┐
                               │ Response:       │
                               │ - access_token  │
                               │ - role          │
                               │ - user data     │
                               │ - prodi scope   │
                               └─────────────────┘
```

**Langkah-langkah:**

1. **User input** email dan password
2. **Auth Controller** menerima request login
3. **Validasi kredensial** — cek email ada di database dan password cocok
4. Jika **invalid**: return error "Email atau password salah"
5. Jika **valid**: Generate token (JWT/Sanctum) yang berisi:
   - `user_id`
   - `role` (mahasiswa/kaprodi/dekan)
   - `prodi_id` (null untuk dekan, id prodi untuk kaprodi/mahasiswa)
   - `exp` (token expiration)
6. **Simpan token** ke database (`access_token` di tabel `users`)
7. **Return response** berisi token dan data profil user

### 5.2 Alur Dashboard Dekan

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   Dekan     │         │   Dashboard  │         │   EWS       │
│  (Frontend) │ ──────▶ │   Controller │ ──────▶ │   Service   │
└─────────────┘         └──────┬───────┘         └──────┬──────┘
                               │                         │
                               ▼                         ▼
                      ┌──────────────────┐      ┌─────────────┐
                      │ Load Prodi List   │      │ Load all    │
                      │ (4 Prodi FIK)    │      │ mahasiswa   │
                      └────────┬─────────┘      └──────┬──────┘
                               │                        │
                               ▼                        ▼
                      ┌─────────────────────────────────────────┐
                      │   LOOP: Per Prodi                        │
                      │   ┌─────────────┐  ┌──────────────┐      │
                      │   │ Hitung jml  │  │ Hitung rata  │      │
                      │   │ mahasiswa  │  │ IPK per prodi│      │
                      │   └─────────────┘  └──────────────┘      │
                      │   ┌─────────────┐  ┌──────────────┐      │
                      │   │ Distribusi │  │ Statistik    │      │
                      │   │ Status EWS  │  │ Kelulusan    │      │
                      │   └─────────────┘  └──────────────┘      │
                      └──────────────────────┬───────────────────┘
                                             │
                                             ▼
                                    ┌─────────────────┐
                                    │  Aggregate       │
                                    │  per Prodi       │
                                    │  Combine result  │
                                    └────────┬─────────┘
                                             │
                                             ▼
                                    ┌─────────────────┐
                                    │  Response JSON  │
                                    │  Dashboard Data │
                                    └─────────────────┘
```

**Langkah-langkah:**

1. Dekan mengakses `/api/ews/dekan/dashboard`
2. Sistem **otentikasi token** → valid? lanjut
3. Sistem **load daftar 4 prodi** di FIK
4. **Loop per prodi** — untuk setiap prodi:
   - Hitung jumlah mahasiswa keseluruhan
   - Hitung rata-rata IPK
   - Hitung distribusi status EWS (Tepat Waktu, Normal, Perhatian, Kritis)
   - Hitung statistik kelulusan (Eligible vs Non-Eligible)
   - Hitung total SKS rata-rata
5. **Aggregate** hasil per prodi ke dalam satu response
6. **Return JSON** berisi data dashboard lengkap

### 5.3 Alur Perhitungan EWS (Calculation Flow)

Ini adalah **otak utama** sistem EWS — bagaimana status peringatan ditentukan.

```
┌─────────────────┐
│   TRIGGER       │
│ (Manual/Batch/  │
│   Auto)         │
└────────┬────────┘
         │
         ▼
┌────────────────────────┐
│ Load Data Akademik     │
│ - sks_lulus            │
│ - semester_aktif       │
│ - ips_mahasiswa        │
│ - khs_krs_mahasiswa    │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 1: Hitung IPS     │
│ semester terbaru       │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 2: Cek SKS tempuh │
│ vs SKS lulus           │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 3: Cek nilai D & E│
│ - Hitung jml SKS nilai D│
│ - Hitung ada/tidak E    │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 4: Cek NFU        │
│ - MK Nasional done?    │
│ - MK Fakultas done?    │
│ - MK Prodi done?       │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 5: Cek MK         │
│ Nasional/Fakultas/Prodi│
└────────┬───────────────┘
         │
         ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 6: TENTUKAN STATUS EWS (berdasarkan prioritas)         │
│                                                              │
│  PRIORITAS 1: KRITIS 🔴                                      │
│  ├── Sisa SKS > maks SKS yang bisa diambil (S1-S14)?       │
│  ├── Semester 13 + ada nilai E/D di MK ganjil?               │
│  └── Semester 14 + ada nilai E/D di MK genap?                │
│                                                              │
│  PRIORITAS 2: PERHATIAN 🟡                                    │
│  ├── Sisa SKS > maks SKS S1-S10 (5 tahun)?                   │
│  ├── Semester 9 + ada nilai E/D di MK ganjil?                │
│  └── Semester 10 + ada nilai E/D di MK genap?                │
│                                                              │
│  PRIORITAS 3: NORMAL 🟢                                      │
│  ├── Sisa SKS > maks SKS S1-S8 (4 tahun)?                     │
│  ├── Semester 7 + ada nilai E/D di MK ganjil?                │
│  └── Semester 8 + ada nilai E/D di MK genap?                 │
│                                                              │
│  PRIORITAS 4: TEPAT WAKTU 🔵                                  │
│  ├── Sisa SKS <= maks SKS S1-S8                              │
│  ├── Tidak ada nilai E                                      │
│  └── Nilai D maksimal 1 MK                                   │
└────────┬────────────────────────────────────────────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 7: Update         │
│ early_warning_system    │
│ table                  │
└────────┬───────────────┘
         │
         ▼
┌────────────────────────┐
│ STEP 8: Generate SPS   │
│ (Surat Peringatan Studi)│
│ - SPS1: IPS Smt1 < 2.0 │
│ - SPS2: IPS Smt2 < 2.0 │
│ - SPS3: IPS Smt3 < 2.0 │
└────────────────────────┘
         │
         ▼
┌────────────────────────┐
│    ✅ JOB COMPLETED     │
│  Return result JSON    │
└────────────────────────┘
```

**Penjelasan Detail Setiap Langkah:**

**Step 1 — Hitung IPS Semester Terbaru**
Sistem mengambil data nilai dari KHS semester terakhir mahasiswa dan menghitung IPS (Indeks Prestasi Semester) dengan rumus:

```
IPS = Σ(Nilai_Huruf × SKS) / Σ(SKS)
```

**Step 2 — Cek SKS Tempuh vs Lulus**
- `sks_tempuh` = total SKS yang pernah diambil (termasuk yang gagal)
- `sks_lulus` = total SKS yang sudah lulus
- `sisa_sks` = 144 - sks_lulus

**Step 3 — Cek Nilai D & E**
- Sistem mengambil nilai TERAKHIR per mata kuliah (dari KHS dengan id terbesar)
- Menghitung total SKS yang mendapat nilai D
- Menandai apakah ada nilai E

**Step 4 — Cek NFU (Nilai Fu)**
NFU = Nilai Furnishing. Cek apakah mahasiswa sudah menyelesaikan:
- MK Nasional
- MK Fakultas
- MK Prodi

**Step 6 — Tentukan Status EWS (Prioritas)**

```
P1: KRITIS (prioritas tertinggi, dicek duluan)
    → if (sisa_sks > sksBisaDiambilSD14) return 'kritis'
    → if (semester 13/14 + nilai E/D) return 'kritis'

P2: PERHATIAN
    → if (sisa_sks > sksBisaDiambilSD10) return 'perhatian'
    → if (semester 9/10 + nilai E/D) return 'perhatian'

P3: NORMAL
    → if (sisa_sks > sksBisaDiambilSD8) return 'normal'
    → if (semester 7/8 + nilai E/D) return 'normal'

P4: TEPAT WAKTU
    → if (sisa_sks <= sksBisaDiambilSD8 && nilaiE==0 && nilaiD<=1)
      return 'tepat_waktu'

Default: return 'normal'
```

**SKS Maksimal yang Bisa Diambil:**

| Semester | Maks SKS per Semester |
|----------|-----------------------|
| Semester 1-10 | 20 SKS |
| Semester 11-14 | 24 SKS |

### 5.4 Alur Export Laporan

```
┌──────────────────┐
│  User Request    │
│  Export Excel    │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐         ┌──────────────────┐
│  Check Auth &    │ ──────▶ │ Validate filters │
│  Role Permission │         │ (prodi, angkatan)│
└────────┬─────────┘         └────────┬─────────┘
         │                            │
         ▼                            ▼
┌─────────────────────────────────────────────────┐
│           GENERATE EXCEL (PhpSpreadsheet)        │
│  ┌──────────────────────────────────────────┐   │
│  │ Header: TOP FIK UDINUS                   │   │
│  │ Header: Logo/Nama Sistem                 │   │
│  │ Header: Tanggal Cetak                    │   │
│  │ Header: Filter yang digunakan            │   │
│  ├──────────────────────────────────────────┤   │
│  │ Data Columns (sesuai endpoint)           │   │
│  │ - No | NIM | Nama | Prodi | IPK | SKS   │   │
│  │ - Status EWS | Status Kelulusan | dll   │   │
│  ├──────────────────────────────────────────┤   │
│  │ Data Rows                                │   │
│  │ - Isi data sesuai filter                 │   │
│  │ - Styling: border, color coding          │   │
│  │ - Conditional formatting (warna status)  │   │
│  ├──────────────────────────────────────────┤   │
│  │ Footer: Tanggal generate, user who gen   │   │
│  └──────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────┘
                             │
                             ▼
                    ┌─────────────────┐
                    │ Download file   │
                    │ .xlsx           │
                    └─────────────────┘
```

**Fitur Export yang Tersedia:**

| Endpoint Export | Data | Filter |
|----------------|------|--------|
| `/mahasiswa/all/export` | Semua mahasiswa | Prodi, Angkatan, Status EWS |
| `/mahasiswa/detail-angkatan/{tahun}/export` | Mahasiswa per angkatan | Prodi |
| `/mahasiswa/mk-gagal/export` | Mahasiswa dengan MK gagal | Prodi |
| `/table-ringkasan-mahasiswa/export` | Ringkasan mahasiswa | Prodi |
| `/table-ringkasan-status/export` | Ringkasan status EWS | Prodi |
| `/tren-ips/all/export` | Tren IPS per angkatan | Prodi |
| `/tindak-lanjut/export` | Data tindak lanjut | Prodi, Status |

---

## 6. STATUS EWS (EARLY WARNING SYSTEM)

### 6.1 Definisi Status

Sistem EWS memiliki **4 level status** yang menunjukkan tingkat risiko akademik mahasiswa:

| Status | Warna | Kode Database | Penjelasan |
|--------|-------|---------------|------------|
| **Tepat Waktu** | 🔵 Biru | `tepat_waktu` | Mahasiswa on-track, berpotensi lulus dalam 4 tahun (8 semester) |
| **Normal** | 🟢 Hijau | `normal` | Mahasiswa dalam kondisi normal, berpotensi lulus 4-5 tahun |
| **Perhatian** | 🟡 Kuning | `perhatian` | Mahasiswa berisiko tidak lulus tepat waktu, target mundur ke 5 tahun |
| **Kritis** | 🔴 Merah | `kritis` | Mahasiswa berisiko tinggi DO atau tidak lulus dalam 7 tahun (14 semester) |

**Warna sebagai Visual Cue:**
- **Biru/Hijau** — Mahasiwa baik-baik saja
- **Kuning** — Perlu perhatian, tapi belum kritis
- **Merah** — Perluintervensi segera

### 6.2 Kriteria Setiap Status

#### 🔵 Tepat Waktu

Mahasiswa bisa prediksi **lulus dalam 4 tahun (8 semester)** jika:

| Kriteria | Nilai |
|----------|-------|
| SKS sisa | <= Maks SKS yang bisa diambil (S1-S8) |
| Nilai E | Tidak ada sama sekali |
| Nilai D | Maksimal 1 mata kuliah |
| MK Nasional/Fakultas/Prodi | Semua sudah selesai |

**Contoh kasus:**
- Semester 7 aktif, SKS lulus 110 (sisa 34), bisa ambil max 40 SKS di S7-S8 → ✅ Tepat Waktu
- Tidak ada nilai E, nilai D hanya 1 → ✅

#### 🟢 Normal

Mahasiswa **tidak bisa lulus 4 tahun** tapi **masih bisa lulus 4-5 tahun** jika:

| Kriteria | Nilai |
|----------|-------|
| SKS sisa | <= Maks SKS yang bisa diambil (S1-S8) tapi ada nilai E/D di semester 7/8 |
| Atau | Sisa SKS > maks S1-S8 tapi <= maks S1-S10 |

**Contoh kasus:**
- Semester 7 aktif, SKS lulus 90 (sisa 54), max S7-S8 = 40, tapi max S7-S10 = 80 → ✅ Normal
- Ada 2 nilai D, tapi tidak ada nilai E → ✅

#### 🟡 Perhatian

Mahasiswa **berisiko lulus dalam 5-7 tahun** jika:

| Kriteria | Nilai |
|----------|-------|
| SKS sisa | > Maks SKS S1-S10 (tidak bisa lulus 5 tahun) |
| Atau | Semester 9/10 masih ada nilai E/D di MK ganjil/genap |

**Contoh kasus:**
- Semester 9 aktif, SKS lulus 80 (sisa 64), max S9-S10 = 40 (tidak cukup) → ✅ Perhatian
- Semester 9, ada nilai E di MK semester 3 → ✅ Perhatian

#### 🔴 Kritis

Mahasiswa **berisiko tinggi DO atau tidak lulus** jika:

| Kriteria | Nilai |
|----------|-------|
| SKS sisa | > Maks SKS S1-S14 (tidak bisa lulus 7 tahun pun) |
| Atau | Semester 13/14 masih ada nilai E/D di MK yang belum diulang |
| Atau | Sisa SKS sangat banyak sementara semester sudah tinggi |

**Contoh kasus:**
- Semester 13 aktif, SKS lulus 60 (sisa 84), max S13-S14 = 48 (tidak cukup) → ✅ KRITIS
- Semester 13, ada nilai E di MK semester 1 → ✅ KRITIS (tidak sempat ulang)

### 6.3 Alur Status EWS (Diagram Keputusan)

```
                    ┌─────────────────┐
                    │   MULAI HITUNG  │
                    │   STATUS EWS    │
                    └────────┬────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │ Sudah Lulus (SKS >= 144)?    │
              └──────────────┬──────────────┘
                     YES     │     NO
                      │              │
                      ▼              ▼
            ┌─────────────┐   ┌────────────────────────┐
            │ by semester │   │ Sisa SKS > Maks S1-S14?│
            │ atur status │   └────────────┬───────────┘
            └─────────────┘        YES   │    NO
                                          ▼
                                  ┌────────────┐
                                  │   KRITIS   │
                                  └────────────┘
                                          │
                                          ▼
                        ┌──────────────────────────────┐
                        │ Semester 13/14 + nilai E/D? │
                        └──────────────┬─────────────┘
                               YES     │    NO
                                │              │
                                ▼              ▼
                         ┌──────────┐  ┌────────────────────────┐
                         │  KRITIS  │  │Sisa SKS > Maks S1-S10?│
                         └──────────┘  └────────────┬─────────┘
                                         YES   │    NO
                                          │        │
                                          ▼        ▼
                                   ┌───────────┐┌─────────────────┐
                                   │ PERHATIAN ││Sisa SKS > Maks  │
                                   └───────────┘│   S1-S8?       │
                                                 └───────┬─────────┘
                                               YES   │     NO
                                                │        │
                                                ▼        ▼
                                         ┌──────────┐┌─────────────────┐
                                         │  NORMAL  ││Semester 7/8 +   │
                                         └──────────┘│nilai E/D?      │
                                                     └───────┬─────────┘
                                                   YES   │     NO
                                                    │        │
                                                    ▼        ▼
                                             ┌──────────┐┌────────────────────┐
                                             │  NORMAL  ││SKS OK + E=0 + D<=1│
                                             └──────────┘│  → TEPAT WAKTU     │
                                                          └────────┬──────────┘
                                                                    │
                                                              ┌──────┴──────┐
                                                              │             │
                                                              ▼             ▼
                                                         ┌──────────┐┌──────────┐
                                                         │   TEPAT  ││  NORMAL  │
                                                         │   WAKTU  ││ (default)│
                                                         └──────────┘└──────────┘
```

---

## 7. WORKFLOW TINDAK LANJUT

### 7.1 Alur Proses Tindak Lanjut

```
Mahasiswa Status = KRITIS
        │
        ▼
┌──────────────────────────────┐
│  Sistem Generate Peringatan │
│  (SPS1/SPS2/SPS3)           │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  Kaprodi Melihat di         │
│  Dashboard Tindak Lanjut    │
│  (Card "Perlu Intervensi") │
└──────────────┬───────────────┘
               │
               ▼
┌──────────────────────────────┐
│  Mahasiswa Dipanggil untuk  │
│  Bimbingan oleh Dosen Wali │
└──────────────┬───────────────┘
               │
               ▼
┌─────────────────────────────────────────────────┐
│  PILIH JENIS INTERVENSI:                        │
│  ┌─────────────────┐  ┌─────────────────────┐   │
│  │ 1. REKOMITMEN   │  │ 2. PINDAH PRODI     │   │
│  │    (Surat       │  │    (Jika masalah   │   │
│  │    Pernyataan)  │  │    fundamental)    │   │
│  └────────┬────────┘  └─────────┬─────────┘   │
│           │                       │              │
│           ▼                       ▼              │
│  ┌──────────────────┐  ┌──────────────────────┐  │
│  │ Generate Surat   │  │ Ajukan Permohonan   │  │
│  │ Rekomitmen       │  │ Pindah Prodi        │  │
│  │ (Template)       │  │                     │  │
│  └────────┬─────────┘  └──────────┬───────────┘  │
│           │                         │              │
│           ▼                         ▼              │
│  ┌──────────────────┐  ┌──────────────────────┐  │
│  │ Mahasiswa        │  │ Proses Administrasi  │  │
│  │ Tanda Tangan     │  │ Pindah Prodi         │  │
│  │ Surat Rekomitmen │  │                      │  │
│  └────────┬─────────┘  └──────────┬───────────┘  │
│           │                         │              │
│           ▼                         ▼              │
│  ┌──────────────────────────────────────────────┐│
│  │  STATUS VERIFIKASI:                           ││
│  │  "Belum Diverifikasi" → "Telah Diverifikasi" ││
│  └───────────────────────────────────┬──────────┘│
│                                      │           │
│                         ┌────────────┴─────────┐ │
│                         ▼                      ▼ │
│                  ┌────────────┐         ┌──────────┐
│                  │  SELESAI   │         │ DALAM    │
│                  │  ✅ Proses │         │  PROSES  │
│                  │  Selesai   │         │  ⏳      │
│                  └────────────┘         └──────────┘
└─────────────────────────────────────────────────┘
```

### 7.2 Jenis Intervensi

#### 1. Rekomitmen (Surat Pernyataan)

**Kapan digunakan:**
- Mahasiswa mendapat SPS3 (Surat Peringatan Studi Tahap 3)
- IPS semester 3 < 2.0
- Status EWS menjadi Kritis

**Proses:**
1. Sistem generate template surat rekomitmen
2. Mahasiswa mengunduh template dari sistem
3. Mahasiswa填写 dan menandatangani surat
4. Surat diunggah ke sistem
5. Kaprodi memverifikasi surat
6. Status berubah menjadi "Telah Diverifikasi"

**Data yang disimpan:**
- `kategori`: `rekomitmen`
- `link`: URL file surat yang diunggah
- `tanggal_pengajuan`: Tanggal pengajuan
- `status`: `belum_diverifikasi` / `telah_diverifikasi`

#### 2. Pindah Prodi

**Kapan digunakan:**
- Masalah fundamental yang tidak bisa diselesaikan di prodi saat ini
- Setelah berbagai intervensi tidak membuahkan hasil
- Berdasarkan rekomendasi dosen wali

**Proses:**
1. Mahasiswa mengajukan permohonan pindah prodi
2. Kaprodi asal dan Kaprodi tujuan memberikan persetujuan
3. Proses administrasi handled oleh fakultas
4. Status diverifikasi setelah disetujui

### 7.3 SPS (Surat Peringatan Studi)

| Surat | Kondisi | Keterangan |
|-------|---------|------------|
| **SPS1** | IPS Semester 1 < 2.0 | Peringatan pertama — belum ada tindakan wajib |
| **SPS2** | IPS Semester 2 < 2.0 | Peringatan kedua — perlu bimbingan |
| **SPS3** | IPS Semester 3 < 2.0 | **WAJIB REKOMITMEN** — mahasiswa harus mengisi surat rekomitmen |

**Alur SPS3:**
```
IPS Semester 3 < 2.0
      │
      ▼
SPS3 = 'yes'
      │
      ▼
Mahasiswa WAJIBAjukan Rekomitmen
      │
      ▼
Dosen Wali memberikan Bimbingan Intensif
      │
      ▼
Mahasiswa isi & tanda tangan Surat Rekomitmen
      │
      ▼
Kaprodi verifikasi
      │
      ▼
Status = "Telah Diverifikasi"
```

---

## 8. ATURAN BUSINESS LOGIC

### 8.1 Kelayakan Lulus (Eligible)

Mahasiswa dinyatakan **ELIGIBLE** (memenuhi syarat kelulusan) jika memenuhi **SEMUA** syarat berikut:

| No | Syarat | Keterangan |
|----|--------|-----------|
| 1 | IPK >= 2.5 | Indeks Prestasi Kumulatif minimal 2.5 |
| 2 | SKS Lulus >= 144 | Total SKS yang sudah lulus minimal 144 |
| 3 | MK Nasional Selesai | Semua mata kuliah wajib nasional sudah lulus |
| 4 | MK Fakultas Selesai | Semua mata kuliah wajib fakultas sudah lulus |
| 5 | MK Prodi Selesai | Semua mata kuliah wajib prodi sudah lulus |
| 6 | Tidak ada nilai E | Mahasiswa TIDAK memiliki satupun nilai E |
| 7 | Nilai D tidak melebihi batas | Maksimal 2 MK dengan nilai D, dan total SKS nilai D <= 7.2 |

> **Catatan Penting:** Jika **salah satu saja** syarat tidak terpenuhi, mahasiswa berstatus **NON-ELIGIBLE**.

**Detail Aturan Nilai D:**
- Maksimal **2 mata kuliah** yang boleh mendapat nilai D
- Total **SKS** dari mata kuliah bernilai D **tidak boleh melebihi 7.2 SKS** (5% dari 144 SKS standar kelulusan)
- **Contoh valid:** 3 SKS (MK1) + 3 SKS (MK2) = 6 SKS ✅
- **Contoh invalid:** 2 SKS (MK1) + 2 SKS (MK2) + 2 SKS (MK3) = 6 SKS ❌ (3 mata kuliah)

### 8.2 Ekspektasi SKS per Semester

Sistem EWS menghitung ekspektasi SKS mahasiswa berdasarkan semester aktif dan target kelulusan.

| Semester | SKS Tempuh Minimum | SKS Tempuh Maksimum |
|----------|-------------------|---------------------|
| 1 | 18 | 20 |
| 2 | 36 | 40 |
| 3 | 54 | 60 |
| 4 | 72 | 80 |
| 5 | 90 | 100 |
| 6 | 108 | 120 |
| 7 | 126 | 140 |
| 8 | 144 | 144 |

**Aturan SKS Maksimal:**

| Range Semester | SKS Maksimal per Semester |
|----------------|--------------------------|
| Semester 1-10 | 20 SKS per semester |
| Semester 11-14 | 24 SKS per semester |

**Contoh Perhitungan:**
- Mahasiswa semester 6 ingin lulus di semester 8
- Sisa SKS: 144 - sks_lulus
- SKS yang bisa diambil: S6 (20) + S7 (20) + S8 (20) = **60 SKS**
- Jika sisa SKS > 60 → mahasiswa tidak bisa lulus tepat waktu

### 8.3 NFU (Nilai Furnishing)

NFU adalah singkatan dari **Nilai Furnishing** — paket mata kuliah wajib yang harus diselesaikan mahasiswa.

**NFU Terdiri dari:**

| Kategori | Contoh MK | Syarat |
|----------|-----------|--------|
| **MK Nasional** | Bahasa Indonesia, Pancasila, Kewarganegaraan | Wajib untuk semua mahasiswa Indonesia |
| **MK Fakultas** | Dasar Komputasi, Matematika Dasar, Statistik | Wajib untuk semua prodi FIK |
| **MK Prodi** | Algorithm & Programming, Database, Jaringan Komputer | Wajib untuk prodi tertentu |

**Syarat NFU untuk Status "Tepat Waktu":**
- MK Nasional = **Selesai** ✅
- MK Fakultas = **Selesai** ✅
- MK Prodi = **Selesai** ✅

> **Catatan:** NFU biasanya wajib untuk semester 7-8 (tahun keempat). Jika belum selesai, mahasiswa tidak bisa mendapatkan status "Tepat Waktu".

---

## 9. USER INTERFACE OVERVIEW

### 9.1 Dekan Dashboard

Dekan Dashboard dirancang untuk memberikan **overview seluruh fakultas** dalam sekali pandang.

**Komponen Utama:**

**Card Statistik Utama (Top Row):**
- Total mahasiswa seluruh FIK
- Rata-rata IPK fakultas
- Persentase mahasiswa Tepat Waktu
- Persentase Eligible Kelulusan

**Distribusi Status EWS:**
- Donat/Chart yang menunjukkan proporsi:
  - 🔵 Tepat Waktu: X%
  - 🟢 Normal: X%
  - 🟡 Perhatian: X%
  - 🔴 Kritis: X%

**Statistik per Prodi:**
- Card per prodi (A11, A12, A14, A15)
- Setiap card menampilkan:
  - Total mahasiswa prodi
  - Rata-rata IPK prodi
  - Distribusi status EWS prodi
  - Jumlah mahasiswa Eligible/Non-Eligible

**Fitur Export:**
- Tombol "Export Excel" di setiap tabel/section
- File Excel berisi data sesuai filter

### 9.2 Kaprodi Dashboard

Kaprodi Dashboard mirip dengan Dekan Dashboard tetapi **hanya fokus pada prodi yang dikelola** oleh Kaprodi yang login.

**Komponen Utama:**

**Card Statistik Prodi:**
- Total mahasiswa prodi
- Rata-rata IPK prodi
- Persentase eligible kelulusan
- Card "Perlu Intervensi" (mahasiswa kritis)

**Tabel Ringkasan Mahasiswa:**
- Daftar mahasiswa prodi
- Kolom: NIM, Nama, Semester, IPK, SKS, Status EWS, Eligible
- Bisa difilter dan di-sort
- Click row → ke detail mahasiswa

**Distribusi Status EWS Prodi:**
- Visual distribusi status EWS
- Perbandingan antar angkatan

**Tren IPS:**
- Line chart tren IPS per angkatan
- Sumbu X: Angkatan
- Sumbu Y: IPS Rata-rata

### 9.3 Mahasiswa Dashboard

Mahasiswa Dashboard dirancang agar **mahasiswa bisa memantau kondisi akademik mereka sendiri** secara mandiri dan intuitif.

**Komponen Utama:**

**Card Status Akademik Pribadi:**
- Status EWS pribadi (dengan warna/warna yang sesuai)
- IPK saat ini
- SKS tempuh / SKS lulus
- Semester aktif

**Card Status Kelulusan:**
- Eligible / Non-Eligible
- Jika Non-Eligible: alasan mengapa belum eligible

**Ringkasan MK:**
- Progress MK Nasional: X/Y selesai
- Progress MK Fakultas: X/Y selesai
- Progress MK Prodi: X/Y selesai

**Tab KHS/KRS:**
- Riwayat nilai per semester
- Bisa expend per semester untuk lihat detail
- Tombol Export Transkrip

**Tab Peringatan:**
- Jika ada SPS1/SPS2/SPS3: ditampilkan di sini
- Penjelasan singkat mengapa peringatan diberikan

**Tab Tindak Lanjut:**
- Jika mahasiswa sedang dalam proses intervensi: ditampilkan di sini
- Link download template surat
- Status verifikasi

---

## 10. KEAMANAN DAN AKSES

### 10.1 Authentication

Sistem EWS menggunakan **Laravel Sanctum** (mekanisme token-based authentication) untuk mengelola akses pengguna.

**Alur Authentication:**

```
┌──────────────────────────────────────────────────────────────┐
│                    ALUR AUTHENTICATION                       │
└──────────────────────────────────────────────────────────────┘

1. LOGIN REQUEST
   POST /api/login
   Body: { email: "xxx", password: "xxx" }

2. VALIDASI
   ┌─────────────────┐
   │ Cek email ada   │──❌──▶ Return 401 "Email tidak ditemukan"
   │ di database     │
   └────────┬────────┘
            │ ✅
            ▼
   ┌─────────────────┐
   │ Verifikasi      │──❌──▶ Return 401 "Password salah"
   │ Password        │
   └────────┬────────┘
            │ ✅
            ▼
3. GENERATE TOKEN
   ┌─────────────────┐
   │ Generate        │
   │ Sanctum Token   │
   │ (expires: 7    │
   │  days default)  │
   └────────┬────────┘
            │
            ▼
   ┌─────────────────┐
   │ Simpan token    │
   │ ke tabel users  │
   │ (access_token)  │
   └────────┬────────┘
            │
            ▼
4. RESPONSE
   {
     "access_token": "1|abc123...",
     "token_type": "Bearer",
     "role": "dekan|kaprodi|mahasiswa",
     "user": { ...data user... }
   }
```

**Login Per Role:**
Sistem menyediakan endpoint login terpisah untuk setiap role:

| Endpoint | Role | Keterangan |
|----------|------|------------|
| `POST /api/login` | General |通用 login |
| `POST /api/login-kaprodi` | Kaprodi | Login dengan NPP khusus Kaprodi |
| `POST /api/login-dekan` | Dekan | Login dengan email/npp Dekan |
| `POST /api/login-mahasiswa` | Mahasiswa | Login dengan NIM mahasiswa |

### 10.2 Authorization (Otorisasi)

Sistem menggunakan **middleware** untuk membatasi akses berdasarkan role dan scope.

**Mekanisme Otorisasi:**

```
┌──────────────────────────────────────────────────────────────┐
│                    MIDDLEWARE STACK                          │
└──────────────────────────────────────────────────────────────┘

Request datang
      │
      ▼
┌─────────────────┐
│ Auth Middleware │──❌──▶ 401 Unauthorized
│ (Token valid?) │
└────────┬────────┘
         │ ✅
         ▼
┌─────────────────┐
│ Role Middleware │──❌──▶ 403 Forbidden (role tidak sesuai)
│ (Role sesuai?)  │
└────────┬────────┘
         │ ✅
         ▼
┌─────────────────┐
│ Scope Middleware│──❌──▶ 403 Forbidden (scope tidak sesuai)
│ (Data scope     │
│  sesuai?)       │
└────────┬────────┘
         │ ✅
         ▼
   Controller
   dipanggil
```

**Role-Based Access:**

| Role | Scope | Cakupan Akses |
|------|-------|---------------|
| `dekan` | Semua prodi | Semua data 4 prodi FIK |
| `kaprodi` | Prodi sendiri | Hanya data prodi terkait |
| `mahasiswa` | Data sendiri | Hanya data pribadi |

**Scope Middleware:**
Kaprodi hanya boleh mengakses data mahasiswa yang berasal dari prodi mereka. Scope middleware secara otomatis menambahkan filter `WHERE prodi_id = ?` pada semua query.

### 10.3 Middleware Detail

**Auth Middleware:**
- Memvalidasi token dari header `Authorization: Bearer {token}`
- Mengekstrak user_id dan role dari token
- Memasukkan data user ke request

**Role Middleware:**
- Mengecek apakah role user sesuai dengan endpoint yang diakses
- Route Kaprodi: hanya role `kaprodi` dan `dekan` yang bisa akses
- Route Dekan: hanya role `dekan` yang bisa akses
- Route Mahasiswa: hanya role `mahasiswa` yang bisa akses

**Scope Middleware (untuk Kaprodi):**
- Membatasi query database hanya pada prodi_id Kaprodi yang login
- Mencegah Kaprodi mengakses data prodi lain

---

## 11. ENDPOINT REFERENCE (NON-TECHNICAL)

### Authentication

| Endpoint | Metode | Fungsi | Akses |
|----------|--------|--------|-------|
| `/api/login` | POST | Login umum | Public |
| `/api/login-kaprodi` | POST | Login Kaprodi | Public |
| `/api/login-dekan` | POST | Login Dekan | Public |
| `/api/login-mahasiswa` | POST | Login Mahasiswa | Public |
| `/api/profile` | GET | Lihat profil user login | Semua role (sudah login) |

### Dekan (Fakultas)

| Endpoint | Metode | Fungsi |
|----------|--------|--------|
| `ews/dekan/dashboard` | GET | Overview seluruh fakultas |
| `ews/dekan/table-ringkasan-mahasiswa` | GET | Tabel ringkasan semua mahasiswa |
| `ews/dekan/table-ringkasan-mahasiswa/export` | GET | Download Excel ringkasan |
| `ews/dekan/status-mahasiswa` | GET | Status semua mahasiswa |
| `ews/dekan/mahasiswa/detail/{id}` | GET | Detail satu mahasiswa |
| `ews/dekan/mahasiswa/detail-angkatan/{tahun}` | GET | Mahasiswa per angkatan |
| `ews/dekan/mahasiswa/detail-angkatan/{tahun}/export` | GET | Download Excel angkatan |
| `ews/dekan/mahasiswa/all` | GET | Daftar semua mahasiswa |
| `ews/dekan/mahasiswa/all/export` | GET | Download Excel semua mahasiswa |
| `ews/dekan/distribusi-status-ews` | GET | Distribusi status EWS per prodi |
| `ews/dekan/table-ringkasan-status` | GET | Tabel ringkasan status EWS |
| `ews/dekan/table-ringkasan-status/export` | GET | Export ringkasan status |
| `ews/dekan/statistik-kelulusan` | GET | Card statistik kelulusan |
| `ews/dekan/table-statistik-kelulusan` | GET | Tabel detail kelulusan |
| `ews/dekan/tren-ips/all` | GET | Tren IPS per angkatan |
| `ews/dekan/tren-ips/all/export` | GET | Export tren IPS |
| `ews/dekan/card-capaian` | GET | Card capaian mahasiswa |
| `ews/dekan/top-mk-gagal` | GET | Top 10 MK paling gagal |
| `ews/dekan/mahasiswa/mk-gagal` | GET | Mahasiswa dengan MK gagal |
| `ews/dekan/mahasiswa/mk-gagal/export` | GET | Export MK gagal |
| `ews/dekan/recalculate-all-status` | POST | Recalculate semua status EWS |
| `ews/dekan/mahasiswa/{id}/recalculate-status` | POST | Recalculate satu mahasiswa |
| `ews/dekan/tindak-lanjut/cards` | GET | Card statistik tindak lanjut |
| `ews/dekan/tindak-lanjut/` | GET | Daftar tindak lanjut |
| `ews/dekan/tindak-lanjut/export` | GET | Export tindak lanjut |
| `ews/dekan/tindak-lanjut/{id}` | PATCH | Update status verifikasi |

### Kaprodi (Program Studi)

| Endpoint | Metode | Fungsi |
|----------|--------|--------|
| `ews/kaprodi/dashboard` | GET | Overview prodi sendiri |
| `ews/kaprodi/table-ringkasan-mahasiswa` | GET | Tabel ringkasan mahasiswa prodi |
| `ews/kaprodi/table-ringkasan-mahasiswa/export` | GET | Download Excel ringkasan prodi |
| `ews/kaprodi/status-mahasiswa` | GET | Status mahasiswa prodi |
| `ews/kaprodi/mahasiswa/detail/{id}` | GET | Detail satu mahasiswa prodi |
| `ews/kaprodi/mahasiswa/detail-angkatan/{tahun}` | GET | Mahasiswa angkatan tertentu |
| `ews/kaprodi/mahasiswa/detail-angkatan/{tahun}/export` | GET | Export Excel angkatan |
| `ews/kaprodi/mahasiswa/all` | GET | Daftar semua mahasiswa prodi |
| `ews/kaprodi/mahasiswa/all/export` | GET | Download Excel prodi |
| `ews/kaprodi/distribusi-status-ews` | GET | Distribusi status EWS prodi |
| `ews/kaprodi/table-ringkasan-status` | GET | Tabel ringkasan status |
| `ews/kaprodi/table-ringkasan-status/export` | GET | Export ringkasan status |
| `ews/kaprodi/statistik-kelulusan` | GET | Card statistik kelulusan prodi |
| `ews/kaprodi/table-statistik-kelulusan` | GET | Tabel kelulusan prodi |
| `ews/kaprodi/tren-ips/all` | GET | Tren IPS prodi |
| `ews/kaprodi/tren-ips/all/export` | GET | Export tren IPS prodi |
| `ews/kaprodi/card-capaian` | GET | Card capaian prodi |
| `ews/kaprodi/top-mk-gagal` | GET | Top MK gagal prodi |
| `ews/kaprodi/mahasiswa/mk-gagal` | GET | Mahasiswa MK gagal prodi |
| `ews/kaprodi/mahasiswa/mk-gagal/export` | GET | Export MK gagal prodi |
| `ews/kaprodi/recalculate-all-status` | POST | Recalculate semua status |
| `ews/kaprodi/mahasiswa/{id}/recalculate-status` | POST | Recalculate satu mahasiswa |
| `ews/kaprodi/tindak-lanjut/cards` | GET | Card statistik tindak lanjut |
| `ews/kaprodi/tindak-lanjut/` | GET | Daftar tindak lanjut prodi |
| `ews/kaprodi/tindak-lanjut/export` | GET | Export tindak lanjut |
| `ews/kaprodi/tindak-lanjut/bulk-update` | PATCH | Bulk update status |
| `ews/kaprodi/tindak-lanjut/{id}` | PATCH | Update status satu |

### Mahasiswa

| Endpoint | Metode | Fungsi |
|----------|--------|--------|
| `ews/mahasiswa/dashboard` | GET | Info akademik pribadi |
| `ews/mahasiswa/card-status-akademik` | GET | Card status akademik |
| `ews/mahasiswa/khs-krs` | GET | Riwayat KHS/KRS lengkap |
| `ews/mahasiswa/khs-krs/export` | GET | Export transkrip ke Excel |
| `ews/mahasiswa/khs-krs/{id}` | GET | Detail KHS per semester |
| `ews/mahasiswa/peringatan` | GET | Status peringatan SPS |
| `ews/mahasiswa/tindak-lanjut/cards` | GET | Card statistik tindak lanjut |
| `ews/mahasiswa/tindak-lanjut/` | GET | Status tindak lanjut pribadi |
| `ews/mahasiswa/tindak-lanjut/` | POST | Ajukan tindak lanjut |
| `ews/mahasiswa/tindak-lanjut/template/{kategori}` | GET | Download template surat |

---

## 12. KESIMPULAN

### 12.1 Ringkasan Sistem

Sistem Early Warning System (EWS) FIK UDINUS adalah platform backend berbasis Laravel yang dirancang untuk:

1. **Mendeteksi dini** mahasiswa yang berisiko tidak lulus tepat waktu berdasarkan indikator akademik yang akurat.
2. **Menyediakan visibilitas** bagi Dekan, Kaprodi, dan Dosen Wali untuk memantau kondisi akademik mahasiswa secara terpusat.
3. **Mendukung prosesintervensi** melalui workflow tindak lanjut yang terstruktur (rekomitmen dan pindah prodi).
4. **Memberdayakan mahasiswa** untuk memantau status akademik mereka sendiri secara mandiri.
5. **Menghasilkan laporan** dalam format Excel untuk keperluan administrasi dan pengambilan keputusan.

### 12.2 Keberhasilan

Beberapa aspek keberhasilan yang telah dicapai:

- ✅ **100% endpoint berfungsi** — seluruh endpoint API telah diuji dan berjalan dengan baik sesuai spesifikasi
- ✅ **Role-based access berjalan dengan benar** — setiap role hanya bisa mengakses data sesuai scope-nya
- ✅ **Algoritma EWS komprehensif** — mempertimbangkan SKS, IPS, IPK, nilai D/E, MK wajib, dan batas waktu kelulusan
- ✅ **Recalculation otomatis dan manual** — sistem bisa auto-trigger saat data berubah atau di-trigger manual oleh pengguna
- ✅ **Export Excel berfungsi** — seluruh fitur export menghasilkan file Excel dengan styling profesional
- ✅ **Workflow tindak lanjut terintegerasi** — proses intervensi dari deteksi hingga verifikasi berjalan sistematis
- ✅ **Mendukung 4 prodi** — sistem dirancang untuk mengakomodasi seluruh program studi di FIK

### 12.3 Saran Pengembangan

Untuk pengembangan ke depan, beberapa hal yang dapat dipertimbangkan:

| Prioritas | Pengembangan | Keterangan |
|-----------|-------------|------------|
| **Tinggi** | **Frontend Mobile App** | Pengembangan aplikasi mobile (iOS/Android) agar mahasiswa dan dosen dapat mengakses EWS dari mana saja |
| **Tinggi** | **Notifikasi Telegram** | Integrasi notifikasi otomatis via Telegram bot untuk memberitahu Kaprodi/Dosen Wali saat mahasiswa menjadi kritis |
| **Sedang** | **Analitik Lanjutan** | Dashboard analitik dengan predictive analytics — memprediksi mahasiswa mana yang akan menjadi kritis sebelum semester berjalan |
| **Sedang** | **Export PDF** | Selain Excel, tambahkan kemampuan export ke PDF untuk keperluan arsip |
| **Rendah** | **Integrasi SIAPUDINUS** | Integrasi langsung dengan sistem informasi akademik yang sudah ada di UDINUS untuk sinkronisasi data otomatis |
| **Rendah** | **Dashboard Dosen Wali** | Panel khusus untuk dosen wali melihat dan mengelola mahasiswa bimbingannya |

---

## LAMPIRAN

### Lampiran A: Daftar Program Studi FIK UDINUS

| No | Kode Prodi | Nama Program Studi |
|----|-----------|-------------------|
| 1 | A11 | Teknik Informatika |
| 2 | A12 | Sistem Informasi |
| 3 | A14 | Desain Komunikasi Visual |
| 4 | A15 | Animasi |

### Lampiran B: Tipe Mata Kuliah

| Tipe | Keterangan |
|------|-----------|
| `nasional` | Mata kuliah wajib nasional |
| `fakultas` | Mata kuliah wajib fakultas |
| `prodi` | Mata kuliah wajib program studi |
| `peminatan` | Mata kuliah pilihan/peminatan |

### Lampiran C: Referensi Status EWS

| Status | Warna | Keterangan |
|--------|-------|------------|
| `tepat_waktu` | 🔵 Biru | On track lulus 4 tahun |
| `normal` | 🟢 Hijau | Normal, bisa lulus 4-5 tahun |
| `perhatian` | 🟡 Kuning | Berisiko, target 5-7 tahun |
| `kritis` | 🔴 Merah | Berisiko tinggi DO |

### Lampiran D: Referensi Status Kelulusan

| Status | Keterangan |
|--------|------------|
| `eligible` | Memenuhi seluruh syarat kelulusan |
| `non-eligible` | Belum memenuhi syarat kelulusan |

### Lampiran E: Surat Peringatan Studi (SPS)

| Surat | Kondisi Pemicu |
|-------|---------------|
| SPS1 | IPS Semester 1 < 2.0 |
| SPS2 | IPS Semester 2 < 2.0 |
| SPS3 | IPS Semester 3 < 2.0 (WAJIB REKOMITMEN) |

---

*Dokumen ini disusun sebagai laporan pemahaman sistem EWS FIK UDINUS — bukan dokumentasi teknis. Untuk referensi teknis, lihat dokumentasi API dan source code di repository.*

**Project:** Top FIK x EWS  
**Backend:** `be_ews_r`  
**Versi API:** 1.0  
**Last Updated:** April 2026
