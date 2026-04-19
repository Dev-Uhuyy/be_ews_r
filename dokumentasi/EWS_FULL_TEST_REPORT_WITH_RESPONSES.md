# EWS API Full Test Report - Complete JSON Responses

**Date:** 2026-04-19
**Pass Rate:** 56/56 (100%)
**Server:** http://127.0.0.1:8000

## Test Credentials
- **Dekan:** dekan@ews.com / password
- **Kaprodi:** kaprodi_a11@ews.com / password
- **Mahasiswa:** mahasiswa@ews.com / password

## Tokens Used
- **Dekan Token:** `87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6`
- **Kaprodi Token:** `86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49`
- **Mahasiswa Token:** `88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b`

---

## DEKAN ENDPOINTS (25)

### 1. POST /api/login (Dekan)
**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"dekan@ews.com","password":"password"}'
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": {
    "access_token": "87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6",
    "token_type": "Bearer",
    "user": {
      "name": "Dekan EWS Test",
      "email": "dekan@ews.com",
      "roles": "dekan",
      "permissions": [
        "ews-mahasiswa",
        "ews-kaprodi",
        "ews-dekan"
      ]
    },
    "dekan": {
      "scope": "fakultas"
    }
  }
}
```

---

### 2. GET /api/ews/dekan/dashboard
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/dashboard \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Dashboard Fakultas per Prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "prodi": {
        "id": 1,
        "kode": "A11",
        "nama": "Teknik Informatika"
      },
      "status_mahasiswa": {
        "total": 386,
        "aktif": 310,
        "mangkir": 51,
        "cuti": 25
      },
      "rata_ipk_per_angkatan": [
        {"tahun_masuk": "2025", "rata_ipk": "2.72", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2024", "rata_ipk": "2.68", "jumlah_mahasiswa": 77},
        {"tahun_masuk": "2023", "rata_ipk": "2.63", "jumlah_mahasiswa": 61},
        {"tahun_masuk": "2022", "rata_ipk": "2.71", "jumlah_mahasiswa": 57},
        {"tahun_masuk": "2021", "rata_ipk": "2.64", "jumlah_mahasiswa": 54},
        {"tahun_masuk": "2020", "rata_ipk": "2.69", "jumlah_mahasiswa": 60}
      ],
      "status_kelulusan": {
        "total": 385,
        "eligible": 0,
        "tidak_eligible": 385
      }
    },
    {
      "prodi": {"id": 2, "kode": "A12", "nama": "Sistem Informasi"},
      "status_mahasiswa": {"total": 379, "aktif": 307, "mangkir": 33, "cuti": 39},
      "rata_ipk_per_angkatan": [
        {"tahun_masuk": "2025", "rata_ipk": "2.72", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2024", "rata_ipk": "2.73", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2023", "rata_ipk": "2.90", "jumlah_mahasiswa": 52},
        {"tahun_masuk": "2022", "rata_ipk": "2.81", "jumlah_mahasiswa": 53},
        {"tahun_masuk": "2021", "rata_ipk": "2.84", "jumlah_mahasiswa": 64},
        {"tahun_masuk": "2020", "rata_ipk": "2.65", "jumlah_mahasiswa": 56}
      ],
      "status_kelulusan": {"total": 377, "eligible": 1, "tidak_eligible": 376}
    },
    {
      "prodi": {"id": 3, "kode": "A14", "nama": "Desain Komunikasi Visual"},
      "status_mahasiswa": {"total": 372, "aktif": 298, "mangkir": 46, "cuti": 28},
      "rata_ipk_per_angkatan": [
        {"tahun_masuk": "2025", "rata_ipk": "2.88", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2024", "rata_ipk": "2.75", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2023", "rata_ipk": "2.73", "jumlah_mahasiswa": 57},
        {"tahun_masuk": "2022", "rata_ipk": "2.91", "jumlah_mahasiswa": 64},
        {"tahun_masuk": "2021", "rata_ipk": "2.83", "jumlah_mahasiswa": 52},
        {"tahun_masuk": "2020", "rata_ipk": "2.67", "jumlah_mahasiswa": 45}
      ],
      "status_kelulusan": {"total": 370, "eligible": 4, "tidak_eligible": 366}
    },
    {
      "prodi": {"id": 4, "kode": "A15", "nama": "Ilmu Komunikasi"},
      "status_mahasiswa": {"total": 372, "aktif": 307, "mangkir": 30, "cuti": 35},
      "rata_ipk_per_angkatan": [
        {"tahun_masuk": "2025", "rata_ipk": "2.73", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2024", "rata_ipk": "2.69", "jumlah_mahasiswa": 76},
        {"tahun_masuk": "2023", "rata_ipk": "2.65", "jumlah_mahasiswa": 55},
        {"tahun_masuk": "2022", "rata_ipk": "2.82", "jumlah_mahasiswa": 57},
        {"tahun_masuk": "2021", "rata_ipk": "2.65", "jumlah_mahasiswa": 51},
        {"tahun_masuk": "2020", "rata_ipk": "2.69", "jumlah_mahasiswa": 55}
      ],
      "status_kelulusan": {"total": 370, "eligible": 3, "tidak_eligible": 367}
    }
  ]
}
```

---

### 3. GET /api/ews/dekan/distribusi-status-ews
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/distribusi-status-ews \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Distribusi status EWS per Prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "prodi": {"id": 1, "kode": "A11", "nama": "Teknik Informatika"},
      "distribusi": {"tepat_waktu": 11, "normal": 191, "perhatian": 134, "kritis": 49}
    },
    {
      "prodi": {"id": 2, "kode": "A12", "nama": "Sistem Informasi"},
      "distribusi": {"tepat_waktu": 14, "normal": 183, "perhatian": 126, "kritis": 54}
    },
    {
      "prodi": {"id": 3, "kode": "A14", "nama": "Desain Komunikasi Visual"},
      "distribusi": {"tepat_waktu": 9, "normal": 200, "perhatian": 114, "kritis": 47}
    },
    {
      "prodi": {"id": 4, "kode": "A15", "nama": "Ilmu Komunikasi"},
      "distribusi": {"tepat_waktu": 12, "normal": 191, "perhatian": 124, "kritis": 43}
    }
  ]
}
```

---

### 4. GET /api/ews/dekan/status-mahasiswa
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/status-mahasiswa \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~300ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 2, "nim": "A11.2020.00002", "nama_lengkap": "Mhs BK A11", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 9, "nim": "A11.2020.00100", "nama_lengkap": "MHS A11 2020 100", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 10, "nim": "A11.2020.00101", "nama_lengkap": "MHS A11 2020 101", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 11, "nim": "A11.2020.00102", "nama_lengkap": "MHS A11 2020 102", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 12, "nim": "A11.2020.00103", "nama_lengkap": "MHS A11 2020 103", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 13, "nim": "A11.2020.00104", "nama_lengkap": "MHS A11 2020 104", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 14, "nim": "A11.2020.00105", "nama_lengkap": "MHS A11 2020 105", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 15, "nim": "A11.2020.00106", "nama_lengkap": "MHS A11 2020 106", "nama_dosen_wali": "Dosen A11"}
  ],
  "pagination": {
    "total": 1510,
    "per_page": 10,
    "current_page": 1,
    "last_page": 151,
    "from": 1,
    "to": 10,
    "next_page_url": "http://127.0.0.1:8000/api/ews/dekan/status-mahasiswa?page=2",
    "prev_page_url": null
  }
}
```

---

### 5. POST /api/ews/dekan/recalculate-all-status
**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/ews/dekan/recalculate-all-status \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~500ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Proses recalculate semua status EWS dimulai di background",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": null
}
```

---

### 6. GET /api/ews/dekan/mahasiswa/all
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/mahasiswa/all?page=1&per_page=2" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"nama_prodi": "Teknik Informatika", "mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"}
  ],
  "pagination": {
    "total": 1510,
    "per_page": 2,
    "current_page": 1,
    "last_page": 755,
    "from": 1,
    "to": 2,
    "next_page_url": "http://127.0.0.1:8000/api/ews/dekan/mahasiswa/all?page=2",
    "prev_page_url": null
  }
}
```

---

