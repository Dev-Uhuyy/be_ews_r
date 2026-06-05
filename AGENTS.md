# AGENTS.md

## Stack
- Laravel 12 + PHP 8.2+
- Sanctum auth via custom `sti_api_token` middleware (NOT `auth:sanctum`)
- Spatie `laravel-permission` for roles
- PhpOffice\PhpSpreadsheet for XLSX exports (via custom `ExportFormatterTrait`)
- Scramble for API doc generation
- Vite + TailwindCSS for frontend asset pipeline

## Key Commands
```bash
# Setup (run once)
composer setup   # install + .env + migrate + npm install + build

# Dev (4 parallel processes: server, queue, logs, vite)
composer dev

# Test
composer test    # clears config cache first, then artisan test

# Lint/format
./vendor/bin/pint
```

## Architecture

### Roles & Route Prefix
| Role | Prefix | Scope |
|------|--------|-------|
| super_fakultas | `/api/ews/super-fakultas` | All prodi in fakultas |
| admin | `/api/ews/admin` | Own prodi only |
| koor | `/api/ews/koor` | Own prodi only (recalculate, surat rekomitmen) |
| dosen | `/api/ews/dosen` | Own mahasiswa Wali only |
| mahasiswa | `/api/ews/mahasiswa` | Own data only |

### Login Endpoints (public)
```
POST /api/login-admin
POST /api/login-super-fakultas
POST /api/login-mahasiswa
```
All return `{ access_token, user, role_specific_data }`. Token passed as `Authorization: Bearer {token}`.

### Important Service Files
- `app/Services/SuperFakultas/EwsService.php` — Core EWS status calculation logic
- `app/Services/Admin/EwsService.php` — Admin-specific EWS logic

> **Note:** Batch job `app/Jobs/RecalculateAllEwsJob.php` SUDAH ADA (dipakai endpoint `recalculate-all-status`, dispatch ke queue, chunk 100). Folder `app/Observers/` masih TIDAK ADA — auto-recalc observer belum diimplementasikan (recalc per-mahasiswa hanya lewat endpoint manual `recalculate-status`).

### EWS Status Logic (critical)
EWS status calculation is documented in `EWS-LOGIC.md`. Key rules:
- Only **latest value per mata kuliah** (MAX id) counts — retake results replace older ones
- `nilai_d_melebihi_batas = 'yes'` if: >2 MK with D OR total SKS D > 7.2 (5% of 144)
- Students with `status_mahasiswa IN ('Lulus', 'DO')` are **excluded from all calculations**
- SPS1/SPS2/SPS3 triggered when IPS semester N < 2.0
- SPS3 requires rekomitmen (surat rekomitmen)

### Export Pattern
All export controllers return XLSX files via Maatwebsite Excel. Services live under `app/Services/{Role}/Export/`.

### Custom Middleware
- `sti_api_token` — validates Bearer token (token from external sti-api system)

## Existing Docs (read these first)
- `EWS-LOGIC.md` — full business logic for EWS status + kelulusan
- `ROLE-*.md` — role-specific endpoint documentation
- `api_documentation.md` — complete API reference for frontend integration

## Testing
- Minimal: only `tests/Unit/ExampleTest.php` exists
- Run with `composer test`

## Database
- SQLite default (`database/database.sqlite`)
- Migrations in `database/migrations/` (prefixed `2026_01_06_...`)
- ERD reference: `database-erd.dbml`
