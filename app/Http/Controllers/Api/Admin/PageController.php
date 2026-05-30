<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mengelola operasi CRUD halaman statis CMS sekolah di panel Admin.
 *
 * Halaman statis berbeda dari artikel (Post) karena bersifat permanen dan
 * merepresentasikan konten institusional (Visi Misi, Sejarah, dll.).
 * Slug di-generate otomatis dari judul halaman untuk URL yang SEO-friendly.
 *
 * @see \App\Models\Page
 * @see \App\Http\Controllers\Api\PageController  Versi read-only untuk konsumsi publik.
 */
class PageController extends Controller
{
    /**
     * Menampilkan seluruh daftar halaman terurut berdasarkan urutan navigasi.
     *
     * @return \Illuminate\Http\JsonResponse  Seluruh halaman diurutkan berdasarkan field `order` (ascending).
     */
    public function index(): JsonResponse
    {
        return response()->json(Page::orderBy('order')->get());
    }

    /**
     * Membuat halaman statis baru dengan slug yang di-generate dari judulnya.
     *
     * Slug di-generate menggunakan `Str::slug` dari judul halaman.
     * Field `meta_title` dan `meta_description` digunakan untuk optimasi SEO;
     * jika tidak diisi, frontend sebaiknya menggunakan `title` sebagai fallback.
     *
     * @param  \Illuminate\Http\Request  $request  Data halaman baru (title, content, dan field SEO).
     * @return \Illuminate\Http\JsonResponse         Data halaman yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'required|string',
            'thumbnail'        => 'nullable|string|max:255',
            'is_published'     => 'boolean',
            'order'            => 'integer|min:0',
            'meta_title'       => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $page = Page::create([
            ...$request->only(['title', 'content', 'thumbnail', 'is_published', 'order', 'meta_title', 'meta_description']),
            'slug' => Str::slug($request->title),
        ]);

        return response()->json($page, 201);
    }

    /**
     * Menampilkan detail lengkap satu halaman statis.
     *
     * @param  int  $id  Primary key halaman (page_id).
     * @return \Illuminate\Http\JsonResponse  Detail halaman lengkap, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Page::findOrFail($id));
    }

    /**
     * Memperbarui data halaman statis secara parsial.
     *
     * Slug diperbarui otomatis jika `title` berubah untuk menjaga konsistensi URL.
     * Perubahan slug pada halaman yang sudah terindeks mesin pencari dapat berdampak
     * negatif pada SEO; disarankan untuk mengubah title secara hati-hati.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang diperbarui (semua opsional).
     * @param  int                       $id       Primary key halaman yang diperbarui.
     * @return \Illuminate\Http\JsonResponse        Data halaman yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika halaman tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title'            => 'sometimes|string|max:255',
            'content'          => 'sometimes|string',
            'thumbnail'        => 'nullable|string|max:255',
            'is_published'     => 'boolean',
            'order'            => 'integer|min:0',
            'meta_title'       => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:255',
        ]);

        $data = $request->only(['title', 'content', 'thumbnail', 'is_published', 'order', 'meta_title', 'meta_description']);
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $page->update($data);

        return response()->json($page);
    }

    /**
     * Menghapus halaman statis secara permanen.
     *
     * @param  int  $id  Primary key halaman yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Page::findOrFail($id)->delete();
        return response()->json(['message' => 'Halaman dihapus.']);
    }
}
