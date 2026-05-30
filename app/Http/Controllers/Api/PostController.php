<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menyediakan akses read-only ke data artikel/berita sekolah untuk konsumsi publik.
 *
 * Seluruh endpoint di controller ini bersifat publik (tanpa autentikasi) dan
 * hanya mengembalikan artikel dengan status `published`. Operasi CRUD artikel
 * dilakukan secara eksklusif melalui {@see \App\Http\Controllers\Api\Admin\PostController}.
 *
 * @see \App\Models\Post
 */
class PostController extends Controller
{
    /**
     * Mengembalikan daftar artikel yang sudah dipublikasikan dengan paginasi.
     *
     * Mendukung dua parameter filter opsional melalui query string:
     * - `category`: Menyaring artikel berdasarkan slug kategori menggunakan
     *   `whereHas` (subquery) untuk efisiensi dibanding JOIN manual.
     * - `search`: Pencarian parsial pada judul artikel menggunakan operator LIKE.
     *
     * Artikel diurutkan berdasarkan `published_at` (bukan `created_at`) agar
     * artikel yang dijadwalkan tetap muncul sesuai tanggal publikasi aktualnya.
     * Eager loading `author` dan `categories` dibatasi kolom tertentu untuk
     * mengantisipasi masalah performa N+1 query.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (category: slug, search: string).
     * @return \Illuminate\Http\JsonResponse         Daftar artikel terpaginasi (12 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $posts = Post::with('author:id,name', 'categories:category_id,category_name,slug')
            ->where('status', 'published')
            ->when($request->category, fn ($q) => $q->whereHas('categories', fn ($q) => $q->where('slug', $request->category)))
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderByDesc('published_at')
            ->paginate(12);

        return response()->json($posts);
    }

    /**
     * Mengembalikan detail satu artikel berdasarkan slug-nya.
     *
     * Menggunakan `slug` sebagai identifier publik (bukan ID numerik) agar
     * URL lebih deskriptif dan bersahabat dengan mesin pencari (SEO-friendly).
     * Hanya artikel dengan status `published` yang dapat diakses; artikel
     * berstatus `draft` atau `archived` mengembalikan HTTP 404.
     *
     * @param  string  $slug  Slug unik artikel (URL-friendly, contoh: 'pengumuman-ppdb-2025').
     * @return \Illuminate\Http\JsonResponse  Detail artikel beserta penulis dan kategori, atau 404.
     */
    public function show(string $slug): JsonResponse
    {
        $post = Post::with('author:id,name', 'categories:category_id,category_name,slug')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json($post);
    }
}
