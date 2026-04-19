# EWS API Documentation

Early Warning System (EWS) REST API built with Laravel.

## Base Information

- **Base URL**: `http://localhost:8000/api`
- **Authentication**: Bearer Token (Laravel Sanctum)
- **Response Format**: JSON with consistent wrapper

## Standard Response Format

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response (4xx/5xx):**
```json
{
  "success": false,
  "message": "Error description",
  "error": { ... }
}
```

---

## Authentication

All endpoints except login require Bearer token authentication:

```
Authorization: Bearer {{access_token}}
```

The Postman collection auto-injects this header via a pre-request script.

---

## Role Summary

| Role | Prefix | Access Scope |
|------|--------|--------------|
| kaprodi | `/ews/kaprodi/` | Own prodi only |
| dekan | `/ews/dekan/` | All prodi in faculty |
| mahasiswa | `/ews/mahasiswa/` | Own data only |

---

## EWS Status Codes

Students are classified into 4 categories:

| Status | Color | Description | Condition |
|--------|-------|-------------|-----------|
| **MERAH** | 🔴 Red | Drop Out Risk | Cannot graduate within 7 years (14 semesters) |
| **KUNING** | 🟡 Yellow | At Risk | Can graduate within 7 years |
| **HIJAU** | 🟢 Green | On Track | Can graduate within 5 years |
| **BIRU** | 🔵 Blue | Graduated/Completed | Already completed studies |

### Status Determination Logic

The status is calculated based on:
1. **SKS remaining** vs **maximum SKS possible** to take
2. **NFU requirements** (Ujian Fitting) at specific semesters
3. **Grade history** (D and E grades)

See `EWS-LOGIC.md` for detailed logic documentation.

---

## API Endpoints

### 00 - Authentication

#### POST /api/login
Generic login for any registered user.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "1|abc...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com",
      "roles": "kaprodi"
    }
  }
}
```

---

#### POST /api/login-kaprodi
Login specifically for Kaprodi role. Returns `kaprodi` object with `prodi_id`.

**Request:**
```json
{
  "email": "kaprodi_a11@ews.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "2|xyz...",
    "token_type": "Bearer",
    "user": { ... },
    "kaprodi": {
      "id": 1,
      "prodi_id": 11,
      "nama_prodi": "Teknik Informatika"
    }
  }
}
```

---

#### POST /api/login-dekan
Login for Dekan role.

**Request:**
```json
{
  "email": "dekan@ews.com",
  "password": "password"
}
```

---

#### POST /api/login-mahasiswa
Login for Mahasiswa (student) role. Returns `mahasiswa` object with student data.

**Request:**
```json
{
  "email": "mahasiswa@ews.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "3|...",
    "token_type": "Bearer",
    "user": { ... },
    "mahasiswa": {
      "id": 1,
      "nim": "A11.2021.12345",
      "nama": "Nama Mahasiswa",
      "prodi_id": 11
    }
  }
}
```

---

#### GET /api/profile
Get authenticated user's profile.

**Headers:** `Authorization: Bearer {{access_token}}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "roles": "kaprodi"
  }
}
```

---

## Kaprodi Endpoints

**Base Path:** `/api/ews/kaprodi/`
**Auth:** `kaprodi` or `dekan` role

---

### Dashboard

#### GET /api/ews/kaprodi/dashboard

Get main dashboard overview for the prodi.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_mahasiswa": 150,
    "distribusi_status": {
      "MERAH": 5,
      "KUNING": 12,
      "HIJAU": 100,
      "BIRU": 33
    },
    "mahasiswa_bahaya": 5,
    "mahasiswa_issues": 17
  }
}
```

---

#### GET /api/ews/kaprodi/table-ringkasan-mahasiswa

Paginated table of student summaries.

**Query Parameters:**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| per_page | int | 20 | Items per page |
| search | string | "" | Search by name/NIM |
| tahun_masuk | int | all | Filter by admission year |

**Response:** Paginated list of student objects with `meta` pagination info.

---

#### GET /api/ews/kaprodi/table-ringkasan-mahasiswa/export

Download CSV export of student summary table.

---

### Student Management

#### GET /api/ews/kaprodi/mahasiswa/detail/{mahasiswaId}

