# EWS API - Postman Guide (v3)

A comprehensive guide to using the EWS (Early Warning System) Postman collection covering all 56 tested endpoints.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Setup \& Import](#setup--import)
3. [Environment Variables](#environment-variables)
4. [Authentication](#authentication)
5. [Folder Structure](#folder-structure)
6. [Role-Based Access](#role-based-access)
7. [Endpoints Reference](#endpoints-reference)
8. [Troubleshooting](#troubleshooting)

---

## Quick Start

### 1-Minute Setup

1. **Import**: In Postman → Import → select both `EWS.postman_collection.json` and `EWS.postman_environment.json`
2. **Select environment**: Top-right dropdown → Select **"EWS - Local"**
3. **Login**: Expand **"🔐 Authentication"** → **"Login"** → Click **Send**
4. **Test**: Expand any role folder (👔 Dekan, 📋 Kaprodi, or 🎓 Mahasiswa) → run any request

If you get `success: true` back, you're ready!

---

## Setup & Import

### Import Steps

1. Open Postman
2. Click **Import** (top-left)
3. Drag & drop or browse to select the JSON files
4. Files appear in:
   - **Collections**: `EWS API (Dekan + Kaprodi + Mahasiswa)`
   - **Environments**: `EWS - Local`

### Changing Base URL

Default: `http://127.0.0.1:8000`

To change:
1. Click the **gear icon** next to the environment dropdown
2. Find `base_url` → edit value
3. Click **Save**

---

## Environment Variables

### Token Variables

| Variable | Type | Description |
|----------|------|-------------|
| `access_token` | secret | Generic Bearer token — set by any login |
| `dekan_token` | secret | Set when logging in as dekan |
| `kaprodi_token` | secret | Set when logging in as kaprodi |
| `mahasiswa_token` | secret | Set when logging in as mahasiswa |
| `role` | default | Auto-set: `dekan`, `kaprodi`, or `mahasiswa` |

### Data Variables

| Variable | Example | Description |
|----------|---------|-------------|
| `email` | `dekan@ews.com` | Login email. Pre-set to dekan. Change to test other roles |
| `password` | `password` | Password for all test accounts |
| `prodi_id` | `11` | Filter by prodi: 11 (A11), 12 (A12), 14 (A14), 15 (A15) |
| `tahun_masuk` | `2021` | Filter by admission year |
| `mahasiswa_id` | `1` | Student ID — set from list responses |
| `khs_krs_id` | `1` | KHS/KRS record ID |
| `tindak_lanjut_id` | `1` | Tindak lanjut record ID |

### Test Account Emails

```
Dekan:     dekan@ews.com
Kaprodi:   kaprodi_a11@ews.com, kaprodi_a12@ews.com, kaprodi_a14@ews.com, kaprodi_a15@ews.com
Mahasiswa: mahasiswa@ews.com
Password:  password (all accounts)
```

---

## Authentication

### Single Login Endpoint

**`POST /api/login`** — one endpoint for all roles.

```
Body:
{
  "email": "dekan@ews.com",
  "password": "password"
}
```

After login, the response script:
1. Sets `access_token` (generic)
2. Detects the user's role
3. Sets the appropriate role-specific token (`dekan_token`, `kaprodi_token`, or `mahasiswa_token`)
4. Sets `role`

### Token Injection

A **pre-request script** runs before every request and auto-injects `Authorization: Bearer {{token}}`. It checks role-specific tokens first, then falls back to `access_token`.

### Switching Roles

Just login with a different email — the new token overwrites the old one. Each role folder uses its own token variable (`{{dekan_token}}`, etc.) set in the request's `auth` block.

---

## Folder Structure

```
EWS API (Dekan + Kaprodi + Mahasiswa)
│
├── 🔐 Authentication
│   └── Login - POST /api/login        ← Single endpoint for all roles
│
├── 👔 Dekan (25 endpoints)
│   ├── 📊 Dashboard
│   ├── 🚦 Status EWS
│   ├── 👥 Mahasiswa
│   ├── 📋 Ringkasan
│   ├── 📈 Akademik
│   ├── 🎓 Kelulusan
│   └── 📋 Tindak Lanjut
│
├── 📋 Kaprodi (23 endpoints)
│   ├── 📊 Dashboard
│   ├── 🚦 Status EWS
│   ├── 👥 Mahasiswa
│   ├── 📋 Ringkasan
│   ├── 📈 Akademik
│   ├── 🎓 Kelulusan
│   └── 📋 Tindak Lanjut
│
└── 🎓 Mahasiswa (8 endpoints)
    ├── 📊 Dashboard
    ├── 📚 KHS / KRS
    ├── ⚠️ Peringatan
    └── 📋 Tindak Lanjut
```

---

## Role-Based Access

### Dekan (Fakultas-level)

- **Access**: All prodi within the faculty
- **Optional filter**: `?prodi_id=` query parameter
- **Cannot**: bulk-update tindak lanjut

### Kaprodi (Program Studi-level)

- **Access**: Students in their own prodi only
- **Cannot access**: other prodi's data
- **Can**: bulk-update tindak lanjut for their prodi

### Mahasiswa (Student)

- **Access**: Own data only
- **Can**: view warnings, submit tindak lanjut

---

## Endpoints Reference

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Universal login — sets role-specific token |

### Dekan Endpoints (25 total)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/ews/dekan/dashboard` | Dashboard overview |
| GET | `/api/ews/dekan/distribusi-status-ews` | EWS status distribution |
| GET | `/api/ews/dekan/status-mahasiswa` | Student status summary |
| POST | `/api/ews/dekan/recalculate-all-status` | Recalculate all student statuses |
| GET | `/api/ews/dekan/mahasiswa/all` | All students list |
| GET | `/api/ews/dekan/mahasiswa/all/export` | Export all students (CSV) |
| GET | `/api/ews/dekan/mahasiswa/mk-gagal` | Students with failed courses |
| GET | `/api/ews/dekan/mahasiswa/mk-gagal/export` | Export students with failed courses (CSV) |
| GET | `/api/ews/dekan/mahasiswa/detail-angkatan/{tahun}` | Detail by admission year |
| GET | `/api/ews/dekan/mahasiswa/detail-angkatan/{tahun}/export` | Export by admission year (CSV) |
| GET | `/api/ews/dekan/mahasiswa/detail/{id}` | Student detail |
| GET | `/api/ews/dekan/table-ringkasan-mahasiswa` | Student summary table |
| GET | `/api/ews/dekan/table-ringkasan-mahasiswa/export` | Export student summary (CSV) |
| GET | `/api/ews/dekan/table-ringkasan-status` | EWS status summary table |
| GET | `/api/ews/dekan/table-ringkasan-status/export` | Export EWS status summary (CSV) |
| GET | `/api/ews/dekan/top-mk-gagal` | Top failed courses |
| GET | `/api/ews/dekan/tren-ips/all` | IPS trend data |
| GET | `/api/ews/dekan/tren-ips/all/export` | Export IPS trend (CSV) |
| GET | `/api/ews/dekan/card-capaian` | Academic achievement cards |
| GET | `/api/ews/dekan/statistik-kelulusan` | Graduation statistics |
| GET | `/api/ews/dekan/table-statistik-kelulusan` | Detailed graduation table |
| GET | `/api/ews/dekan/tindak-lanjut` | Tindak lanjut list |
| GET | `/api/ews/dekan/tindak-lanjut/cards` | Tindak lanjut summary cards |
| GET | `/api/ews/dekan/tindak-lanjut/export` | Export tindak lanjut (CSV) |

### Kaprodi Endpoints (23 total)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/ews/kaprodi/dashboard` | Dashboard overview |
| GET | `/api/ews/kaprodi/distribusi-status-ews` | EWS status distribution |
| GET | `/api/ews/kaprodi/status-mahasiswa` | Student status summary |
| POST | `/api/ews/kaprodi/recalculate-all-status` | Recalculate all student statuses |
| GET | `/api/ews/kaprodi/mahasiswa/all` | All students list |
| GET | `/api/ews/kaprodi/mahasiswa/all/export` | Export all students (CSV) |
| GET | `/api/ews/kaprodi/mahasiswa/mk-gagal` | Students with failed courses |
| GET | `/api/ews/kaprodi/mahasiswa/detail-angkatan/{tahun}` | Detail by admission year |
| GET | `/api/ews/kaprodi/mahasiswa/detail/{id}` | Student detail |
| GET | `/api/ews/kaprodi/table-ringkasan-mahasiswa` | Student summary table |
| GET | `/api/ews/kaprodi/table-ringkasan-mahasiswa/export` | Export student summary (CSV) |
| GET | `/api/ews/kaprodi/table-ringkasan-status` | EWS status summary table |
| GET | `/api/ews/kaprodi/table-ringkasan-status/export` | Export EWS status summary (CSV) |
| GET | `/api/ews/kaprodi/top-mk-gagal` | Top failed courses |
| GET | `/api/ews/kaprodi/tren-ips/all` | IPS trend data |
| GET | `/api/ews/kaprodi/tren-ips/all/export` | Export IPS trend (CSV) |
| GET | `/api/ews/kaprodi/card-capaian` | Academic achievement cards |
| GET | `/api/ews/kaprodi/statistik-kelulusan` | Graduation statistics |
| GET | `/api/ews/kaprodi/table-statistik-kelulusan` | Detailed graduation table |
| GET | `/api/ews/kaprodi/tindak-lanjut` | Tindak lanjut list |
| GET | `/api/ews/kaprodi/tindak-lanjut/cards` | Tindak lanjut summary cards |
| GET | `/api/ews/kaprodi/tindak-lanjut/export` | Export tindak lanjut (CSV) |

### Mahasiswa Endpoints (8 total)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/ews/mahasiswa/dashboard` | Personal dashboard |
| GET | `/api/ews/mahasiswa/card-status-akademik` | Academic status cards |
| GET | `/api/ews/mahasiswa/khs-krs` | KHS/KRS records list |
| GET | `/api/ews/mahasiswa/khs-krs/export` | Export KHS/KRS (CSV) |
| GET | `/api/ews/mahasiswa/peringatan` | EWS warnings |
| GET | `/api/ews/mahasiswa/tindak-lanjut` | Own tindak lanjut history |
| GET | `/api/ews/mahasiswa/tindak-lanjut/cards` | Tindak lanjut summary cards |
| GET | `/api/ews/mahasiswa/tindak-lanjut/template/akademik` | Template for tindak lanjut |

### EWS Status Codes

| Code | Color | Meaning |
|------|-------|---------|
| MERAH | Red | Drop out risk — cannot graduate in 7 years |
| KUNING | Yellow | At risk — may graduate in 7 years |
| HIJAU | Green | On track — should graduate in 5 years |
| BIRU | Blue | Graduated or completed |

---

## Troubleshooting

### "401 Unauthorized" / "Unauthenticated"

Token missing or expired.
**Fix**: Run login again. Check that `access_token` is set in environment.

### "403 Forbidden"

Wrong role for this endpoint. Make sure you're logged in as the correct role.

### Empty Response / "No data"

1. No data matches filters — try empty `tahun_masuk` or `prodi_id`
2. Database not seeded
**Fix**: `php artisan db:seed`

### CSV Download Not Working

Postman may show JSON instead of downloading the file.
**Fix**: Check the Response panel for raw CSV text, or the Downloads tab.

### Variable `{{xxx}}` Not Replaced

1. Ensure "EWS - Local" environment is selected
2. Variable name is case-sensitive — check exact spelling
3. Variable is enabled in environment settings

### Connection Refused on localhost:8000

Laravel server not running.
**Fix**:
```bash
cd C:/Users/Jovan/.openclaw/workspace/projects/EWS/be_ews_r
php artisan serve
```

---

## Additional Resources

- Laravel Routes: `routes/api.php`
- EWS Logic: `EWS-LOGIC.md`
- Database ERD: `database-erd.dbml`
