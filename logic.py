def hitung_sks_maks_bisa_diambil(semester_sekarang: int, semester_target: int) -> int:
    """
    Menghitung total SKS maksimal yang dapat diambil oleh mahasiswa
    mulai dari semester_sekarang hingga semester_target.
    """
    if semester_sekarang > semester_target:
        return 0

    total_sks_bisa_diambil = 0
    for smt in range(semester_sekarang, semester_target + 1):
        if smt <= 10:
            total_sks_bisa_diambil += 20
        else:
            total_sks_bisa_diambil += 24
            
    return total_sks_bisa_diambil

def cek_status_kelulusan(
    sks_lulus: int,
    jumlah_nilai_e: int,
    jumlah_nilai_d: int,
    semester_saat_ini: int,
    status_done_nfu_ganjil: bool,
    status_done_nfu_genap: bool,
    ada_ED_matkul_ganjil: bool,
    ada_ED_matkul_genap: bool
) -> str:
    """
    Menentukan status kelulusan mahasiswa (MERAH, KUNING, HIJAU, BIRU)
    berdasarkan logika yang ditentukan.
    """
    
    # 1. Hitung variabel turunan
    sisa_sks = 144 - sks_lulus
    is_genap = (semester_saat_ini % 2 == 0)
    is_ganjil = not is_genap

    # 2. Hitung batas SKS yang bisa diambil dari semester ini
    sks_bisa_diambil_sd_14 = hitung_sks_maks_bisa_diambil(semester_saat_ini, 14)
    sks_bisa_diambil_sd_10 = hitung_sks_maks_bisa_diambil(semester_saat_ini, 10)
    sks_bisa_diambil_sd_8 = hitung_sks_maks_bisa_diambil(semester_saat_ini, 8)

    # 3. Implementasi Logika (Prioritas: MERAH -> KUNING -> HIJAU -> BIRU)

    # --- LOGIKA MERAH (DROP OUT) ---
    status_merah = False
    
    # Kondisi SKS Merah (berlaku kapan saja)
    if sisa_sks > sks_bisa_diambil_sd_14:
        status_merah = True 

    # Kondisi Nilai/NFU Merah (hanya berlaku di semester 13 & 14)
    if is_ganjil and semester_saat_ini == 13:
        # DIPERBAIKI: Hapus cek global 'jumlah_nilai_e/d'
        # Hanya cek NFU Ganjil atau E/D di matkul Ganjil
        if (not status_done_nfu_ganjil) or ada_ED_matkul_ganjil:
            status_merah = True
    elif is_genap and semester_saat_ini == 14:
        # DIPERBAIKI: Hapus cek global 'jumlah_nilai_e/d'
        # Hanya cek NFU Genap atau E/D di matkul Genap
        if (not status_done_nfu_genap) or ada_ED_matkul_genap:
            status_merah = True

    if status_merah:
        return "MERAH"

    # --- LOGIKA KUNING (LULUS 7 TAHUN) ---
    status_kuning = False
    
    # Kondisi SKS Kuning (berlaku kapan saja)
    if sisa_sks > sks_bisa_diambil_sd_10:
        status_kuning = True

    # Kondisi Nilai/NFU Kuning (hanya berlaku di semester 9 & 10)
    if is_ganjil and semester_saat_ini == 9:
        # DIPERBAIKI: Hapus cek global 'jumlah_nilai_e/d'
        if (not status_done_nfu_ganjil) or ada_ED_matkul_ganjil:
            status_kuning = True
    elif is_genap and semester_saat_ini == 10:
        # DIPERBAIKI: Hapus cek global 'jumlah_nilai_e/d'
        if (not status_done_nfu_genap) or ada_ED_matkul_genap:
            status_kuning = True
            
    if status_kuning:
        return "KUNING"

    # --- LOGIKA HIJAU (LULUS 5 TAHUN) ---
    status_hijau = False
    
    # Kondisi SKS Hijau (berlaku kapan saja)
    if sisa_sks > sks_bisa_diambil_sd_8:
        status_hijau = True
        
    # Kondisi Nilai/NFU Hijau (hanya berlaku di semester 7 & 8)
    if is_ganjil and semester_saat_ini == 7:
        # DIPERBAIKI: Hapus cek global 'jumlah_nilai_e/d'
        if (not status_done_nfu_ganjil) or ada_ED_matkul_ganjil:
            status_hijau = True
    elif is_genap and semester_saat_ini == 8:
        # DIPERBAIKI: Hapus cek global 'jumlah_nilai_e/d'
        if (not status_done_nfu_genap) or ada_ED_matkul_genap:
            status_hijau = True

    if status_hijau:
        return "HIJAU"

    # --- LOGIKA BIRU (LULUS 4 TAHUN) ---
    # Logika Biru TEPAT. Menggunakan total 'jumlah_nilai_e/d' 
    # untuk syarat lulus cepat.
    
    kondisi_sks_biru = (sisa_sks <= sks_bisa_diambil_sd_8)
    
    if is_ganjil and semester_saat_ini == 7:
        if kondisi_sks_biru and (jumlah_nilai_e <= 0) and (jumlah_nilai_d <= 1) and status_done_nfu_ganjil:
            return "BIRU"
            
    elif is_genap and semester_saat_ini == 8:
        # (Masih mengikuti permintaan Anda bahwa SMT 8 mengecek NFU Ganjil)
        if kondisi_sks_biru and (jumlah_nilai_e <= 0) and (jumlah_nilai_d <= 1) and status_done_nfu_ganjil:
            return "BIRU"

    # --- DEFAULT ---
    return "NORMAL"