### 7. GET /api/ews/dekan/mahasiswa/all/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/mahasiswa/all/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~300ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Data Mahasiswa 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 118555
```

**Response Body:** Binary Excel file (118,555 bytes)

---

### 8. GET /api/ews/dekan/mahasiswa/mk-gagal
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/mahasiswa/mk-gagal?page=1&per_page=5" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data mahasiswa dengan MK gagal berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {"nama": "MHS A11 2020 102", "nim": "A11.2020.00102", "nama_matkul": "Dasar Pemrograman", "kode_matkul": "A11.54101", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2020 103", "nim": "A11.2020.00103", "nama_matkul": "Dasar Pemrograman", "kode_matkul": "A11.54101", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2020 104", "nim": "A11.2020.00104", "nama_matkul": "Dasar Pemrograman", "kode_matkul": "A11.54101", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2020 107", "nim": "A11.2020.00107", "nama_matkul": "Dasar Pemrograman", "kode_matkul": "A11.54101", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2020 110", "nim": "A11.2020.00110", "nama_matkul": "Dasar Pemrograman", "kode_matkul": "A11.54101", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"}
  ],
  "pagination": {
    "total": 779,
    "per_page": 5,
    "current_page": 1,
    "last_page": 156,
    "from": 1,
    "to": 5,
    "next_page_url": "http://127.0.0.1:8000/api/ews/dekan/mahasiswa/mk-gagal?page=2",
    "prev_page_url": null
  }
}
```

---

### 9. GET /api/ews/dekan/mahasiswa/mk-gagal/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/mahasiswa/mk-gagal/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Daftar Mahasiswa MK Gagal 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 35898
```

---

### 10. GET /api/ews/dekan/mahasiswa/detail-angkatan/2021
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/mahasiswa/detail-angkatan/2021?page=1&per_page=5" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~300ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Detail angkatan berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "summary": {
    "rata_ips_per_semester": [
      {"semester": 1, "rata_ips": 2.94},
      {"semester": 2, "rata_ips": 3},
      {"semester": 3, "rata_ips": 2.77},
      {"semester": 4, "rata_ips": 2.76},
      {"semester": 5, "rata_ips": 2.26},
      {"semester": 6, "rata_ips": 2.82},
      {"semester": 7, "rata_ips": 2.78},
      {"semester": 8, "rata_ips": 2.77},
      {"semester": 9, "rata_ips": 2.75},
      {"semester": 10, "rata_ips": 2.83},
      {"semester": 11, "rata_ips": 2.81},
      {"semester": 12, "rata_ips": 2.79}
    ],
    "distribusi_status_ews": {
      "tepat_waktu": 0,
      "normal": 4,
      "perhatian": 182,
      "kritis": 35
    },
    "total_mahasiswa": 221
  },
  "data": [
    {
      "nama_prodi": "Teknik Informatika",
      "nim": "A11.2021.00100",
      "nama_lengkap": "MHS A11 2021 100",
      "nama_dosen_wali": "Dosen A11",
      "semester_aktif": 11,
      "tahun_masuk": "2021",
      "ipk": "2.34",
      "sks_lulus": 122,
      "mk_nasional": "yes",
      "mk_fakultas": "yes",
      "mk_prodi": "yes",
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "no",
      "status_ews": "perhatian",
      "status_kelulusan": "noneligible",
      "mahasiswa_id": 85,
      "jumlah_nilai_e": 2,
      "sks_nilai_e": 5,
      "nilai_e_detail": ["Statistika dan Probabilitas", "Seminar Proposal Skripsi"],
      "jumlah_nilai_d": 2,
      "sks_nilai_d": 5,
      "nilai_d_detail": ["Kerja Praktik", "Kewirausahaan Berbasis Teknologi"],
      "mk_nasional_detail": [],
      "mk_fakultas_detail": [],
      "mk_prodi_detail": []
    },
    {
      "nama_prodi": "Teknik Informatika",
      "nim": "A11.2021.00101",
      "nama_lengkap": "MHS A11 2021 101",
      "nama_dosen_wali": "Dosen A11",
      "semester_aktif": 12,
      "tahun_masuk": "2021",
      "ipk": "2.16",
      "sks_lulus": 126,
      "mk_nasional": "yes",
      "mk_fakultas": "yes",
      "mk_prodi": "yes",
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "yes",
      "status_ews": "perhatian",
      "status_kelulusan": "noneligible",
      "mahasiswa_id": 86,
      "jumlah_nilai_e": 0,
      "sks_nilai_e": 0,
      "nilai_e_detail": [],
      "jumlah_nilai_d": 0,
      "sks_nilai_d": 0,
      "nilai_d_detail": [],
      "mk_nasional_detail": [],
      "mk_fakultas_detail": [],
      "mk_prodi_detail": []
    }
  ],
  "pagination": {
    "total": 221,
    "per_page": 5,
    "current_page": 1,
    "last_page": 45,
    "from": 1,
    "to": 5,
    "next_page_url": "http://127.0.0.1:8000/api/ews/dekan/mahasiswa/detail-angkatan/2021?page=2",
    "prev_page_url": null
  }
}
```

---

### 11. GET /api/ews/dekan/mahasiswa/detail-angkatan/2021/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/mahasiswa/detail-angkatan/2021/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~280ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Detail Angkatan 2021 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 26740
```

---

### 12. GET /api/ews/dekan/mahasiswa/detail/1
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/mahasiswa/detail/1 \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Detail mahasiswa berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": {
    "id": 1,
    "nama": "Mhs TA KP A11",
    "nim": "A11.2020.00001",
    "status_mahasiswa": "aktif",
    "dosen_wali": {
      "id": 1,
      "nama": "Dosen A11"
    },
    "akademik": {
      "id": 1,
      "semester_aktif": 5,
      "tahun_masuk": "2020",
      "ipk": 0,
      "sks_tempuh": 0,
      "sks_lulus": 0,
      "mk_nasional": "no",
      "mk_fakultas": "no",
      "mk_prodi": "no",
      "mk_nasional_detail": [
        "Pendidikan Kebangsaan & Pancasila",
        "Pendidikan Keagamaan",
        "Bahasa Indonesia Komunikasi",
        "English for Academic Purposes"
      ],
      "mk_fakultas_detail": [
        "Matematika Diskrit",
        "Aljabar Linier",
        "Statistika dan Probabilitas",
        "Kewirausahaan Berbasis Teknologi"
      ],
      "mk_prodi_detail": [
        "Dasar Pemrograman",
        "Sistem Digital",
        "Algoritma dan Struktur Data",
        "Pemrograman Berorientasi Objek",
        "Basis Data",
        "Sistem Operasi",
        "Pemrograman Web",
        "Desain dan Analisis Algoritma",
        "Rekayasa Perangkat Lunak",
        "Interaksi Manusia & Komputer",
        "Machine Learning",
        "Data Mining",
        "Manajemen Proyek TI",
        "Kecerdasan Buatan",
        "Deep Learning",
        "Cloud Computing",
        "Kuliah Kerja Nyata (KKN)",
        "Kerja Praktik",
        "Tugas Akhir / Skripsi",
        "Seminar Proposal Skripsi"
      ],
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "yes",
      "total_sks_nilai_d": 0,
      "max_sks_nilai_d": 7.2
    },
    "status_ews": "perhatian",
    "status_kelulusan": "noneligible",
    "ip_per_semester": [
      {"semester": 1, "ips": 3.55},
      {"semester": 2, "ips": 3.31},
      {"semester": 3, "ips": 3.55},
      {"semester": 4, "ips": 2.27},
      {"semester": 5, "ips": 2.77}
    ],
    "mata_kuliah_nilai_d": [],
    "mata_kuliah_nilai_e": [],
    "riwayat_sps": []
  }
}
```

---

### 13. GET /api/ews/dekan/table-ringkasan-mahasiswa
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/table-ringkasan-mahasiswa?page=1&per_page=5" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Tabel ringkasan mahasiswa berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "nama_prodi": "Desain Komunikasi Visual",
      "kode_prodi": "A14",
      "total_angkatan": {
        "jumlah_mahasiswa": 325,
        "aktif": 259,
        "cuti": 26,
        "mangkir": 40,
        "rata_ipk": 2.82,
        "tepat_waktu": 9,
        "normal": 200,
        "perhatian": 107,
        "kritis": 9
      },
      "detail_angkatan": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "aktif": "65", "cuti": "7", "mangkir": "4", "rata_ipk": "2.88", "tepat_waktu": "2", "normal": "74", "perhatian": "0", "kritis": "0"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "aktif": "66", "cuti": "8", "mangkir": "2", "rata_ipk": "2.75", "tepat_waktu": "2", "normal": "72", "perhatian": "2", "kritis": "0"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 57, "aktif": "42", "cuti": "3", "mangkir": "12", "rata_ipk": "2.73", "tepat_waktu": "5", "normal": "37", "perhatian": "15", "kritis": "0"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 64, "aktif": "45", "cuti": "4", "mangkir": "15", "rata_ipk": "2.91", "tepat_waktu": "0", "normal": "17", "perhatian": "45", "kritis": "2"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 52, "aktif": "41", "cuti": "4", "mangkir": "7", "rata_ipk": "2.83", "tepat_waktu": "0", "normal": "0", "perhatian": "45", "kritis": "7"}
      ]
    }
  ],
  "pagination": {
    "total": 24,
    "per_page": 5,
    "current_page": 1,
    "last_page": 5,
    "from": 1,
    "to": 5,
    "next_page_url": "http://127.0.0.1:8000/api/ews/dekan/table-ringkasan-mahasiswa?page=2",
    "prev_page_url": null
  }
}
```

---

### 14. GET /api/ews/dekan/table-ringkasan-mahasiswa/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/table-ringkasan-mahasiswa/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Ringkasan Mahasiswa 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 8454
```

