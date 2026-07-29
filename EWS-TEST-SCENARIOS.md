# EWS — Skenario Test Multi-Jenjang

Matrix skenario yang membuktikan logic EWS multi-jenjang di [`EwsServiceBase.php`](app/Services/EwsServiceBase.php)
jalan benar untuk semua 7 jenjang, sesuai rumus di [`EWS-LOGIC.md`](EWS-LOGIC.md#multi-jenjang-d2d3d4s1profesis2s3).

Jalankan semua skenario di bawah:

```bash
php artisan test --filter=Ews
```

---

## Parameter per Jenjang

| Jenjang | `K` | SKS target | Batas Normal (`K`) | Batas Perhatian (`K+2`) | Batas Kritis | Override? |
|---|---|---|---|---|---|---|
| D2 | 4 | 72 | 4 | 6 | 8 (`2K`) | tidak |
| D3 | 6 | 108 | 6 | 8 | 12 (`2K`) | tidak |
| D4 | 8 | 144 | 8 | 10 | 16 (`2K`) | tidak |
| **S1** | 8 | 144 | 8 | 10 | **14** | **ya** — `config('ews.jenjang.S1.batas_kritis')`, bukan `2K=16` |
| Profesi | 4 | 24 | 4 | 6 | 8 (`2K`) | tidak |
| S2 | 4 | 36 | 4 | 6 | 8 (`2K`) | tidak |
| S3 | 8 | 42 | 8 | 10 | 16 (`2K`) | tidak |

---

## Skenario Status EWS (tepat_waktu / normal / perhatian / kritis)

Tiap baris = 1 test method di [`tests/Feature/EwsStatusTest.php`](tests/Feature/EwsStatusTest.php).
Kolom "Sisa SKS" & "SKS bisa diambil" dipakai buat verifikasi manual rumus `hitungSksMaksBisaDiambil()`.

| Jenjang | Semester | SKS lulus | Sisa SKS | SKS bisa diambil s.d. batas | Expected | Test method |
|---|---|---|---|---|---|---|
| S1 | 7 | 110 | 34 | 40 (s.d. K=8) | `tepat_waktu` | `all_4_status_values_are_reachable` |
| S1 | 7 | 80 | 64 | 40 (K=8) / 80 (K+2=10) | `normal` | `all_4_status_values_are_reachable` |
| S1 | 9 | 50 | 94 | 60 (K+2=10) | `perhatian` | `all_4_status_values_are_reachable` |
| S1 | 13 | 20 | 124 | 0 (> batas kritis 14) | `kritis` | `all_4_status_values_are_reachable` |
| S1 | 14 | 130 | 14 | 24 (s.d. batas kritis 14) | `perhatian` | `s1_kritis_boundary_stays_at_14` |
| S1 | 15 | 130 | 14 | 0 (> batas kritis 14) | `kritis` | `s1_kritis_boundary_stays_at_14` |
| D3 (`K=6`) | 6 | 100 | 8 | 20 (s.d. K=6) | `tepat_waktu` | `d3_status_values_follow_kurikulum_6_semester` |
| D3 (`K=6`) | 13 | 50 | 58 | 0 (> batas kritis 12) | `kritis` | `d3_status_values_follow_kurikulum_6_semester` |
| S2 (`K=4`) | 4 | 30 | 6 | 20 (s.d. K=4) | `tepat_waktu` | `s2_status_values_follow_kurikulum_4_semester` |
| S2 (`K=4`) | 9 | 10 | 26 | 0 (> batas kritis 8) | `kritis` | `s2_status_values_follow_kurikulum_4_semester` |
| D2 (`K=4`) | 4 | 60 | 12 | 20 (s.d. K=4) | `tepat_waktu` | `d2_status_values_follow_kurikulum_4_semester` |
| D2 (`K=4`) | 9 | 10 | 62 | 0 (> batas kritis 8) | `kritis` | `d2_status_values_follow_kurikulum_4_semester` |
| D4 (`K=8`, no override) | 8 | 130 | 14 | 20 (s.d. K=8) | `tepat_waktu` | `d4_status_values_follow_kurikulum_8_semester_no_override` |
| D4 (`K=8`, no override) | 17 | 50 | 94 | 0 (> batas kritis 16, generik `2K`) | `kritis` | `d4_status_values_follow_kurikulum_8_semester_no_override` |
| Profesi (`K=4`) | 4 | 20 | 4 | 20 (s.d. K=4) | `tepat_waktu` | `profesi_status_values_follow_kurikulum_4_semester` |
| Profesi (`K=4`) | 9 | 2 | 22 | 0 (> batas kritis 8) | `kritis` | `profesi_status_values_follow_kurikulum_4_semester` |
| S3 (`K=8`) | 8 | 38 | 4 | 20 (s.d. K=8) | `tepat_waktu` | `s3_status_values_follow_kurikulum_8_semester` |
| S3 (`K=8`) | 17 | 5 | 37 | 0 (> batas kritis 16, generik `2K`) | `kritis` | `s3_status_values_follow_kurikulum_8_semester` |

> Catatan: D4/Profesi/S2/S3/D2/D3 semua pakai rumus generik `batasKritis = 2K` tanpa
> override. **Hanya S1** yang di-hardcode ke `14` (compat historis, lihat
> [EWS-LOGIC.md](EWS-LOGIC.md#multi-jenjang-d2d3d4s1profesis2s3)).

---

## Skenario Kelulusan (Eligible / Non-eligible)

Logic kelulusan sudah generik lewat `sksTarget` per jenjang (bukan literal 144), dibuktikan
di [`tests/Feature/EwsKelulusanTest.php`](tests/Feature/EwsKelulusanTest.php) — 7 kondisi +
boundary IPK/SKS/nilai-D, semua jenjang pakai jalur kode yang sama karena `sksTarget` cuma
parameter, tidak ada percabangan per jenjang di `hitungStatusKelulusan()`.

| Kondisi | Expected | Test method |
|---|---|---|
| Semua 7 syarat terpenuhi | `eligible` | `all_7_conditions_met_yields_eligible` |
| IPK ≤ 2.0 | `noneligible` | `condition_ipk_fails`, `boundary_ipk_exactly_2_0_is_noneligible` |
| SKS lulus < target | `noneligible` | `condition_sks_lulus_fails` |
| SKS lulus == target (exact) | `eligible` | `boundary_sks_exactly_144_is_eligible` |
| Ada nilai E | `noneligible` | `condition_nilai_e_yes_yields_noneligible` |
| Nilai D > 2 MK | `noneligible` | `condition_d_melebihi_batas_yields_noneligible` |
| Nilai D tepat 2 MK | `eligible` | `boundary_d_count_exactly_2_is_eligible` |

---

## Skenario Branch Detail (30+ kasus, S1)

Percabangan internal `hitungStatus()` (branch a–l dari diagram di `EWS-LOGIC.md`) dibuktikan
lengkap di [`tests/Feature/EwsCalculationTest.php`](tests/Feature/EwsCalculationTest.php) —
termasuk kasus retake (nilai terakhir menang) dan batch `updateAllStatus()` (exclude Lulus/DO).
Ini semua S1-only by default karena `Prodi::factory()` fallback ke S1 kalau `gelar` tidak
di-set — sengaja dipakai buat isolasi branch tanpa noise variabel jenjang.

## Skenario Seeder (integration, DB nyata)

Untuk cek manual lewat DB/Postman (bukan cuma PHPUnit), 50 skenario deterministik S1 sudah
ada di [`database/seeders/EwsTargetedScenarioSeeder.php`](database/seeders/EwsTargetedScenarioSeeder.php)
(prefix NIM `.SCN.`). Seeder ini belum cover multi-jenjang (perlu Prodi master data dengan
`gelar` selain S1 yang belum ada di data seed saat ini) — kalau butuh verifikasi manual
lintas jenjang juga, tambahin di sini.
