<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mengelola operasi CRUD album foto galeri sekolah di panel Admin.
 *
 * Setiap album dapat memiliki banyak aset {@see \App\Models\Media} yang
 * dihubungkan melalui tabel pivot `album_medias`. Slug di-generate otomatis
 * dari judul untuk membentuk URL yang deskriptif di endpoint publik.
 *
 * @see \App\Models\Album
 * @see \App\Http\Controllers\Api\AlbumController  Versi read-only untuk konsumsi publik.
 */
class AlbumController extends Controller
{
    /**
     * Menampilkan daftar seluruh album beserta jumlah foto di setiap album.
     *
     * `withCount('medias')` menambahkan atribut `medias_count` ke setiap objek
     * album menggunakan satu query agregat (`COUNT`) tanpa memuat seluruh
     * record media, menghindari masalah performa N+1 pada daftar album yang panjang.
     *
     * @return \Illuminate\Http\JsonResponse  Daftar album terurut berdasarkan `order` (ascending).
     */
    public function index(): JsonResponse
    {
        return response()->json(Album::withCount('medias')->orderBy('order')->get());
    }

    /**
     * Membuat album baru dan men-generate slug dari judulnya.
     *
     * Slug di-generate menggunakan `Str::slug` (URL-safe, huruf kecil, spasi → tanda hubung)
     * dan disimpan bersamaan dengan data album. Jika judul sama dengan album lain,
     * slug yang dihasilkan akan identik; pastikan judul album unik untuk menghindari
     * konflik URL di endpoint publik.
     *
     * @param  \Illuminate\Http\Request  $request  Data album baru (title, cover, description, is_published, order).
     * @return \Illuminate\Http\JsonResponse         Data album yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'cover'        => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'is_published' => 'boolean',
            'order'        => 'integer|min:0',
        ]);

        $album = Album::create([
            ...$request->only(['title', 'cover', 'description', 'is_published', 'order']),
            'slug' => Str::slug($request->title),
        ]);

        return response()->json($album, 201);
    }

    /**
     * Menampilkan detail satu album beserta seluruh foto di dalamnya.
     *
     * @param  int  $id  Primary key album (album_id).
     * @return \Illuminate\Http\JsonResponse  Detail album beserta relasi `medias`, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(Album::with('medias')->findOrFail($id));
    }

    /**
     * Memperbarui data album dan menyinkronisasi daftar foto di dalamnya.
     *
     * Slug diperbarui otomatis jika judul berubah. Sinkronisasi foto menggunakan
     * `sync()` pada relasi `medias` (bukan `attach()`), sehingga foto yang tidak
     * disertakan dalam request akan dilepas dari album (dihapus dari pivot),
     * tanpa menghapus file fisik dari disk `public`.
     *
     * Penggunaan `$request->has('medias')` (bukan `filled()`) memungkinkan
     * pengiriman array kosong `[]` untuk mengosongkan semua foto dalam album.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang diperbarui (semua opsional) dan array media_id.
     * @param  int                       $id       Primary key album yang diperbarui.
     * @return \Illuminate\Http\JsonResponse        Album yang telah diperbarui beserta foto terbaru.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika album tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $album = Album::findOrFail($id);

        $request->validate([
            'title'        => 'sometimes|string|max:255',
            'cover'        => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'is_published' => 'boolean',
            'order'        => 'integer|min:0',
            'medias'       => 'nullable|array',
            'medias.*'     => 'exists:medias,media_id',
        ]);

        $data = $request->only(['title', 'cover', 'description', 'is_published', 'order']);
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $album->update($data);

        if ($request->has('medias')) {
            $album->medias()->sync($request->medias ?? []);
        }

        return response()->json($album->load('medias'));
    }

    /**
     * Menghapus album secara permanen beserta hubungan pivotnya.
     *
     * Record pada tabel pivot `album_medias` akan dihapus otomatis karena
     * foreign key constraint atau Eloquent model events (jika dikonfigurasi).
     * File foto fisik di disk `public` tidak ikut dihapus; penghapusan aset
     * dilakukan secara terpisah melalui {@see \App\Http\Controllers\Api\Admin\MediaController}.
     *
     * @param  int  $id  Primary key album yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Album::findOrFail($id)->delete();
        return response()->json(['message' => 'Album dihapus.']);
    }
}