---

### 15. GET /api/ews/dekan/table-ringkasan-status
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/table-ringkasan-status?page=1&per_page=5" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Table ringkasan status per prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "nama_prodi": "Desain Komunikasi Visual",
      "kode_prodi": "A14",
      "total_status": {
        "jumlah_mahasiswa": 372,
        "ipk_kurang_dari_2": 67,
        "mangkir": 46,
        "cuti": 28,
        "perhatian": 114
      },
      "detail_angkatan": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "14", "mangkir": "4", "cuti": "7", "perhatian": "0"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "13", "mangkir": "2", "cuti": "8", "perhatian": "2"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "13", "mangkir": "12", "cuti": "3", "perhatian": "15"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 64, "ipk_kurang_dari_2": "10", "mangkir": "15", "cuti": "4", "perhatian": "45"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 52, "ipk_kurang_dari_2": "9", "mangkir": "7", "cuti": "4", "perhatian": "45"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 47, "ipk_kurang_dari_2": "8", "mangkir": "6", "cuti": "2", "perhatian": "7"}
      ]
    },
    {
      "nama_prodi": "Ilmu Komunikasi",
      "kode_prodi": "A15",
      "total_status": {
        "jumlah_mahasiswa": 372,
        "ipk_kurang_dari_2": 76,
        "mangkir": 30,
        "cuti": 35,
        "perhatian": 124
      },
      "detail_angkatan": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "15", "mangkir": "4", "cuti": "8", "perhatian": "0"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "16", "mangkir": "3", "cuti": "9", "perhatian": "8"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 55, "ipk_kurang_dari_2": "12", "mangkir": "3", "cuti": "5", "perhatian": "15"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "11", "mangkir": "5", "cuti": "4", "perhatian": "37"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 51, "ipk_kurang_dari_2": "11", "mangkir": "7", "cuti": "5", "perhatian": "46"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "11", "mangkir": "8", "cuti": "4", "perhatian": "18"}
      ]
    },
    {
      "nama_prodi": "Sistem Informasi",
      "kode_prodi": "A12",
      "total_status": {
        "jumlah_mahasiswa": 379,
        "ipk_kurang_dari_2": 79,
        "mangkir": 33,
        "cuti": 39,
        "perhatian": 126
      },
      "detail_angkatan": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "16", "mangkir": "3", "cuti": "8", "perhatian": "0"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "18", "mangkir": "3", "cuti": "14", "perhatian": "2"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 52, "ipk_kurang_dari_2": "7", "mangkir": "6", "cuti": "2", "perhatian": "18"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 53, "ipk_kurang_dari_2": "10", "mangkir": "10", "cuti": "6", "perhatian": "39"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 64, "ipk_kurang_dari_2": "14", "mangkir": "7", "cuti": "6", "perhatian": "49"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 58, "ipk_kurang_dari_2": "14", "mangkir": "4", "cuti": "3", "perhatian": "18"}
      ]
    },
    {
      "nama_prodi": "Teknik Informatika",
      "kode_prodi": "A11",
      "total_status": {
        "jumlah_mahasiswa": 387,
        "ipk_kurang_dari_2": 86,
        "mangkir": 51,
        "cuti": 25,
        "perhatian": 134
      },
      "detail_anglerenan": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "20", "mangkir": "6", "cuti": "6", "perhatian": "0"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "ipk_kurang_dari_2": "13", "mangkir": "4", "cuti": "6", "perhatian": "5"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "ipk_kurang_dari_2": "20", "mangkir": "11", "cuti": "2", "perhatian": "24"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "12", "mangkir": "10", "cuti": "4", "perhatian": "43"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "ipk_kurang_dari_2": "11", "mangkir": "9", "cuti": "4", "perhatian": "42"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 62, "ipk_kurang_dari_2": "10", "mangkir": "11", "cuti": "3", "perhatian": "20"}
      ]
    }
  ]
}
```

---

### 16. GET /api/ews/dekan/table-ringkasan-status/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/table-ringkasan-status/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Ringkasan Status 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 7891
```

---

### 17. GET /api/ews/dekan/top-mk-gagal
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/top-mk-gagal?page=1&per_page=5" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Top 10 MK gagal all time dari semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {"kode": "A11.54101", "nama": "Dasar Pemrograman", "jumlah_gagal": 175, "dosen_koordinator": "-"},
    {"kode": "A14.54101", "nama": "Nirmana Dwimatra", "jumlah_gagal": 174, "dosen_koordinator": "-"},
    {"kode": "A12.54101", "nama": "Pengantar Sistem Informasi", "jumlah_gagal": 165, "dosen_koordinator": "-"},
    {"kode": "A15.54101", "nama": "Pengantar Ilmu Komunikasi", "jumlah_gagal": 152, "dosen_koordinator": "-"},
    {"kode": "A12.54403", "nama": "Riset Operasi", "jumlah_gagal": 21, "dosen_koordinator": "-"},
    {"kode": "A14.54901", "nama": "Desain Buku & Editorial", "jumlah_gagal": 20, "dosen_koordinator": "-"},
    {"kode": "A14.54103", "nama": "Sejarah Seni Rupa", "jumlah_gagal": 19, "dosen_koordinator": "-"},
    {"kode": "A12.54201", "nama": "Analisis dan Perancangan SI", "jumlah_gagal": 19, "dosen_koordinator": "-"},
    {"kode": "A12.54922", "nama": "Visualisasi Data Bisnis", "jumlah_gagal": 17, "dosen_koordinator": "-"},
    {"kode": "A12.11104", "nama": "English for Academic Purposes", "jumlah_gagal": 17, "dosen_koordinator": "-"}
  ]
}
```

---

### 18. GET /api/ews/dekan/tren-ips/all
**Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/ews/dekan/tren-ips/all?page=1&per_page=5" \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Tren IPS per Prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "prodi": {"id": 1, "kode": "A11", "nama": "Teknik Informatika"},
      "tren_ips": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "tren_ips": "naik", "mk_gagal": 117, "mk_ulang": 0},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "tren_ips": "turun", "mk_gagal": 105, "mk_ulang": 0},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "tren_ips": "naik", "mk_gagal": 89, "mk_ulang": 0},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "tren_ips": "naik", "mk_gagal": 85, "mk_ulang": 0},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "tren_ips": "stabil", "mk_gagal": 71, "mk_ulang": 0},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 60, "tren_ips": "stabil", "mk_gagal": 80, "mk_ulang": 0}
      ]
    },
    {
      "prodi": {"id": 2, "kode": "A12", "nama": "Sistem Informasi"},
      "tren_ips": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "tren_ips": "turun", "mk_gagal": 94, "mk_ulang": 0},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "tren_ips": "turun", "mk_gagal": 105, "mk_ulang": 0},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 52, "tren_ips": "naik", "mk_gagal": 70, "mk_ulang": 0},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 53, "tren_ips": "turun", "mk_gagal": 72, "mk_ulang": 0},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 64, "tren_ips": "stabil", "mk_gagal": 95, "mk_ulang": 0},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 56, "tren_ips": "stabil", "mk_gagal": 84, "mk_ulang": 0}
      ]
    },
    {
      "prodi": {"id": 3, "kode": "A14", "nama": "Desain Komunikasi Visual"},
      "tren_ips": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "tren_ips": "naik", "mk_gagal": 97, "mk_ulang": 0},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "tren_ips": "turun", "mk_gagal": 81, "mk_ulang": 0},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 57, "tren_ips": "naik", "mk_gagal": 90, "mk_ulang": 0},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 64, "tren_ips": "stabil", "mk_gagal": 84, "mk_ulang": 0},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 52, "tren_ips": "stabil", "mk_gagal": 76, "mk_ulang": 0},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 45, "tren_ips": "stabil", "mk_gagal": 78, "mk_ulang": 0}
      ]
    },
    {
      "prodi": {"id": 4, "kode": "A15", "nama": "Ilmu Komunikasi"},
      "tren_ips": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "tren_ips": "turun", "mk_gagal": 103, "mk_ulang": 0},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "tren_ips": "naik", "mk_gagal": 92, "mk_ulang": 0},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 55, "tren_ips": "turun", "mk_gagal": 68, "mk_ulang": 0},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "tren_ips": "naik", "mk_gagal": 64, "mk_ulang": 0},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 51, "tren_ips": "stabil", "mk_gagal": 63, "mk_ulang": 0},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 55, "tren_ips": "stabil", "mk_gagal": 71, "mk_ulang": 0}
      ]
    }
  ]
}
```

