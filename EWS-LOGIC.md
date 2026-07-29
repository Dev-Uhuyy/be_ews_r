# Early Warning System (EWS) - Business Logic Documentation

## Overview
Early Warning System (EWS) adalah sistem untuk mendeteksi mahasiswa yang berisiko tidak lulus tepat waktu atau mengalami masalah akademik. Sistem memberikan status peringatan berdasarkan berbagai indikator akademik.

---

## Status EWS

Terdapat **4 tingkat status** dalam EWS:

| Status | Warna | Keterangan (contoh S1, kurikulum 8 semester) |
|--------|-------|------------|
| **Tepat Waktu** | 🔵 Biru | Mahasiswa on track untuk lulus dalam 1 masa kurikulum (S1: 4 tahun/8 semester) |
| **Normal** | 🟢 Hijau | Mahasiswa dalam kondisi normal, berpotensi lulus dalam 1 masa kurikulum |
| **Perhatian** | 🟡 Kuning | Mahasiswa berisiko tidak lulus tepat waktu, target masa kurikulum + 2 semester (S1: 10 semester) |
| **Kritis** | 🔴 Merah | Mahasiswa berisiko tinggi DO atau tidak lulus dalam 2× masa kurikulum (S1: 7 tahun/16 semester) |

---

## Multi-Jenjang (D2/D3/D4/S1/Profesi/S2/S3)

Semua threshold semester & SKS di dokumen ini diturunkan dari **masa kurikulum
(`K`, dalam semester)** per jenjang mahasiswa, bukan angka tetap. Sumber
jenjang: `prodis.gelar`, dipetakan ke `K` dan SKS target lewat `config/ews.php`:

| Jenjang | `K` (kurikulum) | SKS target |
|---|---|---|
| D2 | 4 | 72 |
| D3 | 6 | 108 |
| D4 | 8 | 144 |
| S1 | 8 | 144 |
| Profesi | 4 | 24 |
| S2 | 4 | 36 |
| S3 | 8 | 42 |

Rumus tier status (berlaku untuk semua jenjang):

| Tier | Batas semester | S1 (`K=8`) | D3 (`K=6`) |
|---|---|---|---|
| Tepat Waktu | `semester ≤ K` | ≤ 8 | ≤ 6 |
| Normal | `semester ≤ K+2` | ≤ 10 | ≤ 8 |
| Perhatian | `semester ≤ 2K` | ≤ 16 | ≤ 12 |
| Kritis | `semester > 2K` | > 16 | > 12 |

Kalau `gelar` prodi null atau tidak dikenal, sistem **fallback ke S1**
(`K=8`, 144 SKS) — semua contoh & narasi di bawah dokumen ini memakai S1
sebagai ilustrasi (ditandai "contoh S1"), tapi rumusnya generik untuk semua
jenjang di atas.

---

## Status Kelulusan

Terdapat **2 status kelulusan**:

| Status | Keterangan |
|--------|------------|
| **Eligible** | Memenuhi semua syarat untuk lulus |
| **Non-eligible** | Belum memenuhi syarat kelulusan |

### Kriteria Eligible

Mahasiswa dinyatakan **eligible** jika memenuhi **SEMUA** syarat berikut:

