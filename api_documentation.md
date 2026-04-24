# EWS API Documentation

## Early Warning System API Documentation for Frontend Integration

**Base URL:** `{{api_base}}/api` (misal: `http://localhost:8000/api`)

**Authentication:** Bearer Token (Laravel Sanctum)

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Dekan Dashboard](#2-dekan-dashboard)
3. [Dekan Statistik Kelulusan](#3-dekan-statistik-kelulusan)
4. [Dekan Detail Angkatan](#4-dekan-detail-angkatan)
5. [Dekan Mahasiswa List](#5-dekan-mahasiswa-list)
6. [Dekan Nilai Mahasiswa](#6-dekan-nilai-mahasiswa)
7. [Dekan Recalculate EWS](#7-dekan-recalculate-ews)
8. [Kaprodi Dashboard](#8-kaprodi-dashboard)
9. [Kaprodi Statistik Kelulusan](#9-kaprodi-statistik-kelulusan)
10. [Kaprodi Recalculate EWS](#10-kaprodi-recalculate-ews)
11. [Mahasiswa Profile](#11-mahasiswa-profile)
12. [Export Endpoints](#12-export-endpoints)
13. [Common Data Types](#13-common-data-types)

---

## 1. Authentication

### POST /login-dekan
Login sebagai Dekan.

**Request:**
```json
{
  "email": "dekan@ews.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "access_token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "name": "Nama Dekan",
      "email": "dekan@ews.com",
      "roles": "dekan",
      "permissions": []
    },
    "dekan": {
      "scope": "fakultas"
    }
  }
}
```

---

### POST /login-kaprodi
Login sebagai Kaprodi.

**Request:**
```json
{
  "email": "kaprodi_a11@ews.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "access_token": "2|abc123...",
    "token_type": "Bearer",
    "user": {
      "name": "Nama Kaprodi",
      "email": "kaprodi_a11@ews.com",
      "roles": "kaprodi",
      "permissions": []
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

### POST /login-mahasiswa
Login sebagai Mahasiswa.

**Request:**
```json
{
  "email": "dummy_A11_mhs1@ews.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "access_token": "3|abc123...",
    "token_type": "Bearer",
    "user": {
      "name": "Nama Mahasiswa",
      "email": "dummy_A11_mhs1@ews.com",
      "roles": "mahasiswa",
      "permissions": [],
      "foto": "https://mahasiswa.dinus.ac.id/images/foto/..."
    },
    "mahasiswa": {
      "id": 1,
      "nim": "A11.2021.12345",
      "telepon": "081234567890",
      "transkrip": "..." ,
      "minat": "..." ,
      "prodi": "Teknik Informatika",
      "is_completed": true
    }
  }
}
```

---

### GET /profile
Get profile user yang sedang login (semua role).

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Profile berhasil diambil",
  "data": {
    "user": {
      "id": 1,
      "name": "Nama User",
      "email": "user@email.com",
      "roles": "dekan|kaprodi|mahasiswa|dosen",
      "permissions": ["permission_name"]
    },
    // Untuk role spesifik (dekan/kaprodi/mahasiswa), akan ada data tambahan
    "dekan": { "scope": "fakultas" },
    // ATAU
    "kaprodi": { "prodi_id": 1, "prodi": "Teknik Informatika", "kode_prodi": "A11" },
    // ATAU
    "mahasiswa": {
      "id": 1,
      "nim": "A11.2021.12345",
      "ipk": 3.45,
      "telepon": "081234567890",
      "transkrip": "..." ,
      "minat": "..." ,
      "semester_aktif": 6,
      "is_completed": true
    }
  }
}
```

---

## 2. Dekan Dashboard

**Auth Required:** Bearer Token dengan role `dekan`

### GET /ews/dekan/dashboard
Overview dashboard dekan (semua prodi).

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Dashboard berhasil diambil",
  "data": {
    "statistik_global": {
      "total_mahasiswa": 150,
      "total_mahasiswa_aktif": 120,
      "total_mahasiswa_mangkir": 5,
      "total_mahasiswa_cuti": 10,
      "total_mahasiswa_do": 3
    },
    "rata_ipk_per_tahun": [
      {
        "tahun_masuk": 2021,
        "rata_ipk": 3.25,
        "jumlah_mahasiswa": 50
      }
    ],
    "statistik_kelulusan": {
      "eligible": 100,
      "non_eligible": 50
    },
    "tabel_ringkasan_prodi": [
      {
        "prodi": {
          "id": 1,
          "kode_prodi": "A11",
          "nama_prodi": "Teknik Informatika"
        },
        "jumlah_mahasiswa": 50,
        "jumlah_mahasiswa_aktif": 45,
        "jumlah_mahasiswa_cuti": 3,
        "jumlah_mahasiswa_mangkir": 2,
        "ipk_rata_rata": 3.30,
        "jumlah_tepat_waktu": 30,
        "jumlah_perhatian": 15,
        "jumlah_kritis": 5
      }
    ]
  }
}
```

---

### GET /ews/dekan/dashboard/detail
Detail data per prodi dan tahun angkatan.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |

**Example:** `/ews/dekan/dashboard/detail?prodi_id=1`

**Response (200):**
```json
{
  "success": true,
  "message": "Detail dashboard berhasil diambil",
  "data": [
    {
      "prodi": {
        "id": 1,
        "kode_prodi": "A11",
        "nama_prodi": "Teknik Informatika"
      },
      "tahun_angkatan": [
        {
          "tahun_masuk": 2021,
          "jumlah_mahasiswa": 50,
          "mahasiswa_aktif": 45,
          "jumlah_cuti_2x": 2,
          "ipk_rata_rata": 3.25,
          "tepat_waktu": 30,
          "normal": 10,
          "perhatian": 7,
          "kritis": 3
        }
      ]
    }
  ]
}
```

---

### GET /ews/dekan/dashboard/mahasiswa
List mahasiswa berdasarkan kriteria spesifik.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | Yes | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |
| criteria | string | No | Filter kriteria: `aktif`, `cuti_2x`, `tepat_waktu`, `perhatian`, `kritis` |

**Example:** `/ews/dekan/dashboard/mahasiswa?prodi_id=1&tahun_masuk=2021&criteria=kritis`

**Response (200):**
```json
{
  "success": true,
  "message": "List mahasiswa berhasil diambil",
  "data": [
    {
      "tahun_masuk": 2021,
      "jumlah": 5,
      "mahasiswa": [
        {
          "mahasiswa_id": 1,
          "nim": "A11.2021.12345",
          "nama_mahasiswa": "Nama Mahasiswa",
          "sks_total": 120,
          "ipk": 2.80,
          "status_mahasiswa": "aktif",
          "ews_status": "kritis"
        }
      ]
    }
  ]
}
```

---

## 3. Dekan Statistik Kelulusan

**Auth Required:** Bearer Token dengan role `dekan`

### GET /ews/dekan/statistik-kelulusan
Statistik kelulusan per prodi dengan detail per tahun angkatan.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| per_page | integer | No | Jumlah item per halaman (default: 10) |

**Example:** `/ews/dekan/statistik-kelulusan?prodi_id=1`

**Response (200):**
```json
{
  "success": true,
  "message": "Statistik kelulusan berhasil diambil",
  "data": {
    "data": [
      {
        "nama_prodi": "Teknik Informatika",
        "kode_prodi": "A11",
        "tahun_masuk": 2021,
        "jumlah_mahasiswa": 50,
        "ipk_kurang_dari_2": 3,
        "sks_kurang_dari_144": 10,
        "nilai_d_melebihi_batas": 5,
        "nilai_e": 2,
        "eligible": 40,
        "noneligible": 10,
        "ipk_rata2": 3.25
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 10,
      "total": 5
    }
  }
}
```

---

## 4. Dekan Detail Angkatan

**Auth Required:** Bearer Token dengan role `dekan`

### GET /ews/dekan/tahun-angkatan
List tahun angkatan yang tersedia.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |

**Response (200):**
```json
{
  "success": true,
  "message": "Tahun angkatan berhasil diambil",
  "data": {
    "tahun_angkatan": [
      {
        "tahun_masuk": 2021,
        "prodi": [
          { "id": 1, "kode_prodi": "A11", "nama_prodi": "Teknik Informatika" }
        ]
      }
    ],
    "prodi": [
      { "id": 1, "kode_prodi": "A11", "nama_prodi": "Teknik Informatika" },
      { "id": 2, "kode_prodi": "A12", "nama_prodi": "Sistem Informasi" }
    ]
  }
}
```

---

### GET /ews/dekan/detail-angkatan/{tahunMasuk}
Detail mahasiswa per tahun angkatan.

**Headers:** `Authorization: Bearer {access_token}`

**Path Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| tahunMasuk | integer | Yes | Tahun angkatan (misal: 2021) |

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |

**Example:** `/ews/dekan/detail-angkatan/2021?prodi_id=1`

**Response (200):**
```json
{
  "success": true,
  "message": "Detail angkatan berhasil diambil",
  "data": [
    {
      "mahasiswa_id": 1,
      "nim": "A11.2021.12345",
      "nama_mahasiswa": "Nama Mahasiswa",
      "sks_total": 120,
      "ipk": 3.25,
      "nilai_d": {
        "jumlah": 2,
        "total_sks": 4
      },
      "nilai_e": {
        "jumlah": 0,
        "total_sks": 0
      },
      "mk_nasional": "yes",
      "mk_fakultas": "yes",
      "mk_prodi": "no",
      "eligible": "noneligible"
    }
  ]
}
```

---

## 5. Dekan Mahasiswa List

**Auth Required:** Bearer Token dengan role `dekan`

### GET /ews/dekan/mahasiswa/kriteria
Melihat daftar filter/kriteria yang tersedia.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Kriteria tersedia berhasil diambil",
  "data": {
    "filters": {
      "prodi_id": "Filter berdasarkan ID Prodi",
      "tahun_masuk": "Filter berdasarkan tahun angkatan",
      "ipk_max": "IPK kurang dari nilai (contoh: 2.0)",
      "sks_max": "SKS lulus kurang dari nilai (contoh: 144)",
      "has_nilai_d": "Memiliki nilai D melebihi batas (true/false)",
      "has_nilai_e": "Memiliki nilai E (true/false)",
      "status_kelulusan": "Status kelulusan (eligible/noneligible)",
      "ews_status": "Status EWS (tepat_waktu/normal/perhatian/kritis)"
    },
    "status_kelulusan_options": ["eligible", "noneligible"],
    "ews_status_options": ["tepat_waktu", "normal", "perhatian", "kritis"]
  }
}
```

---

### GET /ews/dekan/mahasiswa/list
List mahasiswa dengan filter fleksibel.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |
| ipk_max | float | No | IPK kurang dari nilai tertentu |
| sks_max | integer | No | SKS lulus kurang dari nilai tertentu |
| has_nilai_d | boolean | No | Memiliki nilai D exceeding batas (true/false) |
| has_nilai_e | boolean | No | Memiliki nilai E (true/false) |
| status_kelulusan | string | No | `eligible` atau `noneligible` |
| ews_status | string | No | `tepat_waktu`, `normal`, `perhatian`, atau `kritis` |

**Example:** `/ews/dekan/mahasiswa/list?prodi_id=1&has_nilai_e=true&status_kelulusan=noneligible`

**Response (200):**
```json
{
  "success": true,
  "message": "List mahasiswa berhasil diambil",
  "data": [
    {
      "mahasiswa_id": 1,
      "nim": "A11.2021.12345",
      "nama_mahasiswa": "Nama Mahasiswa",
      "prodi": {
        "id": 1,
        "kode_prodi": "A11",
        "nama_prodi": "Teknik Informatika"
      },
      "tahun_masuk": 2021,
      "sks_total": 120,
      "ipk": 2.80,
      "nilai_d_melebihi_batas": "no",
      "nilai_e": "yes",
      "ews_status": "perhatian",
      "status_kelulusan": "noneligible"
    }
  ]
}
```

---

## 6. Dekan Nilai Mahasiswa

**Auth Required:** Bearer Token dengan role `dekan`

### GET /ews/dekan/mahasiswa/nilai-detail
List mahasiswa dengan detail nilai D, E, SKS tidak lulus, dan MK kurang. Dapat juga获取 satu mahasiswa spesifik.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |
| has_nilai_d | boolean | No | Filter mahasiswa dengan nilai D (true/false) |
| has_nilai_e | boolean | No | Filter mahasiswa dengan nilai E (true/false) |
| mk_nasional_kurang | boolean | No | MK nasional belum lulus (true/false) |
| mk_fakultason_kurang | boolean | No | MK fakultason belum lulus (true/false) |
| status_kelulusan | string | No | `eligible` atau `noneligible` |
| search | string | No | Pencarian berdasarkan nama atau NIM |
| mahasiswa_id | integer | No | Ambil satu mahasiswa spesifik (mengabaikan per_page) |
| per_page | integer | No | Jumlah item per halaman (default: 10) |

**Example (List):** `/ews/dekan/mahasiswa/nilai-detail?prodi_id=1&has_nilai_e=true`

**Response (200) - List:**
```json
{
  "success": true,
  "message": "List nilai mahasiswa berhasil diambil",
  "data": {
    "paginated_data": {
      "data": [
        {
          "mahasiswa_id": 1,
          "nim": "A11.2021.12345",
          "nama_lengkap": "Nama Mahasiswa",
          "nama_prodi": "Teknik Informatika",
          "ipk": 3.25,
          "sks_lulus": 120,
          "mata_kuliah_nilai_d": [
            {
              "kode": "A11.101",
              "nama": "Matematika Diskrit",
              "sks": 3,
              "nilai_akhir_huruf": "D",
              "nilai_akhir_angka": 1.0
            }
          ],
          "jumlah_nilai_d": 1,
          "total_sks_nilai_d": 3,
          "mata_kuliah_nilai_e": [],
          "jumlah_nilai_e": 0,
          "total_sks_nilai_e": 0,
          "mk_nasional_kurang": [],
          "jumlah_mk_nasional_kurang": 0,
          "mk_fakultason_kurang": [],
          "jumlah_mk_fakultason_kurang": 0,
          "total_sks_tidak_lulus": 3
        }
      ],
      "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 5
      }
    },
    "total_mahasiswa": 5
  }
}
```

**Example (Single):** `/ews/dekan/mahasiswa/nilai-detail?mahasiswa_id=5`

**Response (200) - Single:**
```json
{
  "success": true,
  "message": "List nilai mahasiswa berhasil diambil",
  "data": {
    "data": [
      {
        "mahasiswa_id": 5,
        "nim": "A11.2021.12345",
        "nama_lengkap": "Nama Mahasiswa",
        "nama_prodi": "Teknik Informatika",
        "ipk": 3.25,
        "sks_lulus": 120,
        "mata_kuliah_nilai_d": [...],
        "mata_kuliah_nilai_e": [...],
        "mk_nasional_kurang": [...],
        "mk_fakultason_kurang": [...],
        "total_sks_tidak_lulus": 6
      }
    ],
    "total_mahasiswa": 1
  }
}
```

---

### GET /ews/dekan/mahasiswa/nilai-summary
Summary statistik total nilai D, E, dan MK kurang.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |

**Response (200):**
```json
{
  "success": true,
  "message": "Summary nilai mahasiswa berhasil diambil",
  "data": {
    "total_mahasiswa": 150,
    "mahasiswa_dengan_nilai_d": 25,
    "mahasiswa_dengan_nilai_e": 10,
    "mk_nasional_belum_lulus": 15,
    "mk_fakultason_belum_lulus": 12
  }
}
```

---

## 7. Dekan Recalculate EWS

**Auth Required:** Bearer Token dengan role `dekan`

### POST /ews/dekan/mahasiswa/{mahasiswaId}/recalculate-status
Recalculate EWS status untuk satu mahasiswa.

**Headers:** `Authorization: Bearer {access_token}`

**Path Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| mahasiswaId | integer | Yes | ID mahasiswa |

**Response (200):**
```json
{
  "success": true,
  "message": "Recalculate status berhasil",
  "data": {
    "mahasiswa_id": 1,
    "status": "perhatian",
    "status_kelulusan": "noneligible",
    "total_processed": 1,
    "total_updated": 1
  }
}
```

---

### POST /ews/dekan/recalculate-all-status
Recalculate EWS status untuk semua mahasiswa di fakultas.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Recalculate semua status berhasil",
  "data": {
    "total_processed": 150,
    "total_updated": 150
  }
}
```

---

## 8. Kaprodi Dashboard

**Auth Required:** Bearer Token dengan role `kaprodi`

### GET /ews/kaprodi/dashboard
Dashboard kaprodi (hanya prodi sendiri).

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Dashboard berhasil diambil",
  "data": {
    "statistik_global": {
      "total_mahasiswa": 50,
      "total_mahasiswa_aktif": 45,
      "total_mahasiswa_mangkir": 2,
      "total_mahasiswa_cuti": 3,
      "total_mahasiswa_do": 0
    },
    "rata_ipk_per_tahun": [
      {
        "tahun_masuk": 2021,
        "rata_ipk": 3.30,
        "jumlah_mahasiswa": 20
      }
    ],
    "statistik_kelulusan": {
      "eligible": 35,
      "non_eligible": 15
    },
    "tabel_ringkasan_prodi": [
      {
        "prodi": {
          "id": 1,
          "kode_prodi": "A11",
          "nama_prodi": "Teknik Informatika"
        },
        "jumlah_mahasiswa": 50,
        "jumlah_mahasiswa_aktif": 45,
        "jumlah_mahasiswa_cuti": 3,
        "jumlah_mahasiswa_mangkir": 2,
        "ipk_rata_rata": 3.30,
        "jumlah_tepat_waktu": 25,
        "jumlah_perhatian": 15,
        "jumlah_kritis": 5
      }
    ]
  }
}
```

---

### GET /ews/kaprodi/dashboard/detail
Detail per tahun angkatan di prodi kaprodi.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Detail dashboard berhasil diambil",
  "data": {
    "prodi": {
      "id": 1,
      "kode_prodi": "A11",
      "nama_prodi": "Teknik Informatika"
    },
    "tahun_angkatan": [
      {
        "tahun_masuk": 2021,
        "jumlah_mahasiswa": 50,
        "mahasiswa_aktif": 45,
        "jumlah_cuti_2x": 2,
        "ipk_rata_rata": 3.25,
        "tepat_waktu": 30,
        "normal": 10,
        "perhatian": 7,
        "kritis": 3
      }
    ]
  }
}
```

---

### GET /ews/kaprodi/dashboard/mahasiswa
List mahasiswa dengan filter.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |
| criteria | string | No | Filter kriteria: `aktif`, `cuti_2x`, `tepat_waktu`, `perhatian`, `kritis` |

**Example:** `/ews/kaprodi/dashboard/mahasiswa?tahun_masuk=2021&criteria=kritis`

**Response (200):**
```json
{
  "success": true,
  "message": "List mahasiswa berhasil diambil",
  "data": [
    {
      "tahun_masuk": 2021,
      "jumlah": 3,
      "mahasiswa": [
        {
          "mahasiswa_id": 1,
          "nim": "A11.2021.12345",
          "nama_mahasiswa": "Nama Mahasiswa",
          "sks_total": 120,
          "ipk": 2.80,
          "status_mahasiswa": "aktif",
          "ews_status": "kritis"
        }
      ]
    }
  ]
}
```

---

## 9. Kaprodi Statistik Kelulusan

**Auth Required:** Bearer Token dengan role `kaprodi`

### GET /ews/kaprodi/statistik-kelulusan
Statistik kelulusan untuk prodi kaprodi dengan detail per tahun angkatan.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Statistik kelulusan berhasil diambil",
  "data": {
    "prodi": {
      "id": 1,
      "kode_prodi": "A11",
      "nama_prodi": "Teknik Informatika"
    },
    "jumlah_mahasiswa": 50,
    "ipk_dibawah_2": 3,
    "sks_kurang_dari_144": 8,
    "nilai_d_lebih_dari_5_persen": 4,
    "ada_nilai_e": 2,
    "eligible": 40,
    "tidak_eligible": 10,
    "ipk_rata_rata": 3.25,
    "detail_per_tahun": [
      {
        "tahun_masuk": 2021,
        "jumlah_mahasiswa": 25,
        "ipk_dibawah_2": 1,
        "sks_kurang_dari_144": 3,
        "nilai_d_lebih_dari_5_persen": 2,
        "ada_nilai_e": 1,
        "eligible": 20,
        "tidak_eligible": 5,
        "ipk_rata_rata": 3.30
      }
    ]
  }
}
```

---

## 10. Kaprodi Recalculate EWS

**Auth Required:** Bearer Token dengan role `kaprodi`

### POST /ews/kaprodi/mahasiswa/{mahasiswaId}/recalculate-status
Recalculate EWS status untuk satu mahasiswa di prodi sendiri.

**Headers:** `Authorization: Bearer {access_token}`

**Path Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| mahasiswaId | integer | Yes | ID mahasiswa |

**Response (200):**
```json
{
  "success": true,
  "message": "Recalculate status berhasil",
  "data": {
    "mahasiswa_id": 1,
    "status": "perhatian",
    "status_kelulusan": "noneligible"
  }
}
```

---

### POST /ews/kaprodi/recalculate-all-status
Recalculate EWS status untuk semua mahasiswa di prodi kaprodi.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Recalculate semua status berhasil",
  "data": {
    "total_processed": 50,
    "total_updated": 50
  }
}
```

---

## 11. Mahasiswa Profile

**Auth Required:** Bearer Token dengan role `mahasiswa`

### GET /ews/mahasiswa/profile
Get profile mahasiswa lengkap.

**Headers:** `Authorization: Bearer {access_token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Profile berhasil diambil",
  "data": {
    "mahasiswa": {
      "nama": "Nama Mahasiswa",
      "nim": "A11.2021.12345",
      "semester_aktif": 6,
      "tahun_masuk": 2021
    },
    "akademik": {
      "ipk": 3.45,
      "sks_lulus": 120,
      "sks_tempuh": 144,
      "sks_now": 18
    },
    "ews": {
      "status": "perhatian",
      "status_kelulusan": "eligible",
      "alasan_tidak_eligible": []
    },
    "ips": [
      { "semester": 1, "ips": 3.50 },
      { "semester": 2, "ips": 3.75 }
    ],
    "matakuliah_nilai_de": [
      {
        "kode_mk": "A11.101",
        "nama_mk": "Matematika Diskrit",
        "sks": 3,
        "semester": 1,
        "kelompok": "W",
        "nilai": "D",
        "nilai_angka": 1.0
      }
    ],
    "progress_mk": {
      "mk_nasional": "yes",
      "mk_fakultas": "yes",
      "mk_prodi": "no"
    }
  }
}
```

---

## 12. Export Endpoints

All export endpoints return an XLSX file download. The response will have `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.

**Auth Required:** Bearer Token dengan role yang sesuai (`dekan`, `kaprodi`, atau `mahasiswa`)

### Dekan Export Endpoints

#### GET /ews/dekan/export/dashboard
Export seluruh dashboard dekan ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:** Tidak ada filter - export semua data.

**Response:** File XLSX dengan sheet:
- Statistik Global
- Rata-rata IPK per Tahun
- Statistik Kelulusan
- Ringkasan per Program Studi

---

#### GET /ews/dekan/export/dashboard-detail
Export detail dashboard dekan (per prodi dan tahun angkatan) ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter Export ke satu Prodi saja |

**Example:** `/ews/dekan/export/dashboard-detail?prodi_id=1`

**Response:** File XLSX dengan sheet per Prodi.

---

#### GET /ews/dekan/export/statistik-kelulusan
Export statistik kelulusan ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter Export ke satu Prodi saja |

**Response:** File XLSX dengan statistik per Prodi dan detail per tahun angkatan.

---

#### GET /ews/dekan/export/detail-angkatan/{tahunMasuk}
Export detail angkatan ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Path Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| tahunMasuk | integer | Yes | Tahun angkatan (misal: 2021) |

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter ke satu Prodi saja |

**Response:** File XLSX dengan daftar mahasiswa angkatan tersebut.

---

#### GET /ews/dekan/export/mahasiswa-list
Export list mahasiswa dengan filter ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |
| ipk_max | float | No | IPK kurang dari nilai tertentu |
| sks_max | integer | No | SKS lulus kurang dari nilai tertentu |
| has_nilai_d | boolean | No | Memiliki nilai D (true/false) |
| has_nilai_e | boolean | No | Memiliki nilai E (true/false) |
| status_kelulusan | string | No | `eligible` atau `noneligible` |
| ews_status | string | No | Status EWS |

**Response:** File XLSX dengan kolom: No, NIM, Nama, Prodi, Tahun Masuk, SKS Total, IPK, Nilai D, Nilai E, Status EWS, Status Kelulusan.

---

#### GET /ews/dekan/export/nilai-detail
Export detail nilai mahasiswa ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |
| mahasiswa_id | integer | No | Export satu mahasiswa spesifik |

**Response:** File XLSX dengan detail nilai D, E, MK nasional, dan MK fakultason.

---

#### GET /ews/dekan/export/nilai-summary
Export summary nilai mahasiswa ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| prodi_id | integer | No | Filter berdasarkan ID Prodi |
| tahun_masuk | integer | No | Filter berdasarkan tahun angkatan |

**Response:** File XLSX dengan statistik total mahasiswa, nilai D, nilai E, dan MK kurang.

---

### Kaprodi Export Endpoints

#### GET /ews/kaprodi/export/dashboard
Export dashboard kaprodi ke file XLSX (hanya prodi sendiri).

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| tahun_masuk | integer | No | Filter per tahun angkatan |

**Example:** `/ews/kaprodi/export/dashboard?tahun_masuk=2021`

**Response:** File XLSX dengan sheet:
- Statistik Global (hanya prodi sendiri)
- Rata-rata IPK per Tahun
- Statistik Kelulusan
- Ringkasan Prodi

---

#### GET /ews/kaprodi/export/dashboard-detail
Export detail dashboard kaprodi (per tahun angkatan) ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| tahun_masuk | integer | No | Filter per tahun angkatan |

**Example:** `/ews/kaprodi/export/dashboard-detail?tahun_masuk=2021`

**Response:** File XLSX dengan detail per tahun angkatan di prodi kaprodi.

---

#### GET /ews/kaprodi/export/statistik-kelulusan
Export statistik kelulusan prodi kaprodi ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|------------|------|----------|-------------|
| tahun_masuk | integer | No | Filter per tahun angkatan |

**Example:** `/ews/kaprodi/export/statistik-kelulusan?tahun_masuk=2021`

**Response:** File XLSX dengan summary dan detail per tahun angkatan.

---

### Mahasiswa Export Endpoints

#### GET /ews/mahasiswa/export/profile
Export profile mahasiswa sendiri ke file XLSX.

**Headers:** `Authorization: Bearer {access_token}`

**Response:** File XLSX dengan sheet:
- Data Mahasiswa
- Data Akademik (IPK, SKS)
- Status EWS
- IPS per Semester
- Mata Kuliah dengan Nilai D/E
- Progress MK (Nasional, Fakultas, Prodi)

---

## 13. Common Data Types

### Prodi Reference
```json
{
  "id": 1,
  "kode_prodi": "A11",
  "nama_prodi": "Teknik Informatika"
}
```

### EWS Status Options
| Status | Description |
|--------|-------------|
| `tepat_waktu` | Mahasiswa berpotensi lulus tepat 4 tahun |
| `normal` | Mahasiswa berjalan normal |
| `perhatian` | Mahasiswa perlu perhatian (berpotensi 5 tahun) |
| `kritis` | Mahasiswa dalam kondisi kritis (berpotensi DO/7 tahun) |

### Status Kelulusan Options
| Status | Description |
|--------|-------------|
| `eligible` | Mahasiswa eligible untuk lulus |
| `noneligible` | Mahasiswa tidak eligible untuk lulus |

### Alasan Tidak Eligible
- IPK kurang dari atau sama dengan 2.0
- SKS Lulus kurang dari 144
- MK Nasional belum diselesaikan
- MK Fakultas belum diselesaikan
- MK Prodi belum diselesaikan
- Memiliki nilai E
- Nilai D melebihi batas 5% (lebih dari 7.2 SKS atau lebih dari 2 MK)

### Status Mahasiswa Options
| Status | Description |
|--------|-------------|
| `aktif` | Mahasiswa aktif kuliah |
| `cuti` | Mahasiswa cuti kuliah |
| `mangkir` | Mahasiswa mangkir |
| `lulus` | Mahasiswa sudah lulus |
| `do` | Mahasiswa dropout |

### Standard Error Response
```json
{
  "success": false,
  "message": "Error message",
  "data": null
}
```

### HTTP Status Codes
| Code | Description |
|------|-------------|
| 200 | Success |
| 401 | Unauthorized (invalid/missing token) |
| 403 | Forbidden (wrong role) |
| 500 | Internal Server Error |

---

## Integration Notes

### Authentication Flow
1. Frontend melakukan POST ke `/login-{role}` dengan email dan password
2. Simpan `access_token` dari response
3. Sertakan `Authorization: Bearer {access_token}` di semua request berikutnya
4. Token会自动续期

### Error Handling
- Selalu проверка `success` field dalam response
- Для error, проверка `message` untuk detail error
- 401 error berarti token expired atau invalid, lakukan relogin

### Pagination
Beberapa endpoint menggunakan pagination dengan meta data:
```json
{
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

### Filter Combinations
- Filters dapat dikombinasikan untuk hasil yang lebih spesifik
- Filter `mahasiswa_id` akan mengabaikan pagination (mengembalikan single student)

---

*Generated for Frontend Integration - EWS Top FIK 2024*