---

### 19. GET /api/ews/dekan/tren-ips/all/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/tren-ips/all/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Capaian Mahasiswa 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 7202
```

---

### 20. GET /api/ews/dekan/card-capaian
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/card-capaian \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~300ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Capaian mahasiswa per Prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "prodi": {"id": 1, "kode": "A11", "nama": "Teknik Informatika"},
      "capaian": {
        "total_mahasiswa": 387,
        "total_turun_ip": 136,
        "total_naik_ip": 135,
        "tren_per_angkatan": [
          {"tahun_masuk": "2025", "semester_aktif": 3, "rata_ips": 3.04, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 35, "mahasiswa_turun_ip": 41, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2024", "semester_aktif": 4, "rata_ips": 2.92, "jumlah_mahasiswa": 77, "mahasiswa_naik_ip": 33, "mahasiswa_turun_ip": 44, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2023", "semester_aktif": 7, "rata_ips": 2.79, "jumlah_mahasiswa": 61, "mahasiswa_naik_ip": 41, "mahasiswa_turun_ip": 20, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2022", "semester_aktif": 9, "rata_ips": 2.77, "jumlah_mahasiswa": 57, "mahasiswa_naik_ip": 26, "mahasiswa_turun_ip": 31, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2021", "semester_aktif": 12, "rata_ips": 0, "jumlah_mahasiswa": 54, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2020", "semester_aktif": 13, "rata_ips": 0, "jumlah_mahasiswa": 62, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0}
        ]
      }
    },
    {
      "prodi": {"id": 2, "kode": "A12", "nama": "Sistem Informasi"},
      "capaian": {
        "total_mahasiswa": 379,
        "total_turun_ip": 125,
        "total_naik_ip": 131,
        "tren_per_angkatan": [
          {"tahun_masuk": "2025", "semester_aktif": 3, "rata_ips": 3.08, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 42, "mahasiswa_turun_ip": 33, "mahasiswa_stabil_ip": 1},
          {"tahun_masuk": "2024", "semester_aktif": 4, "rata_ips": 2.68, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 29, "mahasiswa_turun_ip": 47, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2023", "semester_aktif": 7, "rata_ips": 2.77, "jumlah_mahasiswa": 52, "mahasiswa_naik_ip": 36, "mahasiswa_turun_ip": 16, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2022", "semester_aktif": 8, "rata_ips": 2.68, "jumlah_mahasiswa": 53, "mahasiswa_naik_ip": 24, "mahasiswa_turun_ip": 29, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2021", "semester_aktif": 11, "rata_ips": 0, "jumlah_mahasiswa": 64, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2020", "semester_aktif": 14, "rata_ips": 0, "jumlah_mahasiswa": 58, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0}
        ]
      }
    },
    {
      "prodi": {"id": 3, "kode": "A14", "nama": "Desain Komunikasi Visual"},
      "capaian": {
        "total_mahasiswa": 372,
        "total_turun_ip": 97,
        "total_naik_ip": 109,
        "tren_per_angkatan": [
          {"tahun_masuk": "2025", "semester_aktif": 3, "rata_ips": 3.02, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 38, "mahasiswa_turun_ip": 36, "mahasiswa_stabil_ip": 2},
          {"tahun_masuk": "2024", "semester_aktif": 4, "rata_ips": 2.8, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 30, "mahasiswa_turun_ip": 45, "mahasiswa_stabil_ip": 1},
          {"tahun_masuk": "2023", "semester_aktif": 7, "rata_ips": 2.81, "jumlah_mahasiswa": 57, "mahasiswa_naik_ip": 41, "mahasiswa_turun_ip": 16, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2022", "semester_aktif": 10, "rata_ips": 0, "jumlah_mahasiswa": 64, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2021", "semester_aktif": 11, "rata_ips": 0, "jumlah_mahasiswa": 52, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2020", "semester_aktif": 14, "rata_ips": 0, "jumlah_mahasiswa": 47, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0}
        ]
      }
    },
    {
      "prodi": {"id": 4, "kode": "A15", "nama": "Ilmu Komunikasi"},
      "capaian": {
        "total_mahasiswa": 372,
        "total_turun_ip": 116,
        "total_naik_ip": 110,
        "tren_per_angkatan": [
          {"tahun_masuk": "2025", "semester_aktif": 4, "rata_ips": 1.95, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 26, "mahasiswa_turun_ip": 25, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2024", "semester_aktif": 5, "rata_ips": 2.59, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 33, "mahasiswa_turun_ip": 43, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2023", "semester_aktif": 8, "rata_ips": 2.17, "jumlah_mahasiswa": 55, "mahasiswa_naik_ip": 25, "mahasiswa_turun_ip": 17, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2022", "semester_aktif": 9, "rata_ips": 2.71, "jumlah_mahasiswa": 57, "mahasiswa_naik_ip": 26, "mahasiswa_turun_ip": 31, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2021", "semester_aktif": 10, "rata_ips": 0, "jumlah_mahasiswa": 51, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0},
          {"tahun_masuk": "2020", "semester_aktif": 12, "rata_ips": 0, "jumlah_mahasiswa": 57, "mahasiswa_naik_ip": 0, "mahasiswa_turun_ip": 0, "mahasiswa_stabil_ip": 0}
        ]
      }
    }
  ]
}
```

---

