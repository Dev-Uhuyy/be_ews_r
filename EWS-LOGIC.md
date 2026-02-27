# Early Warning System (EWS) - Business Logic Documentation

## Overview
Early Warning System (EWS) adalah sistem untuk mendeteksi mahasiswa yang berisiko tidak lulus tepat waktu atau mengalami masalah akademik. Sistem memberikan status peringatan berdasarkan berbagai indikator akademik.

---

## Status EWS

Terdapat **4 tingkat status** dalam EWS:

| Status | Warna | Keterangan |
|--------|-------|------------|
| **Tepat Waktu** | 🔵 Biru | Mahasiswa on track untuk lulus dalam 4 tahun (8 semester) |
| **Normal** | 🟢 Hijau | Mahasiswa dalam kondisi normal, berpotensi lulus dalam 4 tahun |
| **Perhatian** | 🟡 Kuning | Mahasiswa berisiko tidak lulus tepat waktu, target 5 tahun (10 semester) |
| **Kritis** | 🔴 Merah | Mahasiswa berisiko tinggi DO atau tidak lulus dalam 7 tahun (14 semester) |

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
2. ✅ **SKS Lulus >= 144**
3. ✅ **MK Nasional Selesai** (mk_nasional = 'yes')
4. ✅ **MK Fakultas Selesai** (mk_fakultas = 'yes')
5. ✅ **MK Prodi Selesai** (mk_prodi = 'yes')
6. ✅ **TIDAK ada nilai E** (nilai_e = 'no')
7. ✅ **Nilai D tidak melebihi 5% dari SKS lulus** (nilai_d_melebihi_batas = 'no')

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
sisa_sks = max(0, 144 - sks_lulus)
jumlah_nilai_e = COUNT(nilai E dari KHS)
jumlah_nilai_d = COUNT(nilai D dari KHS)
```

### SKS Maksimal yang Bisa Diambil

Sistem menghitung berapa SKS maksimal yang bisa diambil dari semester sekarang hingga target:

- **Semester 1-10:** Maksimal 20 SKS per semester
- **Semester 11-14:** Maksimal 24 SKS per semester

**Fungsi:**
```php
function hitungSksMaksBisaDiambil($semesterSekarang, $semesterTarget) {
    $totalSks = 0;
    for ($smt = $semesterSekarang; $smt <= $semesterTarget; $smt++) {
        if ($smt <= 10) {
            $totalSks += 20;
        } else {
            $totalSks += 24;
        }
    }
    return $totalSks;
}
```

**Contoh:**
- Mahasiswa semester 6 ingin lulus semester 8:
  - Semester 6: 20 SKS
  - Semester 7: 20 SKS
  - Semester 8: 20 SKS
  - **Total: 60 SKS**

---

## Prioritas Penentuan Status

Status ditentukan dengan **urutan prioritas** dari yang paling kritis:

### **Prioritas 1: KRITIS (🔴 Merah)**

#### Kondisi A: Sisa SKS Tidak Cukup untuk 7 Tahun
```
if (sisa_sks > sksBisaDiambilSD14) {
    return 'kritis';
}
```

**Penjelasan:**
- Mahasiswa tidak akan bisa menyelesaikan 144 SKS bahkan jika mengambil SKS maksimal hingga semester 14
- Risiko DO sangat tinggi

**Contoh:**
- Semester aktif: 12
- SKS lulus: 80 → Sisa SKS: 64
- SKS bisa diambil S12-S14: (24+24+24) = 72
- 64 < 72 ✅ (masih aman)
- Tapi jika sisa SKS > 72, maka KRITIS

---

#### Kondisi B: Nilai E/D di Mata Kuliah Ganjil (Semester 13 Ganjil)
```
if (semester_aktif == 13 && semester_ganjil) {
    if (ada_nilai_E_atau_D_di_matkul_ganjil) {
        return 'kritis';
    }
}
```

**Penjelasan:**
- Semester 13 adalah semester ganjil terakhir (tahun ke-7)
- Jika masih ada nilai E/D di mata kuliah semester 1, 3, 5, 7
- Mahasiswa mungkin tidak sempat mengulang

---

#### Kondisi C: Nilai E/D di Mata Kuliah Genap (Semester 14 Genap)
```
if (semester_aktif == 14 && semester_genap) {
    if (ada_nilai_E_atau_D_di_matkul_genap) {
        return 'kritis';
    }
}
```

**Penjelasan:**
- Semester 14 adalah semester terakhir (tahun ke-7)
- Jika masih ada nilai E/D di mata kuliah semester 2, 4, 6, 8
- Mahasiswa tidak punya kesempatan mengulang lagi

---

### **Prioritas 2: PERHATIAN (🟡 Kuning)**

#### Kondisi A: Sisa SKS Tidak Cukup untuk 5 Tahun
```
if (sisa_sks > sksBisaDiambilSD10) {
    return 'perhatian';
}
```

**Penjelasan:**
- Mahasiswa tidak akan bisa menyelesaikan 144 SKS hingga semester 10 (5 tahun)
- Target lulus mundur ke 5-7 tahun

**Contoh:**
- Semester aktif: 8
- SKS lulus: 100 → Sisa SKS: 44
- SKS bisa diambil S8-S10: (20+20+20) = 60
- 44 < 60 ✅ (masih bisa lulus semester 10)
- Tapi jika sisa SKS > 60, maka PERHATIAN

---

#### Kondisi B: Nilai E/D di Mata Kuliah Ganjil (Semester 9)
```
if (semester_aktif == 9 && semester_ganjil) {
    if (ada_nilai_E_atau_D_di_matkul_ganjil) {
        return 'perhatian';
    }
}
```

**Penjelasan:**
- Semester 9 adalah evaluasi 5 tahun
- Masih ada nilai E/D di mata kuliah ganjil
- Risiko tidak lulus 5 tahun

---

#### Kondisi C: Nilai E/D di Mata Kuliah Genap (Semester 10)
```
if (semester_aktif == 10 && semester_genap) {
    if (ada_nilai_E_atau_D_di_matkul_genap) {
        return 'perhatian';
    }
}
```

---

### **Prioritas 3: NORMAL (🟢 Hijau)**

#### Kondisi A: Sisa SKS Tidak Cukup untuk 4 Tahun
```
if (sisa_sks > sksBisaDiambilSD8) {
    return 'normal';
}
```

**Penjelasan:**
- Mahasiswa tidak akan bisa lulus dalam 4 tahun (8 semester)
- Tapi masih bisa lulus dalam 4-5 tahun
- Kondisi masih terkendali

---

#### Kondisi B: Nilai E/D di Mata Kuliah Ganjil (Semester 7)
```
if (semester_aktif == 7 && semester_ganjil) {
    if (ada_nilai_E_atau_D_di_matkul_ganjil) {
        return 'normal';
    }
}
```

---

#### Kondisi C: Nilai E/D di Mata Kuliah Genap (Semester 8)
```
if (semester_aktif == 8 && semester_genap) {
    if (ada_nilai_E_atau_D_di_matkul_genap) {
        return 'normal';
    }
}
```

---

### **Prioritas 4: TEPAT WAKTU (🔵 Biru)**

#### Kondisi (Semester 7 atau 8):
```
kondisiSksBiru = (sisa_sks <= sksBisaDiambilSD8)