Get detailed information about a specific student.

**Path Parameters:**
- `mahasiswaId` (int): Student ID

**Response:** Full student object including IPS history, SKS, grades.

---

#### GET /api/ews/kaprodi/mahasiswa/detail-angkatan/{tahunMasuk}

Get all students in a specific admission year (angkatan).

**Path Parameters:**
- `tahunMasuk` (int): e.g., 2021

**Query Parameters:** `per_page`, `search`

---

#### GET /api/ews/kaprodi/mahasiswa/all

Get list of ALL students in the prodi.

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| per_page | int | Items per page |
| search | string | Filter by name/NIM |
| mode | string | "detailed" for full data |

**Note:** This endpoint auto-sets `mahasiswa_id` from the first result.

---

### EWS Status

#### GET /api/ews/kaprodi/distribusi-status-ews

Get EWS status distribution (count per status color).

**Query Parameters:**
- `tahun_masuk` (int, optional): Filter by admission year

**Response:**
```json
{
  "success": true,
  "data": {
    "labels": ["MERAH", "KUNING", "HIJAU", "BIRU"],
    "data": [5, 12, 100, 33],
    "total": 150
  }
}
```

---

#### GET /api/ews/kaprodi/table-ringkasan-status

Get detailed EWS status summary broken down by angkatan.

---

#### POST /api/ews/kaprodi/mahasiswa/{mahasiswaId}/recalculate-status

Manually trigger EWS status recalculation for a specific student.

**Path Parameters:**
- `mahasiswaId` (int): Student ID

**Response:**
```json
{
  "success": true,
  "message": "Status EWS mahasiswa berhasil dihitung ulang",
  "data": {
    "mahasiswa_id": 1,
    "status_ews_baru": "HIJAU",
    "status_ews_lama": "KUNING"
  }
}
```

---

#### POST /api/ews/kaprodi/recalculate-all-status

Trigger EWS recalculation for ALL students in the prodi.

**Query Parameters:**
- `prodi_id` (int, optional): Specific prodi to recalculate

---

### Academic Achievement

#### GET /api/ews/kaprodi/tren-ips/all

Get semester-by-semester IPS trend data for charts.

**Query Parameters:**
- `tahun_masuk` (int, optional)

**Response:** Chart-compatible format with `labels` (S1, S2...) and `datasets`.

---

#### GET /api/ews/kaprodi/card-capaian

Get summary cards for academic achievement metrics.

**Response:**
```json
{
  "success": true,
  "data": {
    "rata_ips": 3.55,
    "jumlah_mahasiswa_at_risk": 17,
    "persentase_lulus_tepat_waktu": 78.5,
    "total_matkul_gagal": 45
  }
}
```

---

#### GET /api/ews/kaprodi/top-mk-gagal

Get top 10 most failed courses (matakuliah).

**Response:** List of courses with failure count and percentage.

---

### Graduation Statistics

#### GET /api/ews/kaprodi/statistik-kelulusan

Get graduation statistics summary cards.

**Response:**
```json
{
  "success": true,
  "data": {
    "lulus_tepat_waktu": 85,
    "lulus_7_tahun": 10,
    "drop_out": 5,
    "belum_lulus": 50
  }
}
```

---

#### GET /api/ews/kaprodi/table-statistik-kelulusan

Detailed graduation statistics broken down by angkatan.

---

### Follow-up Actions (Tindak Lanjut)

#### GET /api/ews/kaprodi/tindak-lanjut/cards

Get summary counts of tindak lanjut by status.

---

#### GET /api/ews/kaprodi/tindak-lanjut

Paginated list of all tindak lanjut in the prodi.

**Query Parameters:**
| Param | Description |
|-------|-------------|
| per_page | Items per page |
| search | Filter by student name |
| kategori | rekomitmen, bimbingan, surat_teguran, lain_lain |
| status | menunggu_verifikasi, telah_diverifikasi, ditolak |
| tahun_masuk | Filter by angkatan |

---

#### PATCH /api/ews/kaprodi/tindak-lanjut/{id}

Update status of a single tindak lanjut record.

**Request Body:**
```json
{
  "status": "telah_diverifikasi"
}
```