# -----------------------------------------------------------------
# --- SEMUA SKENARIO PENGUJIAN ---
# -----------------------------------------------------------------
print("--- 🚦 PENGUJIAN STATUS MERAH (PRIORITAS 1) ---")

# 1. MERAH karena SKS kurang (di SMT 13)
# Sisa SKS: 49. SKS maks s.d. SMT 14 (dari SMT 13): 24+24 = 48. (49 > 48)
mhs_skenario_1 = cek_status_kelulusan(
    sks_lulus=95, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=13,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"1. MERAH (SKS): {mhs_skenario_1}")

# 2. MERAH karena NFU Ganjil (di SMT 13)
# SKS aman (Sisa 14), tapi NFU Ganjil = False
mhs_skenario_2 = cek_status_kelulusan(
    sks_lulus=130, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=13,
    status_done_nfu_ganjil=False, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"2. MERAH (NFU Ganjil): {mhs_skenario_2}")

# 3. MERAH karena E/D Ganjil (di SMT 13)
# SKS aman (Sisa 34), NFU aman, tapi ada E/D di matkul Ganjil
mhs_skenario_3 = cek_status_kelulusan(
    sks_lulus=110, jumlah_nilai_e=1, jumlah_nilai_d=0, semester_saat_ini=13,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=True, ada_ED_matkul_genap=False
)
print(f"3. MERAH (E/D Ganjil): {mhs_skenario_3}")

# 4. MERAH karena NFU Genap (di SMT 14)
# SKS aman (Sisa 14), tapi NFU Genap = False
mhs_skenario_4 = cek_status_kelulusan(
    sks_lulus=130, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=14,
    status_done_nfu_ganjil=True, status_done_nfu_genap=False,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"4. MERAH (NFU Genap): {mhs_skenario_4}")

# 5. MERAH karena E/D Genap (di SMT 14)
# SKS aman (Sisa 14), NFU aman, tapi ada E/D di matkul Genap
mhs_skenario_5 = cek_status_kelulusan(
    sks_lulus=130, jumlah_nilai_e=1, jumlah_nilai_d=0, semester_saat_ini=14,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=True
)
print(f"5. MERAH (E/D Genap): {mhs_skenario_5}")

print("\n--- 🟡 PENGUJIAN STATUS KUNING (PRIORITAS 2) ---")

# 6. KUNING karena SKS kurang (di SMT 9)
# Sisa SKS: 41. SKS maks s.d. SMT 10 (dari SMT 9): 20+20 = 40. (41 > 40)
mhs_skenario_6 = cek_status_kelulusan(
    sks_lulus=103, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=9,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"6. KUNING (SKS): {mhs_skenario_6}")

# 7. KUNING karena NFU Ganjil (di SMT 9)
# SKS aman (Sisa 24), tapi NFU Ganjil = False
mhs_skenario_7 = cek_status_kelulusan(
    sks_lulus=120, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=9,
    status_done_nfu_ganjil=False, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"7. KUNING (NFU Ganjil): {mhs_skenario_7}")

# 8. KUNING karena E/D Ganjil (di SMT 9)
# SKS aman (Sisa 24), NFU aman, tapi ada E/D di matkul Ganjil
mhs_skenario_8 = cek_status_kelulusan(
    sks_lulus=120, jumlah_nilai_e=1, jumlah_nilai_d=2, semester_saat_ini=9,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=True, ada_ED_matkul_genap=False
)
print(f"8. KUNING (E/D Ganjil): {mhs_skenario_8}")

# 9. KUNING karena NFU Genap (di SMT 10)
# SKS aman (Sisa 24), tapi NFU Genap = False
mhs_skenario_9 = cek_status_kelulusan(
    sks_lulus=120, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=10,
    status_done_nfu_ganjil=True, status_done_nfu_genap=False,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"9. KUNING (NFU Genap): {mhs_skenario_9}")

