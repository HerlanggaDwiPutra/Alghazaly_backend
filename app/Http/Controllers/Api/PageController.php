<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

/**
 * Menyediakan akses read-only ke data halaman statis CMS untuk konsumsi publik.
 *
 * Hanya mengembalikan halaman dengan `is_published = true`. Operasi CRUD
 * dikelola melalui {@see \App\Http\Controllers\Api\Admin\PageController}.
 *
 * @see \App\Models\Page
 */
class PageController extends Controller
{
    /**
     * Mengembalikan daftar ringkas semua halaman yang dipublikasikan untuk navigasi.
     *
     * Kolom `content` sengaja tidak disertakan di endpoint daftar ini untuk
     * menjaga payload yang ringan; frontend hanya membutuhkan title, slug,
     * dan thumbnail untuk merender item menu atau kartu navigasi.
     *
     * @return \Illuminate\Http\JsonResponse  Daftar halaman terurut berdasarkan `order` (ascending).
     */
    public function index(): JsonResponse
    {
        $pages = Page::where('is_published', 1)
            ->orderBy('order')
            ->get(['page_id', 'title', 'slug', 'thumbnail', 'order']);

        return response()->json($pages);
    }

    /**
     * Mengembalikan konten lengkap satu halaman berdasarkan slug-nya.
     *
     * Seluruh kolom halaman dikembalikan termasuk `content`, `meta_title`,
     * dan `meta_description` yang dibutuhkan oleh frontend untuk merender
     * konten halaman dan tag SEO secara dinamis.
     *
     * @param  string  $slug  Identifier URL-friendly halaman (contoh: 'visi-misi').
     * @return \Illuminate\Http\JsonResponse  Konten halaman lengkap, atau 404 jika tidak ditemukan/tidak dipublikasikan.
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', 1)
            ->firstOrFail();

        return response()->json($page);
    }
}