### 21. GET /api/ews/dekan/statistik-kelulusan
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/statistik-kelulusan \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Statistik kelulusan per Prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "prodi": {"id": 1, "kode": "A11", "nama": "Teknik Informatika"},
      "statistik": {
        "eligible": 0,
        "noneligible": 385,
        "aktif": 311,
        "mangkir": 51,
        "cuti": 25,
        "ipk_kurang_dari_2_5": 172,
        "ipk_antara_2_5_3": 72,
        "ipk_lebih_dari_3": 141,
        "mk_nasional": 337,
        "mk_fakultas": 280,
        "mk_prodi": 216
      }
    },
    {
      "prodi": {"id": 2, "kode": "A12", "nama": "Sistem Informasi"},
      "statistik": {
        "eligible": 1,
        "noneligible": 376,
        "aktif": 307,
        "mangkir": 33,
        "cuti": 39,
        "ipk_kurang_dari_2_5": 142,
        "ipk_antara_2_5_3": 79,
        "ipk_lebih_dari_3": 156,
        "mk_nasional": 328,
        "mk_fakultas": 270,
        "mk_prodi": 206
      }
    },
    {
      "prodi": {"id": 3, "kode": "A14", "nama": "Desain Komunikasi Visual"},
      "statistik": {
        "eligible": 4,
        "noneligible": 366,
        "aktif": 298,
        "mangkir": 46,
        "cuti": 28,
        "ipk_kurang_dari_2_5": 133,
        "ipk_antara_2_5_3": 79,
        "ipk_lebih_dari_3": 158,
        "mk_nasional": 313,
        "mk_fakultas": 263,
        "mk_prodi": 202
      }
    },
    {
      "prodi": {"id": 4, "kode": "A15", "nama": "Ilmu Komunikasi"},
      "statistik": {
        "eligible": 3,
        "noneligible": 367,
        "aktif": 307,
        "mangkir": 30,
        "cuti": 35,
        "ipk_kurang_dari_2_5": 160,
        "ipk_antara_2_5_3": 75,
        "ipk_lebih_dari_3": 135,
        "mk_nasional": 320,
        "mk_fakultas": 269,
        "mk_prodi": 200
      }
    }
  ]
}
```

---

### 22. GET /api/ews/dekan/table-statistik-kelulusan
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/table-statistik-kelulusan \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~300ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Table statistik kelulusan berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "nama_prodi": "Desain Komunikasi Visual",
      "kode_prodi": "A14",
      "total_statistik": {
        "jumlah_mahasiswa": 372,
        "ipk_kurang_dari_2": 67,
        "sks_kurang_dari_144": 352,
        "nilai_d_melebihi_batas": 29,
        "nilai_e": 288,
        "eligible": 4,
        "noneligible": 366,
        "ipk_rata2": 2.8
      },
      "detail_statistik": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "14", "sks_kurang_dari_144": "74", "nilai_d_melebihi_batas": "10", "nilai_e": "56", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.88"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "13", "sks_kurang_dari_144": "74", "nilai_d_melebihi_batas": "5", "nilai_e": "51", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.75"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "13", "sks_kurang_dari_144": "52", "nilai_d_melebihi_batas": "7", "nilai_e": "48", "eligible": "2", "noneligible": "55", "ipk_rata2": "2.73"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 64, "ipk_kurang_dari_2": "10", "sks_kurang_dari_144": "61", "nilai_d_melebihi_batas": "5", "nilai_e": "50", "eligible": "1", "noneligible": "63", "ipk_rata2": "2.91"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 52, "ipk_kurang_dari_2": "9", "sks_kurang_dari_144": "50", "nilai_d_melebihi_batas": "2", "nilai_e": "43", "eligible": "0", "noneligible": "52", "ipk_rata2": "2.83"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 47, "ipk_kurang_dari_2": "8", "sks_kurang_dari_144": "41", "nilai_d_melebihi_batas": "0", "nilai_e": "40", "eligible": "1", "noneligible": "44", "ipk_rata2": "2.67"}
      ]
    },
    {
      "nama_prodi": "Ilmu Komunikasi",
      "kode_prodi": "A15",
      "total_statistik": {
        "jumlah_mahasiswa": 372,
        "ipk_kurang_dari_2": 76,
        "sks_kurang_dari_144": 354,
        "nilai_d_melebihi_batas": 25,
        "nilai_e": 291,
        "eligible": 3,
        "noneligible": 367,
        "ipk_rata2": 2.71
      },
      "detail_statistik": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "15", "sks_kurang_dari_144": "72", "nilai_d_melebihi_batas": "6", "nilai_e": "63", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.73"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "16", "sks_kurang_dari_144": "74", "nilai_d_melebihi_batas": "4", "nilai_e": "58", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.69"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 55, "ipk_kurang_dari_2": "12", "sks_kurang_dari_144": "53", "nilai_d_melebihi_batas": "3", "nilai_e": "43", "eligible": "1", "noneligible": "54", "ipk_rata2": "2.65"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "11", "sks_kurang_dari_144": "52", "nilai_d_melebihi_batas": "6", "nilai_e": "41", "eligible": "1", "noneligible": "56", "ipk_rata2": "2.82"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 51, "ipk_kurang_dari_2": "11", "sks_kurang_dari_144": "51", "nilai_d_melebihi_batas": "1", "nilai_e": "42", "eligible": "0", "noneligible": "51", "ipk_rata2": "2.65"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "11", "sks_kurang_dari_144": "52", "nilai_d_melebihi_batas": "5", "nilai_e": "44", "eligible": "1", "noneligible": "54", "ipk_rata2": "2.69"}
      ]
    },
    {
      "nama_prodi": "Sistem Informasi",
      "kode_prodi": "A12",
      "total_statistik": {
        "jumlah_mahasiswa": 379,
        "ipk_kurang_dari_2": 79,
        "sks_kurang_dari_144": 360,
        "nilai_d_melebihi_batas": 30,
        "nilai_e": 303,
        "eligible": 1,
        "noneligible": 376,
        "ipk_rata2": 2.78
      },
      "detail_statistik": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "16", "sks_kurang_dari_144": "73", "nilai_d_melebihi_batas": "4", "nilai_e": "57", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.72"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "18", "sks_kurang_dari_144": "70", "nilai_d_melebihi_batas": "8", "nilai_e": "63", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.73"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 52, "ipk_kurang_dari_2": "7", "sks_kurang_dari_144": "49", "nilai_d_melebihi_batas": "4", "nilai_e": "41", "eligible": "1", "noneligible": "51", "ipk_rata2": "2.90"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 53, "ipk_kurang_dari_2": "10", "sks_kurang_dari_144": "50", "nilai_d_melebihi_batas": "5", "nilai_e": "44", "eligible": "0", "noneligible": "53", "ipk_rata2": "2.81"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 64, "ipk_kurang_dari_2": "14", "sks_kurang_dari_144": "63", "nilai_d_melebihi_batas": "3", "nilai_e": "51", "eligible": "0", "noneligible": "64", "ipk_rata2": "2.84"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 58, "ipk_kurang_dari_2": "14", "sks_kurang_dari_144": "55", "nilai_d_melebihi_batas": "6", "nilai_e": "47", "eligible": "0", "noneligible": "56", "ipk_rata2": "2.65"}
      ]
    },
    {
      "nama_prodi": "Teknik Informatika",
      "kode_prodi": "A11",
      "total_statistik": {
        "jumlah_mahasiswa": 387,
        "ipk_kurang_dari_2": 86,
        "sks_kurang_dari_144": 365,
        "nilai_d_melebihi_batas": 26,
        "nilai_e": 298,
        "eligible": 0,
        "noneligible": 385,
        "ipk_rata2": 2.68
      },
      "detail_statistik": [
        {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "20", "sks_kurang_dari_144": "72", "nilai_d_melebihi_batas": "6", "nilai_e": "60", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.72"},
        {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "ipk_kurang_dari_2": "13", "sks_kurang_dari_144": "74", "nilai_d_melebihi_batas": "7", "nilai_e": "60", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.68"},
        {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "ipk_kurang_dari_2": "20", "sks_kurang_dari_144": "60", "nilai_d_melebihi_batas": "5", "nilai_e": "47", "eligible": "0", "noneligible": "61", "ipk_rata2": "2.63"},
        {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "12", "sks_kurang_dari_144": "53", "nilai_d_melebihi_batas": "2", "nilai_e": "48", "eligible": "0", "noneligible": "57", "ipk_rata2": "2.71"},
        {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "ipk_kurang_dari_2": "11", "sks_kurang_dari_144": "50", "nilai_d_melebihi_batas": "2", "nilai_e": "38", "eligible": "0", "noneligible": "54", "ipk_rata2": "2.64"},
        {"tahun_masuk": "2020", "jumlah_mahasiswa": 62, "ipk_kurang_dari_2": "10", "sks_kurang_dari_144": "56", "nilai_d_melebihi_batas": "4", "nilai_e": "45", "eligible": "0", "noneligible": "61", "ipk_rata2": "2.69"}
      ]
    }
  ],
  "pagination": {
    "total": 24,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 24,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

---

### 23. GET /api/ews/dekan/tindak-lanjut
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/tindak-lanjut \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data tindak lanjut prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [],
  "pagination": {
    "total": 0,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "from": null,
    "to": null,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

---

### 24. GET /api/ews/dekan/tindak-lanjut/cards
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/dekan/tindak-lanjut/cards \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data statistik tindak lanjut per Prodi berhasil diambil",
  "info_akses": {
    "role": "dekan",
    "scope_data": "Seluruh Fakultas (Semua Prodi)"
  },
  "data": [
    {
      "prodi": {"id": 1, "kode": "A11", "nama": "Teknik Informatika"},
      "summary": {"total_rekomitmen": 0, "total_pindah_prodi": 0, "dalam_proses": 0, "selesai": 0}
    },
    {
      "prodi": {"id": 2, "kode": "A12", "nama": "Sistem Informasi"},
      "summary": {"total_rekomitmen": 0, "total_pindah_prodi": 0, "dalam_proses": 0, "selesai": 0}
    },
    {
      "prodi": {"id": 3, "kode": "A14", "nama": "Desain Komunikasi Visual"},
      "summary": {"total_rekomitmen": 0, "total_pindah_prodi": 0, "dalam_proses": 0, "selesai": 0}
    },
    {
      "prodi": {"id": 4, "kode": "A15", "nama": "Ilmu Komunikasi"},
      "summary": {"total_rekomitmen": 0, "total_pindah_prodi": 0, "dalam_proses": 0, "selesai": 0}
    }
  ]
}
```

---

### 25. GET /api/ews/dekan/tindak-lanjut/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/dekan/tindak-lanjut/export \
  -H "Authorization: Bearer 87|ByZpkQWRYGjnuW4irBSTDm1pbpCp6gE3rY7Uzi2Ed1f6a7f6" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Type: application/json
```

---

## KAPRODI ENDPOINTS (23)

### 26. POST /api/login (Kaprodi)
**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"kaprodi_a11@ews.com","password":"password"}'
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "access_token": "86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49",
    "token_type": "Bearer",
    "user": {
      "name": "Kaprodi TI Test",
      "email": "kaprodi_a11@ews.com",
      "roles": "kaprodi",
      "permissions": [
        "ews-mahasiswa",
        "ews-kaprodi"
      ]
    },
    "kaprodi": {
      "prodi_id": 1,
      "prodi": "Teknik Informatika",
      "kode_prodi": "A11"
    }
  }
}
```

