<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mengelola operasi CRUD kategori artikel/berita sekolah di panel Admin.
 *
 * Model Category mendukung struktur hierarkis (parent-child) melalui
 * self-referential relation. Endpoint `index` hanya mengembalikan kategori
 * root dan menyertakan sub-kategori via eager loading untuk mengurangi
 * jumlah query ke database.
 *
 * Slug di-generate otomatis dari nama kategori dan digunakan sebagai
 * identifier filter di endpoint publik portal berita.
 *
 * @see \App\Models\Category
 * @see \App\Http\Controllers\Api\CategoryController  Versi read-only untuk konsumsi publik.
 */
class CategoryController extends Controller
{
    /**
     * Menampilkan seluruh kategori root beserta sub-kategorinya.
     *
     * Hanya mengambil kategori tingkat teratas (`parent_id IS NULL`) dan
     * menyertakan relasi `children` melalui eager loading dalam satu query
     * tambahan, menghindari masalah N+1 jika di-loop di aplikasi.
     *
     * @return \Illuminate\Http\JsonResponse  Daftar kategori root dengan children ter-embed.
     */
    public function index(): JsonResponse
    {
        return response()->json(Category::with('children')->whereNull('parent_id')->get());
    }

    /**
     * Membuat kategori baru dan men-generate slug dari namanya.
     *
     * Jika `parent_id` diisi, kategori ini menjadi sub-kategori dari induknya.
     * Nilai null pada `parent_id` menjadikan kategori ini sebagai root.
     * Slug di-generate dari `category_name` menggunakan `Str::slug`.
     *
     * @param  \Illuminate\Http\Request  $request  Data kategori baru (category_name, parent_id opsional).
     * @return \Illuminate\Http\JsonResponse         Data kategori yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi gagal atau parent_id tidak valid.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category_name' => 'required|string|max:100',
            'parent_id'     => 'nullable|exists:categories,category_id',
        ]);

        $category = Category::create([
            'category_name' => $request->category_name,
            'slug'          => Str::slug($request->category_name),
            'parent_id'     => $request->parent_id,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Memperbarui data kategori secara parsial.
     *
     * Slug diperbarui otomatis jika `category_name` berubah untuk menjaga
     * konsistensi URL. Perubahan slug dapat memutus link filter yang sudah
     * beredar di halaman publik; pertimbangkan konsekuensi SEO sebelum mengubah nama.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang diperbarui (category_name, parent_id).
     * @param  int                       $id       Primary key kategori yang diperbarui (category_id).
     * @return \Illuminate\Http\JsonResponse        Data kategori yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika kategori tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'category_name' => 'sometimes|string|max:100',
            'parent_id'     => 'nullable|exists:categories,category_id',
        ]);

        $data = $request->only(['category_name', 'parent_id']);
        if (isset($data['category_name'])) {
            $data['slug'] = Str::slug($data['category_name']);
        }

        $category->update($data);

        return response()->json($category);
    }

    /**
     * Menghapus kategori secara permanen.
     *
     * Perhatian: Menghapus kategori parent tidak otomatis menghapus sub-kategorinya;
     * sub-kategori akan menjadi orphan (`parent_id` menunjuk ke ID yang tidak ada).
     * Pastikan tidak ada sub-kategori aktif atau artikel terhubung sebelum menghapus.
     *
     * @param  int  $id  Primary key kategori yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Category::findOrFail($id)->delete();
        return response()->json(['message' => 'Kategori dihapus.']);
    }
}
