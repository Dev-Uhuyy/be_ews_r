## Postman (EWS)

### File yang tersedia
- `EWS.postman_collection.json`: Collection lengkap (Auth, Kaprodi, Dekan, Mahasiswa)
- `EWS.postman_environment.json`: Environment default untuk local

### Cara import
Di Postman:
- **Import** → pilih `EWS.postman_collection.json`
- **Import** → pilih `EWS.postman_environment.json`
- Pilih environment **EWS - Local**

### Cara pakai cepat
- Jalankan salah satu request login:
  - `00 - Auth` → `Login Kaprodi` / `Login Dekan` / `Login Mahasiswa`
  - Alternatif cepat: `00 - Auth` → `Login Kaprodi (A11/A12/A14/A15)`
- Token akan otomatis tersimpan ke env variable `access_token`
- Request lain akan otomatis menggunakan `Authorization: Bearer {{access_token}}`

### Variabel environment penting
- `base_url`: default `http://localhost:8000`
- `api_base`: turunan `{{base_url}}/api`
- `access_token`: hasil login (auto-set)
- `email_kaprodi_a11`, `email_kaprodi_a12`, `email_kaprodi_a14`, `email_kaprodi_a15`: akun Kaprodi test (ubah sesuai seed kamu)
- `prodi_id`: opsional untuk mode Dekan (filter prodi spesifik)
- `mahasiswa_id`, `tahun_masuk`, `khs_krs_id`, `tindak_lanjut_id`: isi sesuai data kamu

