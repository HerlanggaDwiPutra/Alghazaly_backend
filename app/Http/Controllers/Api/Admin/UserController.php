<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola manajemen akun pengguna panel Admin dan konfigurasi peran (Role).
 *
 * Controller ini hanya dapat diakses oleh pengguna yang terautentikasi via
 * Sanctum (middleware `auth:sanctum`) dan secara konvensional hanya boleh
 * digunakan oleh Super Admin. Pembatasan peran berbasis role_id sebaiknya
 * diimplementasikan via Laravel Policy atau Gate jika diperlukan kontrol
 * yang lebih granular di masa depan.
 *
 * @see \App\Models\User
 * @see \App\Models\Role
 */
class UserController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengguna beserta nama perannya.
     *
     * Kolom yang dikembalikan dibatasi untuk menghindari eksposur data sensitif
     * (terutama `password` yang sudah di-hide di model, namun field lain tetap perlu dibatasi).
     *
     * @return \Illuminate\Http\JsonResponse  Daftar pengguna diurutkan berdasarkan nama.
     */
    public function index(): JsonResponse
    {
        return response()->json(User::with('role:role_id,name')->orderBy('name')->get(['id', 'name', 'email', 'role_id', 'is_active', 'created_at']));
    }

    /**
     * Membuat akun pengguna baru di panel Admin.
     *
     * Password di-hash secara otomatis oleh cast `hashed` yang dikonfigurasi
     * pada model {@see \App\Models\User::casts()}, sehingga tidak perlu
     * memanggil `bcrypt()` atau `Hash::make()` secara eksplisit.
     *
     * @param  \Illuminate\Http\Request  $request  Data pengguna baru (name, email, password, role_id, is_active).
     * @return \Illuminate\Http\JsonResponse         Data pengguna yang dibuat beserta role-nya (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika email sudah terdaftar atau role_id tidak valid.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'role_id'   => 'required|exists:roles,role_id',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            ...$request->only(['name', 'email', 'role_id', 'is_active']),
            'password' => $request->password,
        ]);

        return response()->json($user->load('role'), 201);
    }

    /**
     * Menampilkan detail lengkap satu pengguna beserta perannya.
     *
     * @param  int  $id  Primary key dari tabel `users`.
     * @return \Illuminate\Http\JsonResponse  Detail pengguna, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(User::with('role')->findOrFail($id));
    }

    /**
     * Memperbarui data akun pengguna yang sudah ada.
     *
     * Password hanya diperbarui jika field `password` diisi dalam request
     * (menggunakan `filled()` bukan `has()` untuk mengabaikan string kosong).
     * Validasi email menggunakan pengecualian ID saat ini untuk mencegah
     * konflik unik dengan dirinya sendiri saat email tidak berubah.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang ingin diperbarui (semua opsional kecuali validasi).
     * @param  int                       $id       Primary key pengguna yang akan diperbarui.
     * @return \Illuminate\Http\JsonResponse        Data pengguna yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika pengguna tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => "sometimes|email|unique:users,email,{$id}",
            'password'  => 'nullable|string|min:8',
            'role_id'   => 'sometimes|exists:roles,role_id',
            'is_active' => 'boolean',
        ]);

        $data = $request->only(['name', 'email', 'role_id', 'is_active']);
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return response()->json($user->load('role'));
    }

    /**
     * Menghapus akun pengguna secara permanen.
     *
     * Terdapat guard keamanan: admin tidak dapat menghapus akun miliknya sendiri
     * untuk mencegah hilangnya akses ke sistem secara tidak sengaja.
     *
     * @param  int  $id  Primary key pengguna yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 403 jika mencoba menghapus diri sendiri.
     */
    public function destroy(int $id): JsonResponse
    {
        // Cegah admin menghapus akun aktifnya sendiri untuk menghindari lockout sistem.
        if ($id === request()->user()->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 403);
        }

        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User dihapus.']);
    }

    /**
     * Mengembalikan seluruh daftar peran yang tersedia di sistem.
     *
     * Digunakan sebagai data referensi untuk dropdown pemilihan peran
     * pada form tambah/edit pengguna di frontend panel admin.
     *
     * @return \Illuminate\Http\JsonResponse  Seluruh data dari tabel `roles`.
     */
    public function roles(): JsonResponse
    {
        return response()->json(Role::all());
    }
}