# 10. KUNING karena E/D Genap (di SMT 10)
# SKS aman (Sisa 24), NFU aman, tapi ada E/D di matkul Genap
mhs_skenario_10 = cek_status_kelulusan(
    sks_lulus=120, jumlah_nilai_e=1, jumlah_nilai_d=0, semester_saat_ini=10,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=True
)
print(f"10. KUNING (E/D Genap): {mhs_skenario_10}")

print("\n--- 🟢 PENGUJIAN STATUS HIJAU (PRIORITAS 3) ---")

# 11. HIJAU karena SKS kurang (di SMT 7)
# Sisa SKS: 41. SKS maks s.d. SMT 8 (dari SMT 7): 20+20 = 40. (41 > 40)
mhs_skenario_11 = cek_status_kelulusan(
    sks_lulus=103, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=7,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"11. HIJAU (SKS): {mhs_skenario_11}")

# 12. HIJAU karena NFU Ganjil (di SMT 7)
# SKS aman (Sisa 40), tapi NFU Ganjil = False
mhs_skenario_12 = cek_status_kelulusan(
    sks_lulus=104, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=7,
    status_done_nfu_ganjil=False, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"12. HIJAU (NFU Ganjil): {mhs_skenario_12}")

# 13. HIJAU karena E/D Ganjil (di SMT 7)
# SKS aman (Sisa 40), NFU aman, tapi ada E/D di matkul Ganjil
mhs_skenario_13 = cek_status_kelulusan(
    sks_lulus=104, jumlah_nilai_e=0, jumlah_nilai_d=2, semester_saat_ini=7,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=True, ada_ED_matkul_genap=False
)
print(f"13. HIJAU (E/D Ganjil): {mhs_skenario_13}")

# 14. HIJAU karena NFU Genap (di SMT 8)
# SKS aman (Sisa 20), tapi NFU Genap = False
mhs_skenario_14 = cek_status_kelulusan(
    sks_lulus=124, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=8,
    status_done_nfu_ganjil=True, status_done_nfu_genap=False,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"14. HIJAU (NFU Genap): {mhs_skenario_14}")

# 15. HIJAU karena E/D Genap (di SMT 8)
# SKS aman (Sisa 20), NFU aman, tapi ada E/D di matkul Genap
mhs_skenario_15 = cek_status_kelulusan(
    sks_lulus=124, jumlah_nilai_e=1, jumlah_nilai_d=0, semester_saat_ini=8,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=True
)
print(f"15. HIJAU (E/D Genap): {mhs_skenario_15}")

print("\n--- 🔵 PENGUJIAN STATUS BIRU (PRIORITAS 4) ---")

# 16. BIRU (di SMT 7)
# SKS aman (Sisa 40 <= 40), Total E=0, Total D=1, NFU Ganjil = True
mhs_skenario_16 = cek_status_kelulusan(
    sks_lulus=104, jumlah_nilai_e=0, jumlah_nilai_d=1, semester_saat_ini=7,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"16. BIRU (SMT 7): {mhs_skenario_16}")

# 17. BIRU (di SMT 8)
# SKS aman (Sisa 20 <= 20), Total E=0, Total D=0, NFU Ganjil = True
mhs_skenario_17 = cek_status_kelulusan(
    sks_lulus=124, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=8,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"17. BIRU (SMT 8): {mhs_skenario_17}")

print("\n--- ☑️ PENGUJIAN STATUS NORMAL (DEFAULT) ---")

# 18. NORMAL (di SMT 5)
# Mahasiswa semester awal, SKS aman, tidak dalam semester evaluasi
mhs_skenario_18 = cek_status_kelulusan(
    sks_lulus=80, jumlah_nilai_e=0, jumlah_nilai_d=0, semester_saat_ini=5,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"18. NORMAL (SMT 5): {mhs_skenario_18}")

# 19. NORMAL (Gagal BIRU di SMT 7)
# SKS aman (Sisa 40), NFU Ganjil aman, tapi Total D >= 2
# Tidak trigger HIJAU (karena ada_ED_matkul_ganjil = False)
# Tidak trigger BIRU (karena jumlah_nilai_d > 1)
mhs_skenario_19 = cek_status_kelulusan(
    sks_lulus=104, jumlah_nilai_e=0, jumlah_nilai_d=2, semester_saat_ini=7,
    status_done_nfu_ganjil=True, status_done_nfu_genap=True,
    ada_ED_matkul_ganjil=False, ada_ED_matkul_genap=False
)
print(f"19. NORMAL (Gagal BIRU): {mhs_skenario_19}")