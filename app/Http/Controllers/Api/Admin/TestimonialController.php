<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola operasi CRUD data testimonial di panel Admin.
 *
 * Testimonial mewakili ulasan atau kesaksian dari wali murid, alumni, atau
 * tokoh terkait yang ditampilkan di halaman publik. Urutan tampil dikontrol
 * manual melalui field `order`, bukan berdasarkan waktu input.
 *
 * @see \App\Models\Testimonial
 * @see \App\Http\Controllers\Api\TestimonialController  Versi read-only untuk konsumsi publik.
 */
class TestimonialController extends Controller
{
    /**
     * Menampilkan seluruh daftar testimonial terurut berdasarkan prioritas tampil.
     *
     * @return \Illuminate\Http\JsonResponse  Seluruh testimonial diurutkan berdasarkan `order` (ascending).
     */
    public function index(): JsonResponse
    {
        return response()->json(Testimonial::orderBy('order')->get());
    }

    /**
     * Menambahkan data testimonial baru.
     *
     * Field `rating` bersifat opsional (nullable); jika tidak diisi, frontend
     * dapat menyembunyikan komponen bintang. Skala rating: 1 (minimum) hingga 5 (maksimum).
     *
     * @param  \Illuminate\Http\Request  $request  Data testimonial baru (name, content, rating, dll.).
     * @return \Illuminate\Http\JsonResponse         Data testimonial yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'role'         => 'nullable|string|max:100',
            'content'      => 'required|string',
            'photo'        => 'nullable|string|max:255',
            'rating'       => 'nullable|integer|min:1|max:5',
            'is_published' => 'boolean',
            'order'        => 'integer|min:0',
        ]);

        return response()->json(Testimonial::create($request->only(['name', 'role', 'content', 'photo', 'rating', 'is_published', 'order'])), 201);
    }

    /**
     * Memperbarui data testimonial yang sudah ada secara parsial.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang diperbarui (semua opsional).
     * @param  int                       $id       Primary key testimonial yang diperbarui.
     * @return \Illuminate\Http\JsonResponse        Data testimonial yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika testimonial tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::findOrFail($id);

        $request->validate([
            'name'         => 'sometimes|string|max:100',
            'role'         => 'nullable|string|max:100',
            'content'      => 'sometimes|string',
            'photo'        => 'nullable|string|max:255',
            'rating'       => 'nullable|integer|min:1|max:5',
            'is_published' => 'boolean',
            'order'        => 'integer|min:0',
        ]);

        $testimonial->update($request->only(['name', 'role', 'content', 'photo', 'rating', 'is_published', 'order']));

        return response()->json($testimonial);
    }

    /**
     * Menghapus testimonial secara permanen.
     *
     * @param  int  $id  Primary key testimonial yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Testimonial::findOrFail($id)->delete();
        return response()->json(['message' => 'Testimoni dihapus.']);
    }
}