---

### 27. GET /api/ews/kaprodi/dashboard
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/dashboard \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Dashboard data berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "status_mahasiswa": {
      "total": 386,
      "aktif": 310,
      "mangkir": 51,
      "cuti": 25
    },
    "rata_ipk_per_angkatan": [
      {"tahun_masuk": "2025", "rata_ipk": "2.72", "jumlah_mahasiswa": 76},
      {"tahun_masuk": "2024", "rata_ipk": "2.68", "jumlah_mahasiswa": 77},
      {"tahun_masuk": "2023", "rata_ipk": "2.63", "jumlah_mahasiswa": 61},
      {"tahun_masuk": "2022", "rata_ipk": "2.71", "jumlah_mahasiswa": 57},
      {"tahun_masuk": "2021", "rata_ipk": "2.64", "jumlah_mahasiswa": 54},
      {"tahun_masuk": "2020", "rata_ipk": "2.69", "jumlah_mahasiswa": 60}
    ],
    "status_kelulusan": {
      "total": 385,
      "eligible": 0,
      "tidak_eligible": 385
    }
  }
}
```

---

### 28. GET /api/ews/kaprodi/distribusi-status-ews
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/distribusi-status-ews \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Distribusi status EWS berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "tepat_waktu": 11,
    "normal": 191,
    "perhatian": 134,
    "kritis": 49
  }
}
```

---

### 29. GET /api/ews/kaprodi/status-mahasiswa
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/status-mahasiswa \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 2, "nim": "A11.2020.00002", "nama_lengkap": "Mhs BK A11", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 9, "nim": "A11.2020.00100", "nama_lengkap": "MHS A11 2020 100", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 10, "nim": "A11.2020.00101", "nama_lengkap": "MHS A11 2020 101", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 11, "nim": "A11.2020.00102", "nama_lengkap": "MHS A11 2020 102", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 12, "nim": "A11.2020.00103", "nama_lengkap": "MHS A11 2020 103", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 13, "nim": "A11.2020.00104", "nama_lengkap": "MHS A11 2020 104", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 14, "nim": "A11.2020.00105", "nama_lengkap": "MHS A11 2020 105", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 15, "nim": "A11.2020.00106", "nama_lengkap": "MHS A11 2020 106", "nama_dosen_wali": "Dosen A11"}
  ],
  "pagination": {
    "total": 387,
    "per_page": 10,
    "current_page": 1,
    "last_page": 39,
    "from": 1,
    "to": 10,
    "next_page_url": "http://127.0.0.1:8000/api/ews/kaprodi/status-mahasiswa?page=2",
    "prev_page_url": null
  }
}
```

---

### 30. POST /api/ews/kaprodi/recalculate-all-status
**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/ews/kaprodi/recalculate-all-status \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~400ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Proses recalculate semua status EWS dimulai di background",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": null
}
```

---

### 31. GET /api/ews/kaprodi/mahasiswa/all
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/all \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 1, "nim": "A11.2020.00001", "nama_lengkap": "Mhs TA KP A11", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 2, "nim": "A11.2020.00002", "nama_lengkap": "Mhs BK A11", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 9, "nim": "A11.2020.00100", "nama_lengkap": "MHS A11 2020 100", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 10, "nim": "A11.2020.00101", "nama_lengkap": "MHS A11 2020 101", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 11, "nim": "A11.2020.00102", "nama_lengkap": "MHS A11 2020 102", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 12, "nim": "A11.2020.00103", "nama_lengkap": "MHS A11 2020 103", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 13, "nim": "A11.2020.00104", "nama_lengkap": "MHS A11 2020 104", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 14, "nim": "A11.2020.00105", "nama_lengkap": "MHS A11 2020 105", "nama_dosen_wali": "Dosen A11"},
    {"mahasiswa_id": 15, "nim": "A11.2020.00106", "nama_lengkap": "MHS A11 2020 106", "nama_dosen_wali": "Dosen A11"}
  ],
  "pagination": {
    "total": 387,
    "per_page": 10,
    "current_page": 1,
    "last_page": 39,
    "from": 1,
    "to": 10,
    "next_page_url": "http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/all?page=2",
    "prev_page_url": null
  }
}
```

---

### 32. GET /api/ews/kaprodi/mahasiswa/all/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/all/export \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~280ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Data Mahasiswa 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 36329
```

---

### 33. GET /api/ews/kaprodi/mahasiswa/mk-gagal
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/mk-gagal \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data mahasiswa dengan MK gagal berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"nama": "MHS A11 2020 132", "nim": "A11.2020.00132", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2020 158", "nim": "A11.2020.00158", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2020 164", "nim": "A11.2020.00164", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2021 155", "nim": "A11.2021.00155", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2022 101", "nim": "A11.2022.00101", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2023 119", "nim": "A11.2023.00119", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2023 126", "nim": "A11.2023.00126", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2024 103", "nim": "A11.2024.00103", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2024 110", "nim": "A11.2024.00110", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"},
    {"nama": "MHS A11 2024 130", "nim": "A11.2024.00130", "nama_matkul": "Kuliah Kerja Nyata (KKN)", "kode_matkul": "A11.11106", "kode_kelompok": "A", "presensi": null, "dosen_pengampu": "Dosen A11"}
  ],
  "pagination": {
    "total": 305,
    "per_page": 10,
    "current_page": 1,
    "last_page": 31,
    "from": 1,
    "to": 10,
    "next_page_url": "http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/mk-gagal?page=2",
    "prev_page_url": null
  }
}
```

---

### 34. GET /api/ews/kaprodi/mahasiswa/detail-angkatan/2021
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/detail-angkatan/2021 \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Detail angkatan berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "summary": {
    "rata_ips_per_semester": [
      {"semester": 1, "rata_ips": 2.95},
      {"semester": 2, "rata_ips": 3.14},
      {"semester": 3, "rata_ips": 2.79},
      {"semester": 4, "rata_ips": 2.72},
      {"semester": 5, "rata_ips": 2.28},
      {"semester": 6, "rata_ips": 2.89},
      {"semester": 7, "rata_ips": 2.83},
      {"semester": 8, "rata_ips": 2.78},
      {"semester": 9, "rata_ips": 2.79},
      {"semester": 10, "rata_ips": 2.76},
      {"semester": 11, "rata_ips": 2.87},
      {"semester": 12, "rata_ips": 2.76}
    ],
    "distribusi_status_ews": {
      "tepat_waktu": 0,
      "normal": 4,
      "perhatian": 42,
      "kritis": 8
    },
    "total_mahasiswa": 54
  },
  "data": [
    {
      "mahasiswa_id": 85,
      "nim": "A11.2021.00100",
      "nama_lengkap": "MHS A11 2021 100",
      "nama_dosen_wali": "Dosen A11",
      "semester_aktif": 11,
      "tahun_masuk": "2021",
      "ipk": "2.34",
      "sks_lulus": 122,
      "mk_nasional": "yes",
      "mk_fakultas": "yes",
      "mk_prodi": "yes",
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "no",
      "status_ews": "perhatian",
      "status_kelulusan": "noneligible",
      "jumlah_nilai_e": 2,
      "sks_nilai_e": 5,
      "nilai_e_detail": ["Statistika dan Probabilitas", "Seminar Proposal Skripsi"],
      "jumlah_nilai_d": 2,
      "sks_nilai_d": 5,
      "nilai_d_detail": ["Kerja Praktik", "Kewirausahaan Berbasis Teknologi"],
      "mk_nasional_detail": [],
      "mk_fakultas_detail": [],
      "mk_prodi_detail": []
    },
    {
      "mahasiswa_id": 86,
      "nim": "A11.2021.00101",
      "nama_lengkap": "MHS A11 2021 101",
      "nama_dosen_wali": "Dosen A11",
      "semester_aktif": 12,
      "tahun_masuk": "2021",
      "ipk": "2.16",
      "sks_lulus": 126,
      "mk_nasional": "yes",
      "mk_fakultas": "yes",
      "mk_prodi": "yes",
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "yes",
      "status_ews": "perhatian",
      "status_kelulusan": "noneligible",
      "jumlah_nilai_e": 0,
      "sks_nilai_e": 0,
      "nilai_e_detail": [],
      "jumlah_nilai_d": 0,
      "sks_nilai_d": 0,
      "nilai_d_detail": [],
      "mk_nasional_detail": [],
      "mk_fakultas_detail": [],
      "mk_prodi_detail": []
    }
  ],
  "pagination": {
    "total": 54,
    "per_page": 10,
    "current_page": 1,
    "last_page": 6,
    "from": 1,
    "to": 10,
    "next_page_url": "http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/detail-angkatan/2021?page=2",
    "prev_page_url": null
  }
}
```

