<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * Menyediakan akses read-only ke data kategori artikel sekolah untuk konsumsi publik.
 *
 * Digunakan oleh frontend untuk membangun navigasi/filter kategori pada
 * halaman portal berita sekolah. Operasi CRUD kategori dikelola melalui
 * {@see \App\Http\Controllers\Api\Admin\CategoryController}.
 *
 * @see \App\Models\Category
 */
class CategoryController extends Controller
{
    /**
     * Mengembalikan seluruh kategori tingkat teratas beserta sub-kategorinya.
     *
     * Hanya mengambil kategori root (`parent_id IS NULL`) dan memuat
     * sub-kategorinya melalui eager loading relasi `children` untuk menghindari
     * masalah N+1 query saat frontend merender menu bertingkat.
     *
     * Kolom dikembalikan dibatasi untuk mengurangi payload; ID dan slug sudah
     * cukup untuk membangun URL filter dan menampilkan label navigasi.
     *
     * @return \Illuminate\Http\JsonResponse  Daftar kategori root dengan children ter-embed.
     */
    public function index(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->with('children:category_id,category_name,slug,parent_id')
            ->orderBy('category_name')
            ->get(['category_id', 'category_name', 'slug', 'parent_id']);

        return response()->json($categories);
    }
}