1. ✅ **IPK > 2.0**
2. ✅ **SKS Lulus >= SKS target jenjang** (S1/D4: 144, D3: 108, D2: 72, S2: 36, S3: 42, Profesi: 24 — lihat [Multi-Jenjang](#multi-jenjang-d2d3d4s1profesis2s3))
3. ✅ **MK Nasional Selesai** (mk_nasional = 'yes')
4. ✅ **MK Fakultas Selesai** (mk_fakultas = 'yes')
5. ✅ **MK Prodi Selesai** (mk_prodi = 'yes')
6. ✅ **TIDAK ada nilai E** (nilai_e = 'no')
7. ✅ **Nilai D tidak melebihi batas** (nilai_d_melebihi_batas = 'no')
   - Maksimal 2 mata kuliah dengan nilai D
   - Total SKS nilai D tidak melebihi **5% dari SKS target jenjang** (S1: 7.2 SKS dari 144, D3: 5.4 SKS dari 108)
   - Contoh (S1): 3 SKS + 3 SKS = 6 SKS ✅ | 2 SKS + 2 SKS + 2 SKS = 6 SKS ❌

Jika salah satu syarat tidak terpenuhi, status menjadi **Non-eligible**.

---

## Algoritma Perhitungan Status EWS

### Input Data

Untuk menghitung status EWS, sistem membutuhkan data:

- `sks_lulus`: SKS yang sudah lulus/selesai
- `semester_aktif`: Semester mahasiswa saat ini
- `nilai_akhir_huruf`: Nilai huruf mata kuliah (A, B+, B, C+, C, D, E)
- `mata_kuliah.semester`: Semester mata kuliah ditawarkan (1-8, ganjil/genap)

### Variabel Hitung

```
sisa_sks = max(0, sks_target - sks_lulus)   // sks_target dari config('ews.jenjang.{gelar}.sks')
jumlah_nilai_e = COUNT(nilai E dari KHS)
jumlah_nilai_d = COUNT(nilai D dari KHS)
```

### SKS Maksimal yang Bisa Diambil

Sistem menghitung berapa SKS maksimal yang bisa diambil dari semester sekarang hingga target:

- **Semester 1 s/d `K+2`:** Maksimal 20 SKS per semester
- **Setelah `K+2`:** Maksimal 24 SKS per semester

Untuk S1 (`K=8`), cutoff-nya semester 10 — sama seperti sebelumnya.

**Fungsi:**
```php
function hitungSksMaksBisaDiambil($semesterSekarang, $semesterTarget, $capCutoff) {
    // $capCutoff = K + 2
    $totalSks = 0;
    for ($smt = $semesterSekarang; $smt <= $semesterTarget; $smt++) {
        if ($smt <= $capCutoff) {
            $totalSks += 20;
        } else {
            $totalSks += 24;
        }
    }
    return $totalSks;
}
```

**Contoh (S1, `K=8`, cutoff smt 10):**
- Mahasiswa semester 6 ingin lulus semester 8:
  - Semester 6: 20 SKS
  - Semester 7: 20 SKS
  - Semester 8: 20 SKS
  - **Total: 60 SKS**

---

## Prioritas Penentuan Status

Status ditentukan dengan **urutan prioritas** dari yang paling kritis:

### **Prioritas 1: KRITIS (🔴 Merah)**

#### Kondisi A: Sisa SKS Tidak Cukup Sampai Batas Kritis (`2K`)
```
if (sisa_sks > sksBisaDiambilSampai2K) {
    return 'kritis';
}
```

**Penjelasan:**
- Mahasiswa tidak akan bisa menyelesaikan SKS target bahkan jika mengambil SKS maksimal hingga semester `2K`
- Risiko DO sangat tinggi

**Contoh (S1, `K=8` → batas `2K=16`):**
- Semester aktif: 12
- SKS lulus: 80 → Sisa SKS: 64
- SKS bisa diambil S12-S16: (24×5) = 120
- 64 < 120 ✅ (masih aman)
- Tapi jika sisa SKS > 120, maka KRITIS

---

#### Kondisi B: Nilai E/D di Mata Kuliah Ganjil (Semester `2K-1`, Ganjil Terakhir)
```
if (semester_aktif == 2K - 1 && semester_ganjil) {
    if (ada_nilai_E_atau_D_di_matkul_ganjil) {
        return 'kritis';
    }
}
```

**Penjelasan:**
- Semester `2K-1` adalah semester ganjil terakhir sebelum batas kritis (S1: semester 15)
- Jika masih ada nilai E/D di mata kuliah semester ganjil (1..K)
- Mahasiswa mungkin tidak sempat mengulang

---

#### Kondisi C: Nilai E/D di Mata Kuliah Genap (Semester `2K`, Terakhir)
```
if (semester_aktif == 2K && semester_genap) {
    if (ada_nilai_E_atau_D_di_matkul_genap) {
        return 'kritis';
    }
}
```

**Penjelasan:**
- Semester `2K` adalah semester terakhir (S1: semester 16)
- Jika masih ada nilai E/D di mata kuliah semester genap (1..K)
- Mahasiswa tidak punya kesempatan mengulang lagi

> **Catatan migrasi:** sebelum EWS multi-jenjang, kondisi B/C ini hardcode
> semester 13/14 untuk S1. Setelah threshold diturunkan dari `2K-1`/`2K`,
> batasnya untuk S1 (`K=8`) bergeser ke semester **15/16**.

---

### **Prioritas 2: PERHATIAN (🟡 Kuning)**

#### Kondisi A: Sisa SKS Tidak Cukup Sampai Batas Perhatian (`K+2`)
```
if (sisa_sks > sksBisaDiambilSampaiKPlus2) {
    return 'perhatian';
}
```

**Penjelasan:**
- Mahasiswa tidak akan bisa menyelesaikan SKS target hingga semester `K+2`
- Target lulus mundur

**Contoh (S1, `K=8` → batas `K+2=10`):**
- Semester aktif: 8
- SKS lulus: 100 → Sisa SKS: 44
- SKS bisa diambil S8-S10: (20+20+20) = 60
- 44 < 60 ✅ (masih bisa lulus semester 10)
- Tapi jika sisa SKS > 60, maka PERHATIAN

---

#### Kondisi B: Nilai E/D di Mata Kuliah Ganjil (Semester `K+1`)
```
if (semester_aktif == K + 1 && semester_ganjil) {
    if (ada_nilai_E_atau_D_di_matkul_ganjil) {
        return 'perhatian';
    }
}
```

**Penjelasan:**
- Semester `K+1` adalah evaluasi perhatian (S1: semester 9)
- Masih ada nilai E/D di mata kuliah ganjil
- Risiko tidak lulus di masa kurikulum + toleransi

---

#### Kondisi C: Nilai E/D di Mata Kuliah Genap (Semester `K+2`)
```
if (semester_aktif == K + 2 && semester_genap) {
    if (ada_nilai_E_atau_D_di_matkul_genap) {
        return 'perhatian';
    }
}
```

---

### **Prioritas 3: NORMAL (🟢 Hijau)**

#### Kondisi A: Sisa SKS Tidak Cukup Sampai Batas Normal (`K`)
```
if (sisa_sks > sksBisaDiambilSampaiK) {
    return 'normal';
}
```

**Penjelasan:**
- Mahasiswa tidak akan bisa lulus dalam 1 masa kurikulum (S1: 8 semester)
- Tapi masih bisa lulus dalam masa kurikulum + toleransi
- Kondisi masih terkendali

---

#### Kondisi B: Nilai E/D di Mata Kuliah Ganjil (Semester `K-1`)
```
if (semester_aktif == K - 1 && semester_ganjil) {
    if (ada_nilai_E_atau_D_di_matkul_ganjil) {
        return 'normal';
    }
}
```

---

#### Kondisi C: Nilai E/D di Mata Kuliah Genap (Semester `K`)
```
if (semester_aktif == K && semester_genap) {
    if (ada_nilai_E_atau_D_di_matkul_genap) {
        return 'normal';
    }
}
```

---

### **Prioritas 4: TEPAT WAKTU (🔵 Biru)**

#### Kondisi (Semester `K-1` atau `K`):
```
kondisiSksBiru = (sisa_sks <= sksBisaDiambilSampaiK)

if (semester_aktif == K - 1 || semester_aktif == K) {
    if (kondisiSksBiru && jumlahNilaiE == 0 && jumlahNilaiD <= 1) {
        return 'tepat_waktu';
    }
}
```

**Penjelasan (S1, `K=8` → semester 7/8):**
- Mahasiswa bisa menyelesaikan sisa SKS hingga semester `K`
- Tidak ada nilai E sama sekali
- Maksimal 1 nilai D (toleransi)
- Diprediksi lulus tepat waktu di masa kurikulum

---

### **Default:**
```
return 'normal';
```

Jika tidak masuk kondisi apapun, default status adalah **Normal**.

---

## Kasus Khusus

### 1. Mahasiswa Sudah Lulus (SKS >= SKS target jenjang)

```
if (sks_lulus >= sks_target) {
    if (semester_aktif <= K) return 'tepat_waktu';
    if (semester_aktif <= K + 2) return 'normal';
    if (semester_aktif <= 2 * K) return 'perhatian';
    return 'kritis';
}
```

**Penjelasan (contoh S1, `K=8`, sks_target=144):**
- Mahasiswa sudah mengumpulkan SKS target
- Status ditentukan berdasarkan semester lulus:
  - ≤ Semester 8: Tepat Waktu
  - Semester 9-10: Normal
  - Semester 11-16: Perhatian
  - > Semester 16: Kritis (seharusnya tidak terjadi)

---

### 2. Update Nilai D dan E

Sistem secara otomatis mengupdate field `nilai_d_melebihi_batas` dan `nilai_e` sebelum menghitung status EWS.

**Logic:**

```php
// Ambil nilai TERAKHIR per mata kuliah (MAX id per matakuliah_id)
$latestKhs = get nilai terakhir per mata kuliah

$totalSksNilaiD = 0;
$countMKNilaiD = 0; // Hitung jumlah mata kuliah dengan nilai D
$adaNilaiE = false;

foreach ($latestKhs as $khs) {
    if ($khs->nilai_akhir_huruf === 'D') {
        $countMKNilaiD++;
        $totalSksNilaiD += $khs->sks;
    }
    if ($khs->nilai_akhir_huruf === 'E') {
        $adaNilaiE = true;
    }
}

// Cek apakah nilai D melebihi batas:
// 1. Maksimal 2 mata kuliah yang boleh mendapat nilai D
// 2. Total SKS tidak melebihi 5% dari SKS target jenjang mahasiswa
$maxSksNilaiD = $sksTarget * 0.05; // S1: 7.2 (5% dari 144), D3: 5.4 (5% dari 108), dst
$nilaiDMelebihiBatas = ($countMKNilaiD > 2) || ($totalSksNilaiD > $maxSksNilaiD);

// Update akademik_mahasiswa
nilai_d_melebihi_batas = $nilaiDMelebihiBatas ? 'yes' : 'no';
nilai_e = $adaNilaiE ? 'yes' : 'no';
```

**Catatan Penting:**
- Hanya nilai **TERAKHIR** per mata kuliah yang dihitung
- Jika mahasiswa retake dan dapat nilai lebih baik, nilai lama tidak dihitung
- **Batas nilai D:** 
  - Maksimal **2 mata kuliah** dengan nilai D
  - Total SKS nilai D tidak melebihi **5% dari SKS target jenjang** (S1/D4: 7.2 dari 144, D3: 5.4 dari 108, dst — lihat [Multi-Jenjang](#multi-jenjang-d2d3d4s1profesis2s3))
  - Berlaku untuk semua mahasiswa terlepas dari total SKS lulus mereka
  - Contoh (S1): 3 SKS + 3 SKS (2 MK) ✅ | 2 SKS + 2 SKS + 2 SKS (3 MK) ❌

---

## SPS (Surat Peringatan Studi)

### SPS1
- **Kondisi:** IPS semester 1 < 2.0
- **Field:** `SPS1 = 'yes'`
- **Keterangan:** Peringatan pertama

### SPS2
- **Kondisi:** IPS semester 2 < 2.0
- **Field:** `SPS2 = 'yes'`
- **Keterangan:** Peringatan kedua

### SPS3
- **Kondisi:** IPS semester 3 < 2.0
- **Field:** `SPS3 = 'yes'`
- **Keterangan:** Peringatan ketiga, **WAJIB REKOMITMEN**
- **Action:** Mahasiswa harus mengisi surat rekomitmen
- **Data:** `id_rekomitmen`, `tanggal_pengajuan_rekomitmen`, `status_rekomitmen`, `link_rekomitmen`

---

## Trigger Recalculation

### Manual Trigger (Satu Mahasiswa)
```
POST /api/ews/koor/mahasiswa/{mahasiswaId}/recalculate-status
```

**Flow:**
1. Find akademik_mahasiswa
2. Update nilai D dan E dari KHS terbaru
3. Hitung status EWS
4. Hitung status kelulusan
5. UpdateOrCreate early_warning_system
6. Return hasil

---

### Batch Trigger (Semua Mahasiswa)
```
POST /api/ews/koor/recalculate-all-status
```

**Flow:**
1. Dispatch background job `RecalculateAllEwsJob`
2. Job process semua akademik_mahasiswa (exclude Lulus & DO)
3. Chunk 100 data per batch untuk efisiensi
4. Update status EWS untuk setiap mahasiswa
5. Log error jika ada
6. Return total processed dan updated

---

### Auto Trigger (Observer) — ⚠️ BELUM DIIMPLEMENTASIKAN

> **Status:** `AkademikMahasiswaObserver` **belum ada** di `app/Observers/`.
> Saat ini recalculation hanya dilakukan manual lewat endpoint
> `recalculate-status` (1 mahasiswa) dan `recalculate-all-status` (batch job).
> Rancangan observer di bawah masih berupa rencana, bukan kode yang berjalan.

Rancangan: observer otomatis trigger recalculation saat:

- **created:** Mahasiswa baru ditambahkan
- **updated:** Data akademik mahasiswa diupdate (IPK, SKS, dll)

**Rancangan code:**
```php
class AkademikMahasiswaObserver
{
    public function created(AkademikMahasiswa $akademik)
    {
        app(EwsService::class)->updateStatus($akademik);
    }

    public function updated(AkademikMahasiswa $akademik)
    {
        app(EwsService::class)->updateStatus($akademik);
    }
}
```

---

## Contoh Perhitungan

> Semua contoh di bawah pakai jenjang **S1** (`K=8`, sks_target=144) sebagai
> ilustrasi. Rumusnya sama untuk jenjang lain, tinggal ganti `K`/sks_target
> sesuai tabel di [Multi-Jenjang](#multi-jenjang-d2d3d4s1profesis2s3).

### Contoh 1: Mahasiswa Tepat Waktu (contoh S1)

**Data:**
- Semester aktif: 7 (ganjil)
- SKS lulus: 110
- Sisa SKS: 34
- Nilai E: 0
- Nilai D: 1

**Perhitungan:**
```
sksBisaDiambilSD8 = 20 + 20 = 40 (semester 7-8)
sisa_sks (34) <= sksBisaDiambilSD8 (40) ✅
jumlahNilaiE == 0 ✅
jumlahNilaiD (1) <= 1 ✅
```

**Result:** ✅ **TEPAT WAKTU**

---

### Contoh 2: Mahasiswa Normal (contoh S1)

**Data:**
- Semester aktif: 7 (ganjil)
- SKS lulus: 90
- Sisa SKS: 54
- Nilai E: 0
- Nilai D: 3

**Perhitungan:**
```
sksBisaDiambilSD8 = 20 + 20 = 40
sisa_sks (54) > sksBisaDiambilSD8 (40) ❌

Tapi:
sksBisaDiambilSD10 = 40 + 20 + 20 = 80 (semester 7-10)
sisa_sks (54) <= sksBisaDiambilSD10 (80) ✅

Dan tidak masuk kondisi kritis di semester 15-16
```

**Result:** ✅ **NORMAL** (tidak bisa lulus 4 tahun, tapi bisa 4-5 tahun)

---

### Contoh 3: Mahasiswa Perhatian (contoh S1)

**Data:**
- Semester aktif: 9 (ganjil)
- SKS lulus: 80
- Sisa SKS: 64
- Ada nilai E di mata kuliah semester 3

**Perhitungan:**
```
sksBisaDiambilSD10 = 20 + 20 = 40 (semester 9-10)
sisa_sks (64) > sksBisaDiambilSD10 (40) ❌

ATAU

semester_aktif == 9 && ada nilai E di matkul ganjil ✅
```

**Result:** ✅ **PERHATIAN**

---

### Contoh 4: Mahasiswa Kritis (contoh S1)

**Data:**
- Semester aktif: 15 (ganjil, `= 2K-1`)
- SKS lulus: 60
- Sisa SKS: 84
- Ada nilai E di mata kuliah semester 1

**Perhitungan:**
```
sksBisaDiambilSampai2K = 24 + 24 = 48 (semester 15-16)
sisa_sks (84) > sksBisaDiambilSampai2K (48) ✅ → KRITIS

ATAU

semester_aktif == 15 (2K-1) && ada nilai E di matkul ganjil ✅ → KRITIS
```

**Result:** 🔴 **KRITIS** (risiko DO sangat tinggi)

---

## Summary Diagram

```
┌─────────────────────────────────────────────┐
│         START: Hitung Status EWS            │
└──────────────┬──────────────────────────────┘
               │
               ▼
       ┌───────────────┐
       │ Update Nilai  │
       │   D dan E     │
       └───────┬───────┘
               │
               ▼
       ┌──────────────────────────────────┐
       │ Sudah Lulus (SKS >= sks_target)? │
       └───────┬──────────┬───────────────┘
               │ YES      │ NO
               ▼          │
       Return status      │ 
       by semester        │
                          ▼
              ┌────────────────────────┐
              │ Sisa SKS > Maks 2K?    │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                  KRITIS     │
                             ▼
              ┌───────────────────────────┐
              │ S(2K-1)/2K ada nilai E/D? │
              └───────┬──────┬────────────┘
                      │ YES  │ NO
                      ▼      │
                  KRITIS     │
                             ▼
              ┌────────────────────────┐
              │ Sisa SKS > Maks K+2?   │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                  PERHATIAN  │
                             ▼
              ┌─────────────────────────────┐
              │ S(K+1)/(K+2) ada nilai E/D? │
              └───────┬──────┬──────────────┘
                      │ YES  │ NO
                      ▼      │
                  PERHATIAN  │
                             ▼
              ┌────────────────────────┐
              │ Sisa SKS > Maks K?     │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                   NORMAL    │
                             ▼
              ┌────────────────────────┐
              │ S(K-1)/K ada nilai E/D?│
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                   NORMAL    │
                             ▼
              ┌─────────────────────────────┐
              │ S(K-1)/K + SKS OK + Max 1   │
              │ nilai D + No nilai E?       │
              └───────┬──────┬──────────────┘
                      │ YES  │ NO
                      ▼      │
                TEPAT WAKTU  │
                             ▼
                          NORMAL
```

> Untuk S1 (`K=8`): `2K=16`, `K+2=10`, `K=8` — sama seperti diagram lama,
> hanya labelnya sekarang generik per jenjang.

---

## File Terkait

- **Config:** `config/ews.php` (tabel kurikulum & SKS target per jenjang)
- **Service:** `app/Services/EwsServiceBase.php` (+ `SuperFakultas/EwsService.php`, `Admin/EwsService.php`)
- **Model:** `app/Models/EarlyWarningSystem.php`
- **Observer:** `app/Observers/AkademikMahasiswaObserver.php` — ⚠️ belum diimplementasikan
- **Job:** `app/Jobs/RecalculateAllEwsJob.php`
- **Controller:** `app/Http/Controllers/SuperFakultas/EwsController.php`, `app/Http/Controllers/Admin/EwsController.php`

---

## Catatan Implementasi

1. **Performa:** Batch calculation menggunakan chunk(100) untuk menghindari memory overflow
2. **Data Consistency:** Selalu update nilai D/E sebelum hitung status
3. **Nilai Terakhir:** Hanya nilai terakhir per mata kuliah yang dihitung (MAX id)
4. **Exclude:** Mahasiswa Lulus dan DO tidak di-recalculate
5. **Observer:** Auto-trigger saat data akademik berubah — ⚠️ belum diimplementasikan (recalc manual lewat endpoint)
6. **Background Job:** Recalculate all berjalan di queue untuk performa