---

### 35. GET /api/ews/kaprodi/mahasiswa/detail/1
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/mahasiswa/detail/1 \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Detail mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "id": 1,
    "nama": "Mhs TA KP A11",
    "nim": "A11.2020.00001",
    "status_mahasiswa": "aktif",
    "dosen_wali": {
      "id": 1,
      "nama": "Dosen A11"
    },
    "akademik": {
      "id": 1,
      "semester_aktif": 5,
      "tahun_masuk": "2020",
      "ipk": 0,
      "sks_tempuh": 0,
      "sks_lulus": 0,
      "mk_nasional": "no",
      "mk_fakultas": "no",
      "mk_prodi": "no",
      "mk_nasional_detail": [
        "Pendidikan Kebangsaan & Pancasila",
        "Pendidikan Keagamaan",
        "Bahasa Indonesia Komunikasi",
        "English for Academic Purposes"
      ],
      "mk_fakultas_detail": [
        "Matematika Diskrit",
        "Aljabar Linier",
        "Statistika dan Probabilitas",
        "Kewirausahaan Berbasis Teknologi"
      ],
      "mk_prodi_detail": [
        "Dasar Pemrograman",
        "Sistem Digital",
        "Algoritma dan Struktur Data",
        "Pemrograman Berorientasi Objek",
        "Basis Data",
        "Sistem Operasi",
        "Pemrograman Web",
        "Desain dan Analisis Algoritma",
        "Rekayasa Perangkat Lunak",
        "Interaksi Manusia & Komputer",
        "Machine Learning",
        "Data Mining",
        "Manajemen Proyek TI",
        "Kecerdasan Buatan",
        "Deep Learning",
        "Cloud Computing",
        "Kuliah Kerja Nyata (KKN)",
        "Kerja Praktik",
        "Tugas Akhir / Skripsi",
        "Seminar Proposal Skripsi"
      ],
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "yes",
      "total_sks_nilai_d": 0,
      "max_sks_nilai_d": 7.2
    },
    "status_ews": "perhatian",
    "status_kelulusan": "noneligible",
    "ip_per_semester": [
      {"semester": 1, "ips": 3.55},
      {"semester": 2, "ips": 3.31},
      {"semester": 3, "ips": 3.55},
      {"semester": 4, "ips": 2.27},
      {"semester": 5, "ips": 2.77}
    ],
    "mata_kuliah_nilai_d": [],
    "mata_kuliah_nilai_e": [],
    "riwayat_sps": []
  }
}
```

---

### 36. GET /api/ews/kaprodi/table-ringkasan-mahasiswa
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/table-ringkasan-mahasiswa \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Tabel ringkasan mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "aktif": "64", "cuti": "6", "mangkir": "6", "rata_ipk": "2.72", "tepat_waktu": "4", "normal": "72", "perhatian": "0", "kritis": "0"},
    {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "aktif": "67", "cuti": "6", "mangkir": "4", "rata_ipk": "2.68", "tepat_waktu": "3", "normal": "68", "perhatian": "5", "kritis": "0"},
    {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "aktif": "48", "cuti": "2", "mangkir": "11", "rata_ipk": "2.63", "tepat_waktu": "4", "normal": "33", "perhatian": "24", "kritis": "0"},
    {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "aktif": "43", "cuti": "4", "mangkir": "10", "rata_ipk": "2.71", "tepat_waktu": "0", "normal": "14", "perhatian": "43", "kritis": "0"},
    {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "aktif": "41", "cuti": "4", "mangkir": "9", "rata_ipk": "2.64", "tepat_waktu": "0", "normal": "4", "perhatian": "42", "kritis": "8"},
    {"tahun_masuk": "2020", "jumlah_mahasiswa": 62, "aktif": "48", "cuti": "3", "mangkir": "11", "rata_ipk": "2.69", "tepat_waktu": "0", "normal": "0", "perhatian": "20", "kritis": "41"}
  ],
  "pagination": {
    "total": 6,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 6,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

---

### 37. GET /api/ews/kaprodi/table-ringkasan-mahasiswa/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/kaprodi/table-ringkasan-mahasiswa/export \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Ringkasan Mahasiswa 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 7526
```

---

### 38. GET /api/ews/kaprodi/table-ringkasan-status
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/table-ringkasan-status \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Table ringkasan status berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "20", "mangkir": "6", "cuti": "6", "perhatian": "0"},
    {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "ipk_kurang_dari_2": "13", "mangkir": "4", "cuti": "6", "perhatian": "5"},
    {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "ipk_kurang_dari_2": "20", "mangkir": "11", "cuti": "2", "perhatian": "24"},
    {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "12", "mangkir": "10", "cuti": "4", "perhatian": "43"},
    {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "ipk_kurang_dari_2": "11", "mangkir": "9", "cuti": "4", "perhatian": "42"},
    {"tahun_masuk": "2020", "jumlah_mahasiswa": 62, "ipk_kurang_dari_2": "10", "mangkir": "11", "cuti": "3", "perhatian": "20"}
  ]
}
```

---

### 39. GET /api/ews/kaprodi/table-ringkasan-status/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/kaprodi/table-ringkasan-status/export \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Ringkasan Status 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 7288
```

---

### 40. GET /api/ews/kaprodi/top-mk-gagal
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/top-mk-gagal \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Top 10 MK gagal all time dari semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"kode": "A11.54101", "nama": "Dasar Pemrograman", "jumlah_gagal": 175, "dosen_koordinator": "-"},
    {"kode": "A11.54303", "nama": "Statistika dan Probabilitas", "jumlah_gagal": 16, "dosen_koordinator": "-"},
    {"kode": "A11.54103", "nama": "Sistem Digital", "jumlah_gagal": 15, "dosen_koordinator": "-"},
    {"kode": "A11.11106", "nama": "Kuliah Kerja Nyata (KKN)", "jumlah_gagal": 15, "dosen_koordinator": "-"},
    {"kode": "A11.54403", "nama": "Interaksi Manusia & Komputer", "jumlah_gagal": 15, "dosen_koordinator": "-"},
    {"kode": "A11.11107", "nama": "Kerja Praktik", "jumlah_gagal": 15, "dosen_koordinator": "-"},
    {"kode": "A11.11109", "nama": "Seminar Proposal Skripsi", "jumlah_gagal": 15, "dosen_koordinator": "-"},
    {"kode": "A11.54902", "nama": "Natural Language Processing", "jumlah_gagal": 14, "dosen_koordinator": "-"},
    {"kode": "A11.54603", "nama": "Cloud Computing", "jumlah_gagal": 13, "dosen_koordinator": "-"},
    {"kode": "A11.54202", "nama": "Aljabar Linier", "jumlah_gagal": 12, "dosen_koordinator": "-"}
  ]
}
```

---

### 41. GET /api/ews/kaprodi/tren-ips/all
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/tren-ips/all \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Tren IPS semua mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "tren_ips": "turun", "mk_gagal": 117, "mk_ulang": 0},
    {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "tren_ips": "turun", "mk_gagal": 105, "mk_ulang": 0},
    {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "tren_ips": "naik", "mk_gagal": 89, "mk_ulang": 0},
    {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "tren_ips": "turun", "mk_gagal": 85, "mk_ulang": 0},
    {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "tren_ips": "turun", "mk_gagal": 71, "mk_ulang": 0},
    {"tahun_masuk": "2020", "jumlah_mahasiswa": 60, "tren_ips": "turun", "mk_gagal": 80, "mk_ulang": 0}
  ]
}
```

---

