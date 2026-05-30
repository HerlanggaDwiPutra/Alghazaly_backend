<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Mengelola autentikasi pengguna menggunakan Laravel Sanctum (token-based).
 *
 * Sistem autentikasi ini menggunakan Sanctum Personal Access Token, bukan
 * session cookie, sehingga cocok untuk arsitektur API stateless yang dikonsumsi
 * oleh aplikasi frontend terpisah (SPA/mobile).
 *
 * Kontrol akses berbasis peran (RBAC) diimplementasikan melalui field `role_id`
 * pada model User; pembatasan endpoint per-peran dikonfigurasi di middleware
 * atau langsung di controller admin masing-masing.
 *
 * @see \App\Models\User
 * @see \App\Models\Role
 */
class AuthController extends Controller
{
    /**
     * Memverifikasi kredensial pengguna dan mengeluarkan Personal Access Token.
     *
     * Terdapat dua lapisan validasi sebelum token diterbitkan:
     * 1. Kecocokan email dan password (via bcrypt `Hash::check`).
     * 2. Status akun aktif (`is_active = 1`); pengguna yang dinonaktifkan
     *    admin tidak dapat login meskipun passwordnya benar.
     *
     * Token yang dihasilkan (`api-token`) tidak memiliki waktu kedaluwarsa
     * default; logout manual diperlukan untuk mencabutnya.
     *
     * @param  \Illuminate\Http\Request  $request  Kredensial login (email, password).
     * @return \Illuminate\Http\JsonResponse         Token akses dan data ringkas pengguna, atau 401 jika gagal.
     *
     * @throws \Illuminate\Validation\ValidationException  Jika format email atau password tidak valid.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Query menggabungkan filter email dan is_active dalam satu roundtrip database
        // untuk menghindari pengungkapan status akun melalui pesan error yang berbeda.
        $user = User::where('email', $request->email)->where('is_active', 1)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->only('id', 'name', 'email', 'role_id'),
        ]);
    }

    /**
     * Mencabut token akses yang sedang aktif (logout dari sesi saat ini saja).
     *
     * Hanya token yang digunakan dalam request ini yang dihapus. Token lain
     * milik pengguna yang sama (misalnya dari perangkat berbeda) tetap valid.
     * Gunakan `$user->tokens()->delete()` untuk mencabut semua token sekaligus.
     *
     * @param  \Illuminate\Http\Request  $request  Request terautentikasi (memerlukan Bearer token).
     * @return \Illuminate\Http\JsonResponse         Konfirmasi logout berhasil.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    /**
     * Mengembalikan profil pengguna yang sedang terautentikasi beserta perannya.
     *
     * Digunakan oleh frontend untuk menentukan hak akses menu dan fitur
     * berdasarkan data `role` yang dimuat via eager loading.
     *
     * @param  \Illuminate\Http\Request  $request  Request terautentikasi.
     * @return \Illuminate\Http\JsonResponse         Data profil pengguna beserta objek `role`.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role'));
    }
}