if (semester_aktif == 7 || semester_aktif == 8) {
    if (kondisiSksBiru && jumlahNilaiE == 0 && jumlahNilaiD <= 1) {
        return 'tepat_waktu';
    }
}
```

**Penjelasan:**
- Mahasiswa bisa menyelesaikan sisa SKS hingga semester 8
- Tidak ada nilai E sama sekali
- Maksimal 1 nilai D (toleransi)
- Diprediksi lulus tepat waktu 4 tahun

---

### **Default:**
```
return 'normal';
```

Jika tidak masuk kondisi apapun, default status adalah **Normal**.

---

## Kasus Khusus

### 1. Mahasiswa Sudah Lulus (SKS >= 144)

```
if (sks_lulus >= 144) {
    if (semester_aktif <= 8) return 'tepat_waktu';
    if (semester_aktif <= 10) return 'normal';
    if (semester_aktif <= 14) return 'perhatian';
    return 'kritis';
}
```

**Penjelasan:**
- Mahasiswa sudah mengumpulkan 144 SKS
- Status ditentukan berdasarkan semester lulus:
  - ≤ Semester 8: Tepat Waktu
  - Semester 9-10: Normal
  - Semester 11-14: Perhatian
  - > Semester 14: Kritis (seharusnya tidak terjadi)

---

### 2. Update Nilai D dan E

Sistem secara otomatis mengupdate field `nilai_d_melebihi_batas` dan `nilai_e` sebelum menghitung status EWS.

**Logic:**

```php
// Ambil nilai TERAKHIR per mata kuliah (MAX id per matakuliah_id)
$latestKhs = get nilai terakhir per mata kuliah

$totalSksNilaiD = 0;
$adaNilaiE = false;

