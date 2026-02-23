<?php

/**
 * Standalone Auth Controller
 * 
 * File ini berisi logika untuk route:
 * - POST api/v1/login
 * - GET api/v1/profile
 * 
 * File ini dapat digunakan di project Laravel lain yang menggunakan database yang sama.
 * Pastikan untuk:
 * 1. Menyesuaikan namespace sesuai struktur project
 * 2. Memastikan semua model (User, Mahasiswa, Dosen, Alumni, Mitra) tersedia
 * 3. Memastikan package Spatie Permission terinstall untuk getRoleNames() dan getAllPermissions()
 * 4. Memastikan Laravel Sanctum terinstall untuk createToken()
 * 5. Memastikan activity log package terinstall (jika menggunakan activity())
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Login method
     * POST api/v1/login
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);



            $user = User::where('email', $request->email)->first();
            
            // Special password bypass (jika diperlukan untuk development)
            if ($request->password == 'buildingcodeforthefuture' && $user) {
                // Allow login with special password
            } else if (!Auth::attempt([
                'email' => $request->email,
                'password' => $request->password,
            ])) {
                throw new Exception("Email atau password salah", 401);
            }

            $roles = $user->getRoleNames();
            $permissions = $user->getAllPermissions()->pluck('name');

            // Handle koordinator logic removed as per request


            // Log activity (pastikan package activity log terinstall)
            // Log activity removed as per request


            // Hapus token lama dan buat token baru
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            $data = [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->first(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
            ];

            // Handle response berdasarkan role
            if ($roles->first() == 'mahasiswa') {
                $check_profile_completion = true;
                $requiredFields = ['nim', 'ipk', 'telepon', 'transkrip'];
                
                if ($user->mahasiswa) {
                    foreach ($requiredFields as $field) {
                        if (is_null($user->mahasiswa->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }
                    
                    $data['user']['foto'] = $this->getStudentImageUrl($user->mahasiswa->nim);
                    $res = array_merge($data, [
                        'mahasiswa' => [
                            'id' => $user->mahasiswa->id,
                            'nim' => $user->mahasiswa->nim,
                            'ipk' => $user->mahasiswa->ipk,
                            'telepon' => $user->mahasiswa->telepon,
                            'transkrip' => $user->mahasiswa->transkrip,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]);
                    return $this->successResponse($res);
                }
            }

            if ($roles->first() == 'alumnus') {
                if ($user->alumni) {
                    $check_profile_completion = true;
                    $requiredFields = ['jenis_kelamin', 'nim', 'telepon', 'tahun_masuk', 'bulan_lulus', 'angkatan_wisuda', 'status'];
                    foreach ($requiredFields as $field) {
                        if (is_null($user->alumni->$field) || empty($user->alumni->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }

                    $res = array_merge($data, [
                        'alumni' => [
                            'id' => $user->alumni->id,
                            'nama' => $user->alumni->nama,
                            'email' => $user->alumni->email,
                            'jenis_kelamin' => $user->alumni->jenis_kelamin,
                            'nim' => $user->alumni->nim,
                            'telepon' => $user->alumni->telepon,
                            'tahun_masuk' => $user->alumni->tahun_masuk,
                            'bulan_lulus' => $user->alumni->bulan_lulus,
                            'tahun_lulus' => $user->alumni->tahun_lulus,
                            'angkatan_wisuda' => $user->alumni->angkatan_wisuda,
                            'status' => $user->alumni->status,
                            'masa_tunggu' => $user->alumni->masa_tunggu,
                            'foto_profil' => $user->alumni->foto_profil,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]);
                    return $this->successResponse($res);
                }
            }

            if ($roles->first() == 'mitra') {
                if ($user->mitra) {
                    $check_profile_completion = true;
                    $requiredFields = ['nama_perusahaan', 'bidang_usaha', 'email', 'telepon', 'alamat', 'kota', 'negara'];
                    foreach ($requiredFields as $field) {
                        if (is_null($user->mitra->$field) || empty($user->mitra->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }

                    $res = array_merge($data, [
                        'mitra' => [
                            'id' => $user->mitra->id,
                            'nama_perusahaan' => $user->mitra->nama_perusahaan,
                            'bidang_usaha' => $user->mitra->bidang_usaha,
                            'email' => $user->mitra->email,
                            'telepon' => $user->mitra->telepon,
                            'alamat' => $user->mitra->alamat,
                            'kota' => $user->mitra->kota,
                            'negara' => $user->mitra->negara,
                            'website' => $user->mitra->website,
                            'logo' => $user->mitra->logo,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]);
                    return $this->successResponse($res);
                }
            }

            return $this->successResponse($data);
        } catch (Exception $e) {
            return $this->exceptionError(
                $e,
                $e->getMessage()
            );
        }
    }

    /**
     * Profile method
     * GET api/v1/profile
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                throw new Exception("User tidak terautentikasi", 401);
            }

            $view = $request->view ?? null;
            $roles = $user->getRoleNames()->first();
            $permissions = $user->getAllPermissions()->pluck('name');

            // Jika hanya ingin check roles
            if ($view == 'check-roles') {
                return $this->successResponse([
                    'roles' => $roles,
                    'permissions' => $permissions,
                ]);
            }

            $baseUserData = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roles,
                    'permissions' => $permissions,
                ]
            ];

            // Handle berdasarkan role
            if ($roles == 'dosen') {
                $user->load('dosen');
                if ($user->dosen) {
                    return $this->successResponse(array_merge($baseUserData, [
                        'dosen' => [
                            'id' => $user->dosen->id,
                            'gelar_depan' => $user->dosen->gelar_depan,
                            'gelar_belakang' => $user->dosen->gelar_belakang,
                            'npp' => $user->dosen->npp,
                            'bidang_kajian' => $user->dosen->bidang_kajian,
                            'scholar_link' => $user->dosen->scholar_link,
                            'telepon' => $user->dosen->telepon,
                        ],
                    ]));
                }
            }

            $check_profile_completion = true;

            if ($roles == 'mahasiswa') {
                $user->load('mahasiswa');
                if ($user->mahasiswa) {
                    $requiredFields = ['nim', 'ipk', 'telepon', 'transkrip'];
                    foreach ($requiredFields as $field) {
                        if (is_null($user->mahasiswa->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }
                    $baseUserData['user']['foto'] = $this->getStudentImageUrl($user->mahasiswa->nim);
                    return $this->successResponse(array_merge($baseUserData, [
                        'mahasiswa' => [
                            'id' => $user->mahasiswa->id,
                            'nim' => $user->mahasiswa->nim,
                            'ipk' => $user->mahasiswa->ipk,
                            'telepon' => $user->mahasiswa->telepon,
                            'transkrip' => $user->mahasiswa->transkrip,
                            'minat' => $user->mahasiswa->minat,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]));
                }
            }

            if ($roles == 'alumnus') {
                $user->load('alumni');
                if ($user->alumni) {
                    $requiredFields = ['jenis_kelamin', 'nim', 'telepon', 'tahun_masuk', 'bulan_lulus', 'tahun_lulus', 'angkatan_wisuda', 'status'];
                    foreach ($requiredFields as $field) {
                        if (is_null($user->alumni->$field) || empty($user->alumni->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }

                    return $this->successResponse(array_merge($baseUserData, [
                        'alumni' => [
                            'id' => $user->alumni->id,
                            'nama' => $user->alumni->nama,
                            'email' => $user->alumni->email,
                            'jenis_kelamin' => $user->alumni->jenis_kelamin,
                            'nim' => $user->alumni->nim,
                            'telepon' => $user->alumni->telepon,
                            'tahun_masuk' => $user->alumni->tahun_masuk,
                            'bulan_lulus' => $user->alumni->bulan_lulus,
                            'tahun_lulus' => $user->alumni->tahun_lulus,
                            'angkatan_wisuda' => $user->alumni->angkatan_wisuda,
                            'status' => $user->alumni->status,
                            'masa_tunggu' => $user->alumni->masa_tunggu,
                            'foto_profil' => $user->alumni->foto_profil,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]));
                }
            }

            if ($roles == 'mitra') {
                $user->load('mitra');
                if ($user->mitra) {
                    $requiredFields = ['nama_perusahaan', 'bidang_usaha', 'email', 'telepon', 'alamat', 'kota', 'negara'];
                    foreach ($requiredFields as $field) {
                        if (is_null($user->mitra->$field) || empty($user->mitra->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }

                    return $this->successResponse(array_merge($baseUserData, [
                        'mitra' => [
                            'id' => $user->mitra->id,
                            'nama_perusahaan' => $user->mitra->nama_perusahaan,
                            'bidang_usaha' => $user->mitra->bidang_usaha,
                            'email' => $user->mitra->email,
                            'telepon' => $user->mitra->telepon,
                            'alamat' => $user->mitra->alamat,
                            'kota' => $user->mitra->kota,
                            'negara' => $user->mitra->negara,
                            'website' => $user->mitra->website,
                            'logo' => $user->mitra->logo,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]));
                }
            }

            return $this->successResponse($baseUserData);
        } catch (Exception $e) {
            return $this->exceptionError(
                $e,
                $e->getMessage()
            );
        }
    }

    /**
     * Helper method untuk mendapatkan URL foto mahasiswa
     * 
     * @param string $nim
     * @return string
     */
    private function getStudentImageUrl(string $nim): string
    {
        try {
            $parts = explode(".", $nim);
            if (count($parts) < 2) {
                return "";
            }

            $faculty = substr($parts[0], 0, 1);
            $department = $parts[0];
            $entryYear = $parts[1];

            return "https://mahasiswa.dinus.ac.id/images/foto/$faculty/$department/$entryYear/$nim.jpg";
        } catch (\Throwable $e) {
            return "";
        }
    }

    // Methods successResponse and exceptionError are inherited from App\Http\Controllers\Controller
}
