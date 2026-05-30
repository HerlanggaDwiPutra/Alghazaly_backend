<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola data profil guru/tenaga pengajar untuk halaman profil sekolah.
 *
 * Data guru menampilkan direktori staf pengajar yang dapat diurutkan secara
 * manual melalui field `order`. Kolom `is_active` mengontrol visibilitas
 * profil di halaman publik tanpa perlu menghapus data.
 *
 * Field `photo` menyimpan path atau URL ke aset gambar; pengelolaan file
 * fisiknya dilakukan melalui {@see \App\Http\Controllers\Api\Admin\MediaController}.
 *
 * @see \App\Models\Teacher
 */
class TeacherController extends Controller
{
    /**
     * Menampilkan seluruh daftar guru diurutkan berdasarkan field `order`.
     *
     * Urutan tampil dikontrol manual oleh admin melalui field `order` (integer)
     * bukan berdasarkan waktu input. Nilai `order` yang lebih kecil tampil lebih dulu.
     *
     * @return \Illuminate\Http\JsonResponse  Seluruh data guru terurut.
     */
    public function index(): JsonResponse
    {
        return response()->json(Teacher::orderBy('order')->get());
    }

    /**
     * Menambahkan profil guru baru ke direktori.
     *
     * @param  \Illuminate\Http\Request  $request  Data profil guru baru.
     * @return \Illuminate\Http\JsonResponse         Data guru yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'position'  => 'required|string|max:100',
            'subject'   => 'nullable|string|max:100',
            'photo'     => 'nullable|string|max:255',
            'bio'       => 'nullable|string',
            'order'     => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $teacher = Teacher::create($request->only(['name', 'position', 'subject', 'photo', 'bio', 'order', 'is_active']));

        return response()->json($teacher, 201);
    }

    /**
     * Menampilkan detail profil satu guru.
     *
     * @param  int  $id  Primary key dari tabel `teachers` (teacher_id).
     * @return \Illuminate\Http\JsonResponse  Detail profil guru, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Teacher::findOrFail($id));
    }

    /**
     * Memperbarui data profil guru yang sudah ada secara parsial.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang ingin diperbarui.
     * @param  int                       $id       Primary key guru yang akan diperbarui.
     * @return \Illuminate\Http\JsonResponse        Data guru yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika guru tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = Teacher::findOrFail($id);

        $request->validate([
            'name'      => 'sometimes|string|max:100',
            'position'  => 'sometimes|string|max:100',
            'subject'   => 'nullable|string|max:100',
            'photo'     => 'nullable|string|max:255',
            'bio'       => 'nullable|string',
            'order'     => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $teacher->update($request->only(['name', 'position', 'subject', 'photo', 'bio', 'order', 'is_active']));

        return response()->json($teacher);
    }

    /**
     * Menghapus profil guru secara permanen dari direktori.
     *
     * File foto fisik yang terhubung tidak ikut dihapus oleh operasi ini.
     * Penghapusan aset media dilakukan secara terpisah melalui MediaController
     * untuk menghindari efek samping yang tidak diinginkan.
     *
     * @param  int  $id  Primary key guru yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Teacher::findOrFail($id)->delete();
        return response()->json(['message' => 'Data guru dihapus.']);
    }
}