**Valid Status Values:**
- `menunggu_verifikasi` - Awaiting verification
- `telah_diverifikasi` - Verified/Approved
- `ditolak` - Rejected

---

#### PATCH /api/ews/kaprodi/tindak-lanjut/bulk-update

Bulk update status for multiple tindak lanjut records.

**Request Body:**
```json
{
  "ids": [1, 2, 3, 4, 5],
  "status": "telah_diverifikasi"
}
```

---

## Dekan Endpoints

**Base Path:** `/api/ews/dekan/`
**Auth:** `dekan` role only

All Kaprodi endpoints are available under Dekan with these differences:
- Views data across ALL prodi (faculty-wide)
- Additional query parameter: `?prodi_id=X` to filter by specific prodi
- **Does NOT have** bulk-update for tindak lanjut (Kaprodi only)

---

## Mahasiswa (Student) Endpoints

**Base Path:** `/api/ews/mahasiswa/`
**Auth:** `mahasiswa` role only

Students can ONLY access their own data.

---

### Dashboard

#### GET /api/ews/mahasiswa/dashboard

Personal student dashboard.

**Response:**
```json
{
  "success": true,
  "data": {
    "nama": "Nama Mahasiswa",
    "nim": "A11.2021.12345",
    "semester": 6,
    "status_ews": "HIJAU",
    "ips_terakhir": 3.65,
    "total_sks": 110,
    "peringatan": [...]
  }
}
```

---

#### GET /api/ews/mahasiswa/card-status-akademik

Academic status summary cards.

---

### KHS/KRS

#### GET /api/ews/mahasiswa/khs-krs

List of all KHS/KRS records (semester academic records).

**Query Parameters:** `per_page`, `page`

---

#### GET /api/ews/mahasiswa/khs-krs/{khsKrsId}

Detailed KHS/KRS for a specific semester showing all courses and grades.

---

### Warnings

#### GET /api/ews/mahasiswa/peringatan

Get all EWS warnings for the student.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tipe": "danger",
      "judul": "Risiko Drop Out",
      "pesan": "Sisa SKS Anda tidak memungkinkan lulus dalam 7 tahun",
      "created_at": "2024-01-15T10:00:00Z"
    }
  ]
}
```

**Tipe Values:**
- `danger` - Critical warning (MERAH)
- `warning` - Warning (KUNING)
- `info` - Information

---

### Follow-up Actions (Tindak Lanjut)

#### GET /api/ews/mahasiswa/tindak-lanjut/cards

Summary of student's own submitted tindak lanjut.

---

#### GET /api/ews/mahasiswa/tindak-lanjut

Paginated history of student's own tindak lanjut.

---

#### POST /api/ews/mahasiswa/tindak-lanjut

Submit a new tindak lanjut (follow-up action).

**Request Body:**
```json
{
  "kategori": "rekomitmen",
  "link": "https://drive.google.com/.../bukti.pdf"
}
```

**Kategori Values:**
- `rekomitmen` - Re-commitment letter
- `bimbingan` - Academic counseling
- `surat_teguran` - Warning letter response
- `lain_lain` - Other

**Link:** URL to evidence document (Google Drive, PDF, etc.)

---

#### GET /api/ews/mahasiswa/tindak-lanjut/template/{kategori}

Get template/guidelines for a specific tindak lanjut category.

**Path Parameter:** `kategori` - one of the categories above

---

## Common Query Parameters

| Parameter | Used In | Description |
|-----------|---------|-------------|
| `per_page` | List endpoints | Items per page (default varies) |
| `page` | List endpoints | Page number for pagination |
| `search` | List endpoints | Search/filter by text |
| `tahun_masuk` | Student endpoints | Filter by admission year |
| `prodi_id` | Dekan endpoints | Filter by study program |
| `status` | Tindak Lanjut | Filter by status |
| `kategori` | Tindak Lanjut | Filter by category |
| `mode` | Student endpoints | "detailed" for full data |

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created (new resource) |
| 400 | Bad Request (validation error) |
| 401 | Unauthorized (no/invalid token) |
| 403 | Forbidden (wrong role) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## Rate Limiting

Currently no rate limiting in development. Production may have standard Laravel throttle limits.