foreach ($latestKhs as $khs) {
    if ($khs->nilai_akhir_huruf === 'D') {
        $totalSksNilaiD += $khs->sks;
    }
    if ($khs->nilai_akhir_huruf === 'E') {
        $adaNilaiE = true;
    }
}

// Cek batas 5% dari SKS lulus
$maxSksNilaiD = $sks_lulus * 0.05;
$nilaiDMelebihiBatas = ($totalSksNilaiD > $maxSksNilaiD);

// Update akademik_mahasiswa
nilai_d_melebihi_batas = $nilaiDMelebihiBatas ? 'yes' : 'no';
nilai_e = $adaNilaiE ? 'yes' : 'no';
```

**Catatan Penting:**
- Hanya nilai **TERAKHIR** per mata kuliah yang dihitung
- Jika mahasiswa retake dan dapat nilai lebih baik, nilai lama tidak dihitung
- Batas nilai D: 5% dari SKS lulus (misal 144 SKS → maksimal 7.2 SKS nilai D)

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

### Auto Trigger (Observer)

System memiliki `AkademikMahasiswaObserver` yang otomatis trigger recalculation saat:

- **created:** Mahasiswa baru ditambahkan
- **updated:** Data akademik mahasiswa diupdate (IPK, SKS, dll)

**Code:**
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

### Contoh 1: Mahasiswa Tepat Waktu

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

### Contoh 2: Mahasiswa Normal

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

Dan tidak critical untuk semester 13-14
```

**Result:** ✅ **NORMAL** (tidak bisa lulus 4 tahun, tapi bisa 4-5 tahun)

---

### Contoh 3: Mahasiswa Perhatian

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

### Contoh 4: Mahasiswa Kritis

**Data:**
- Semester aktif: 13 (ganjil)
- SKS lulus: 60
- Sisa SKS: 84
- Ada nilai E di mata kuliah semester 1

**Perhitungan:**
```
sksBisaDiambilSD14 = 24 + 24 = 48 (semester 13-14)
sisa_sks (84) > sksBisaDiambilSD14 (48) ✅ → KRITIS

ATAU

semester_aktif == 13 && ada nilai E di matkul ganjil ✅ → KRITIS
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
       ┌───────────────────────────┐
       │ Sudah Lulus (SKS >= 144)? │
       └───────┬──────────┬────────┘
               │ YES      │ NO
               ▼          │
       Return status      │ 
       by semester        │
                          ▼
              ┌───────────────────────┐
              │ Sisa SKS > Maks S14?  │
              └───────┬──────┬────────┘
                      │ YES  │ NO
                      ▼      │
                  KRITIS     │
                             ▼
              ┌────────────────────────┐
              │ S13/14 ada nilai E/D?  │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                  KRITIS     │
                             ▼
              ┌────────────────────────┐
              │ Sisa SKS > Maks S10?   │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                  PERHATIAN  │
                             ▼
              ┌────────────────────────┐
              │ S9/10 ada nilai E/D?   │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                  PERHATIAN  │
                             ▼
              ┌────────────────────────┐
              │ Sisa SKS > Maks S8?    │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                   NORMAL    │
                             ▼
              ┌────────────────────────┐
              │ S7/8 ada nilai E/D?    │
              └───────┬──────┬─────────┘
                      │ YES  │ NO
                      ▼      │
                   NORMAL    │
                             ▼
              ┌─────────────────────────────┐
              │ S7/8 + SKS OK + Max 1 nilai │
              │ D + No nilai E?             │
              └───────┬──────┬──────────────┘
                      │ YES  │ NO
                      ▼      │
                TEPAT WAKTU  │
                             ▼
                          NORMAL
```

---

## File Terkait

- **Service:** `app/Services/EwsService.php`
- **Model:** `app/Models/EarlyWarningSystem.php`
- **Observer:** `app/Observers/AkademikMahasiswaObserver.php`
- **Job:** `app/Jobs/RecalculateAllEwsJob.php`
- **Controller:** `app/Http/Controllers/Koor/EwsController.php`

---

## Catatan Implementasi

1. **Performa:** Batch calculation menggunakan chunk(100) untuk menghindari memory overflow
2. **Data Consistency:** Selalu update nilai D/E sebelum hitung status
3. **Nilai Terakhir:** Hanya nilai terakhir per mata kuliah yang dihitung (MAX id)
4. **Exclude:** Mahasiswa Lulus dan DO tidak di-recalculate
5. **Observer:** Auto-trigger saat data akademik berubah
6. **Background Job:** Recalculate all berjalan di queue untuk performa
