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

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Login Kaprodi
     *
     * Endpoint asisten autentikasi untuk Login sebagai Kaprodi. Default Kaprodi test acc:
     * - Email: kaprodi_a11@ews.com
     * - Password: password
     * 
     * @tags Auth
     * @unauthenticated
     */
    public function loginKaprodi(Request $request) 
    { 
        $request->validate(['email' => 'required|string|email', 'password' => 'required|string']);
        return $this->login($request); 
    }

    /**
     * Login Dekan
     *
     * Endpoint asisten autentikasi untuk Login sebagai Dekan. Default Dekan test acc:
     * - Email: dekan@ews.com
     * - Password: password
     * 
     * @tags Auth
     * @unauthenticated
     */
    public function loginDekan(Request $request) 
    { 
        $request->validate(['email' => 'required|string|email', 'password' => 'required|string']);
        return $this->login($request); 
    }

    /**
     * Login Mahasiswa
     *
     * Endpoint asisten autentikasi untuk Login sebagai Mahasiswa. Default Mhs test acc:
     * - Email: dummy_A11_mhs1@ews.com (atau sesuaikan)
     * - Password: password
     * 
     * @tags Auth
     * @unauthenticated
     */
    public function loginMahasiswa(Request $request) 
    { 
        $request->validate(['email' => 'required|string|email', 'password' => 'required|string']);
        return $this->login($request); 
    }

    /**
     * Login General
     *
     * Login utama.
     *
     * **🎉 Auto Token Injection - Seperti Postman!**
     *
     * Setelah login berhasil, token ter-inject ke semua request berkat session memory browser.
     * 
     * @tags Auth
     * @unauthenticated
     *
     * @param Request $request
     * @bodyParam email string required Email user terdaftar
     * @bodyParam password string required Password user terdaftar
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

            // Handle response berdasarkan role EWS
            if ($roles->first() == 'mahasiswa') {
                $check_profile_completion = true;
                $requiredFields = ['nim', 'telepon', 'transkrip'];

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
                            'id'           => $user->mahasiswa->id,
                            'nim'          => $user->mahasiswa->nim,
                            'telepon'      => $user->mahasiswa->telepon,
                            'transkrip'    => $user->mahasiswa->transkrip,
                            'minat'        => $user->mahasiswa->minat,
                            'prodi'        => $user->prodi?->nama,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]);
                    return $this->successResponse($res);
                }
            }

            if ($roles->first() == 'kaprodi') {
                $user->load('dosen.prodi', 'prodi');
                $res = array_merge($data, [
                    'kaprodi' => [
                        'prodi_id'  => $user->prodi_id,
                        'prodi'     => $user->prodi?->nama,
                        'kode_prodi'=> $user->prodi?->kode_prodi,
                    ],
                ]);
                return $this->successResponse($res);
            }

            if ($roles->first() == 'dekan') {
                $user->load('prodi');
                $res = array_merge($data, [
                    'dekan' => [
                        'scope' => 'fakultas', // dekan lihat semua prodi
                    ],
                ]);
                return $this->successResponse($res);
            }

            // Role lain (alumnus, mitra, dll) tidak dalam scope EWS
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
                $user->load('mahasiswa.akademikMahasiswa');
                if ($user->mahasiswa) {
                    // ipk sudah dipindah ke tabel akademik_mahasiswa oleh migration EWS
                    $requiredFields = ['nim', 'telepon', 'transkrip'];
                    foreach ($requiredFields as $field) {
                        if (is_null($user->mahasiswa->$field)) {
                            $check_profile_completion = false;
                            break;
                        }
                    }
                    $baseUserData['user']['foto'] = $this->getStudentImageUrl($user->mahasiswa->nim);
                    return $this->successResponse(array_merge($baseUserData, [
                        'mahasiswa' => [
                            'id'           => $user->mahasiswa->id,
                            'nim'          => $user->mahasiswa->nim,
                            'ipk'          => $user->mahasiswa->akademikMahasiswa?->ipk,
                            'telepon'      => $user->mahasiswa->telepon,
                            'transkrip'    => $user->mahasiswa->transkrip,
                            'minat'        => $user->mahasiswa->minat,
                            'semester_aktif' => $user->mahasiswa->akademikMahasiswa?->semester_aktif,
                            'is_completed' => $check_profile_completion,
                        ],
                    ]));
                }
            }

            // alumnus / mitra tidak dalam scope EWS — kembalikan data dasar
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
