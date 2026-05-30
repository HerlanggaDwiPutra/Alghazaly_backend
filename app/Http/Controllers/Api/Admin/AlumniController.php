<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola data profil alumni sekolah untuk halaman galeri alumni.
 *
 * Data alumni merepresentasikan rekam jejak lulusan Al Ghazaly yang berprestasi.
 * Field `is_published` mengontrol visibilitas profil di halaman publik;
 * profil yang belum dipublikasikan hanya terlihat di panel admin.
 *
 * @see \App\Models\Alumni
 */
class AlumniController extends Controller
{
    /**
     * Menampilkan daftar alumni dengan filter tahun kelulusan dan paginasi.
     *
     * Parameter `year` pada query string menyaring berdasarkan `graduation_year`.
     * Daftar diurutkan dari tahun kelulusan terbaru ke terlama.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (year).
     * @return \Illuminate\Http\JsonResponse         Daftar alumni terpaginasi (15 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = Alumni::when($request->year, fn ($q) => $q->where('graduation_year', $request->year))
            ->orderByDesc('graduation_year')
            ->paginate(15);

        return response()->json($alumni);
    }

    /**
     * Menambahkan profil alumni baru ke database.
     *
     * @param  \Illuminate\Http\Request  $request  Data profil alumni baru.
     * @return \Illuminate\Http\JsonResponse         Data alumni yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                => 'required|string|max:100',
            'graduation_year'     => 'required|integer|digits:4',
            'photo'               => 'nullable|string|max:255',
            'current_institution' => 'nullable|string|max:150',
            'major'               => 'nullable|string|max:100',
            'achievement'         => 'nullable|string',
            'is_published'        => 'boolean',
        ]);

        return response()->json(Alumni::create($request->only(['name', 'graduation_year', 'photo', 'current_institution', 'major', 'achievement', 'is_published'])), 201);
    }

    /**
     * Memperbarui data profil alumni secara parsial.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang ingin diperbarui.
     * @param  int                       $id       Primary key alumni (alumni_id).
     * @return \Illuminate\Http\JsonResponse        Data alumni yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika alumni tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $alumni = Alumni::findOrFail($id);

        $request->validate([
            'name'                => 'sometimes|string|max:100',
            'graduation_year'     => 'sometimes|integer|digits:4',
            'photo'               => 'nullable|string|max:255',
            'current_institution' => 'nullable|string|max:150',
            'major'               => 'nullable|string|max:100',
            'achievement'         => 'nullable|string',
            'is_published'        => 'boolean',
        ]);

        $alumni->update($request->only(['name', 'graduation_year', 'photo', 'current_institution', 'major', 'achievement', 'is_published']));

        return response()->json($alumni);
    }

    /**
     * Menghapus profil alumni secara permanen.
     *
     * File foto fisik alumni tidak ikut dihapus oleh operasi ini.
     *
     * @param  int  $id  Primary key alumni yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Alumni::findOrFail($id)->delete();
        return response()->json(['message' => 'Data alumni dihapus.']);
    }
}
