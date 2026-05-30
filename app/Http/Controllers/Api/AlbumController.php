<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menyediakan akses read-only ke data album foto untuk halaman galeri publik sekolah.
 *
 * Hanya album dengan `is_published = true` yang dapat diakses publik.
 * Operasi CRUD album dikelola melalui
 * {@see \App\Http\Controllers\Api\Admin\AlbumController}.
 *
 * @see \App\Models\Album
 */
class AlbumController extends Controller
{
    /**
     * Mengembalikan daftar album yang dipublikasikan dengan paginasi.
     *
     * Kolom yang dikembalikan sengaja dibatasi (tanpa `content` dan relasi `medias`)
     * untuk menjaga payload ringkas di halaman indeks galeri. Detail foto
     * dalam album dimuat secara terpisah melalui method `show()`.
     *
     * Album diurutkan berdasarkan field `order` (ascending) sesuai pengaturan
     * manual admin, bukan berdasarkan waktu pembuatan.
     *
     * @param  \Illuminate\Http\Request  $request  Request (tidak ada parameter filter yang aktif).
     * @return \Illuminate\Http\JsonResponse         Daftar album terpaginasi (12 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $albums = Album::where('is_published', 1)
            ->orderBy('order')
            ->paginate(12, ['album_id', 'title', 'slug', 'cover', 'description', 'created_at']);

        return response()->json($albums);
    }

    /**
     * Mengembalikan detail satu album beserta seluruh foto di dalamnya.
     *
     * Menggunakan `slug` sebagai identifier publik untuk URL yang lebih
     * deskriptif. Foto dimuat via eager loading relasi `medias` dengan
     * kolom dibatasi untuk mengurangi ukuran payload response.
     *
     * @param  string  $slug  Slug unik album (contoh: 'kegiatan-pramuka-2024').
     * @return \Illuminate\Http\JsonResponse  Detail album beserta daftar foto, atau 404 jika tidak ditemukan.
     */
    public function show(string $slug): JsonResponse
    {
        $album = Album::where('slug', $slug)
            ->where('is_published', 1)
            ->with('medias:media_id,filename,path,mime_type')
            ->firstOrFail();

        return response()->json($album);
    }
}
