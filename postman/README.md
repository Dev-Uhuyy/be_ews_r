# EWS Postman Collection

Postman collection and environment files for the EWS (Early Warning System) Laravel API.

## Files

| File | Description |
|------|-------------|
| `EWS.postman_collection.json` | Complete API collection with all endpoints |
| `EWS.postman_environment.json` | Environment variables for local development |
| `POSTMAN_GUIDE.md` | Comprehensive usage guide |
| `API_Documentation.md` | API endpoint documentation |

## Quick Start

1. Import both JSON files into Postman
2. Select **EWS - Local** environment
3. Run **Login Kaprodi (Default)** from the `00 - Auth` folder
4. Token auto-sets - start exploring endpoints!

For detailed instructions, see [POSTMAN_GUIDE.md](./POSTMAN_GUIDE.md).

## Roles

- **kaprodi**: Study program head - manages students in their prodi
- **dekan**: Faculty dean - oversight of all prodi
- **mahasiswa**: Student - view own data only

## Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Kaprodi A11 | kaprodi_a11@ews.com | password |
| Kaprodi A12 | kaprodi_a12@ews.com | password |
| Kaprodi A14 | kaprodi_a14@ews.com | password |
| Kaprodi A15 | kaprodi_a15@ews.com | password |
| Dekan | dekan@ews.com | password |
| Mahasiswa | mahasiswa@ews.com | password |

## Base URL

Default: `http://localhost:8000/api`

Change `base_url` in the environment to match your server.