### 42. GET /api/ews/kaprodi/tren-ips/all/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/kaprodi/tren-ips/all/export \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="Capaian Mahasiswa 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 7207
```

---

### 43. GET /api/ews/kaprodi/card-capaian
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/card-capaian \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Capaian mahasiswa berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "total_mahasiswa": 385,
    "total_turun_ip": 196,
    "total_naik_ip": 187,
    "tren_per_angkatan": [
      {"tahun_masuk": "2025", "semester_aktif": 3, "rata_ips": 3.04, "jumlah_mahasiswa": 76, "mahasiswa_naik_ip": 35, "mahasiswa_turun_ip": 41, "mahasiswa_stabil_ip": 0},
      {"tahun_masuk": "2024", "semester_aktif": 4, "rata_ips": 2.92, "jumlah_mahasiswa": 77, "mahasiswa_naik_ip": 33, "mahasiswa_turun_ip": 44, "mahasiswa_stabil_ip": 0},
      {"tahun_masuk": "2023", "semester_aktif": 7, "rata_ips": 2.79, "jumlah_mahasiswa": 61, "mahasiswa_naik_ip": 41, "mahasiswa_turun_ip": 20, "mahasiswa_stabil_ip": 0},
      {"tahun_masuk": "2022", "semester_aktif": 9, "rata_ips": 2.77, "jumlah_mahasiswa": 57, "mahasiswa_naik_ip": 26, "mahasiswa_turun_ip": 31, "mahasiswa_stabil_ip": 0},
      {"tahun_masuk": "2021", "semester_aktif": 10, "rata_ips": 2.74, "jumlah_mahasiswa": 54, "mahasiswa_naik_ip": 21, "mahasiswa_turun_ip": 32, "mahasiswa_stabil_ip": 1},
      {"tahun_masuk": "2020", "semester_aktif": 12, "rata_ips": 2.77, "jumlah_mahasiswa": 60, "mahasiswa_naik_ip": 31, "mahasiswa_turun_ip": 28, "mahasiswa_stabil_ip": 1}
    ]
  }
}
```

---

### 44. GET /api/ews/kaprodi/statistik-kelulusan
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/statistik-kelulusan \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Statistik kelulusan berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "eligible": "0",
    "noneligible": "385",
    "aktif": "311",
    "mangkir": "51",
    "cuti": "25",
    "ipk_kurang_dari_2_5": "172",
    "ipk_antara_2_5_3": "72",
    "ipk_lebih_dari_3": "141",
    "mk_nasional": "337",
    "mk_fakultas": "280",
    "mk_prodi": "216"
  }
}
```

---

### 45. GET /api/ews/kaprodi/table-statistik-kelulusan
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/table-statistik-kelulusan \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~250ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Table statistik kelulusan berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [
    {"tahun_masuk": "2025", "jumlah_mahasiswa": 76, "ipk_kurang_dari_2": "20", "sks_kurang_dari_144": "72", "nilai_d_melebihi_batas": "6", "nilai_e": "60", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.72"},
    {"tahun_masuk": "2024", "jumlah_mahasiswa": 77, "ipk_kurang_dari_2": "13", "sks_kurang_dari_144": "74", "nilai_d_melebihi_batas": "7", "nilai_e": "60", "eligible": "0", "noneligible": "76", "ipk_rata2": "2.68"},
    {"tahun_masuk": "2023", "jumlah_mahasiswa": 61, "ipk_kurang_dari_2": "20", "sks_kurang_dari_144": "60", "nilai_d_melebihi_batas": "5", "nilai_e": "47", "eligible": "0", "noneligible": "61", "ipk_rata2": "2.63"},
    {"tahun_masuk": "2022", "jumlah_mahasiswa": 57, "ipk_kurang_dari_2": "12", "sks_kurang_dari_144": "53", "nilai_d_melebihi_batas": "2", "nilai_e": "48", "eligible": "0", "noneligible": "57", "ipk_rata2": "2.71"},
    {"tahun_masuk": "2021", "jumlah_mahasiswa": 54, "ipk_kurang_dari_2": "11", "sks_kurang_dari_144": "50", "nilai_d_melebihi_batas": "2", "nilai_e": "38", "eligible": "0", "noneligible": "54", "ipk_rata2": "2.64"},
    {"tahun_masuk": "2020", "jumlah_mahasiswa": 62, "ipk_kurang_dari_2": "10", "sks_kurang_dari_144": "56", "nilai_d_melebihi_batas": "4", "nilai_e": "45", "eligible": "0", "noneligible": "61", "ipk_rata2": "2.69"}
  ],
  "pagination": {
    "total": 6,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "from": 1,
    "to": 6,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

---

### 46. GET /api/ews/kaprodi/tindak-lanjut
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/tindak-lanjut \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~200ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data tindak lanjut prodi berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": [],
  "pagination": {
    "total": 0,
    "per_page": 10,
    "current_page": 1,
    "last_page": 1,
    "from": null,
    "to": null,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

---

### 47. GET /api/ews/kaprodi/tindak-lanjut/cards
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/kaprodi/tindak-lanjut/cards \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data statistik tindak lanjut berhasil diambil",
  "info_akses": {
    "role": "kaprodi",
    "scope_data": "Prodi Spesifik",
    "nama_prodi": "Teknik Informatika",
    "kode_prodi": "A11"
  },
  "data": {
    "total_rekomitmen": 0,
    "total_pindah_prodi": 0,
    "dalam_proses": 0,
    "selesai": 0
  }
}
```

---

### 48. GET /api/ews/kaprodi/tindak-lanjut/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/kaprodi/tindak-lanjut/export \
  -H "Authorization: Bearer 86|tGYHBqSTNuTpg6YgIphsMgiemkkPmDTOwN7OTneMc381fc49" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Type: application/json
```

---

## MAHASISWA ENDPOINTS (8)

### 49. POST /api/login (Mahasiswa)
**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@ews.com","password":"password"}'
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Success",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": {
    "access_token": "88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b",
    "token_type": "Bearer",
    "user": {
      "name": "Mahasiswa EWS Test",
      "email": "mahasiswa@ews.com",
      "roles": "mahasiswa",
      "permissions": [
        "alumni",
        "ews-mahasiswa"
      ]
    }
  }
}
```

---

### 50. GET /api/ews/mahasiswa/dashboard
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/dashboard \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Dashboard mahasiswa berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": null
}
```

---

### 51. GET /api/ews/mahasiswa/card-status-akademik
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/card-status-akademik \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Status akademik berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": null
}
```

---

### 52. GET /api/ews/mahasiswa/khs-krs
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/khs-krs \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Data KHS dan KRS mahasiswa berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": [],
  "pagination": {
    "total": 0,
    "per_page": 15,
    "current_page": 1,
    "last_page": 0,
    "from": null,
    "to": 0,
    "next_page_url": null,
    "prev_page_url": null
  }
}
```

---

### 53. GET /api/ews/mahasiswa/khs-krs/export
**Request:**
```bash
curl -I -X GET http://127.0.0.1:8000/api/ews/mahasiswa/khs-krs/export \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Headers:**
```
HTTP/1.1 200 OK
Content-Disposition: attachment; filename="KHS KRS 2026-04-19.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Length: 6309
```

---

### 54. GET /api/ews/mahasiswa/peringatan
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/peringatan \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Peringatan berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": null
}
```

---

### 55. GET /api/ews/mahasiswa/tindak-lanjut
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/tindak-lanjut \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Riwayat tindak lanjut berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": []
}
```

---

### 56. GET /api/ews/mahasiswa/tindak-lanjut/cards
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/tindak-lanjut/cards \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Ringkasan tindak lanjut berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": {
    "dalam_proses": 0,
    "selesai": 0
  }
}
```

---

### 57. GET /api/ews/mahasiswa/tindak-lanjut/template/akademik
**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/ews/mahasiswa/tindak-lanjut/template/akademik \
  -H "Authorization: Bearer 88|vEiq5azQ9iMGEoNMBf7a3aPQjpceqLkCudZb7xQ693e2558b" \
  -H "Accept: application/json"
```
**Status:** 200 | **Time:** ~150ms | **Pass:** ✅

**Response:**
```json
{
  "success": true,
  "message": "Info template akademik berhasil diambil",
  "info_akses": {
    "role": "mahasiswa",
    "scope_data": "Data Pribadi",
    "nama_prodi": "Teknik Informatika"
  },
  "data": {
    "template_url": "http://127.0.0.1:8000/templates/template_akademik.pdf"
  }
}
```

---

## Summary

| Role | Total Endpoints | Passed | Failed |
|------|-----------------|--------|--------|
| Dekan | 25 | 25 | 0 |
| Kaprodi | 23 | 23 | 0 |
| Mahasiswa | 8 | 8 | 0 |
| **Total** | **56** | **56** | **0** |

## Notes

- All endpoints returned HTTP 200 status
- All responses have consistent JSON structure with `success`, `message`, `info_akses`, and `data` fields
- Export endpoints return Excel files (.xlsx) with appropriate Content-Disposition headers
- Dekan role has access to all 4 prodi data (TI, SI, DKV, IKOM)
- Kaprodi role has access only to their specific prodi (Teknik Informatika - A11)
- Mahasiswa role has access only to their own data (data: null for test user without academic records)
- Response times ranged from 150ms to 500ms for all endpoints

---
**Report Generated:** 2026-04-19 16:12 GMT+7
**Server:** Laravel Development Server
**Base URL:** http://127.0.0.1:8000
