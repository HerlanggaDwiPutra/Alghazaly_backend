<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alumni;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menyediakan akses read-only ke data alumni sekolah untuk halaman galeri alumni publik.
 *
 * Hanya mengembalikan alumni dengan `is_published = true`. Operasi CRUD
 * dikelola secara eksklusif melalui
 * {@see \App\Http\Controllers\Api\Admin\AlumniController}.
 *
 * @see \App\Models\Alumni
 */
class AlumniController extends Controller
{
    /**
     * Mengembalikan daftar alumni yang sudah dipublikasikan dengan paginasi.
     *
     * Mendukung dua parameter filter opsional melalui query string:
     * - `year`: Menyaring berdasarkan tahun kelulusan (4 digit, contoh: `2024`).
     * - `search`: Pencarian parsial pada nama alumni menggunakan operator LIKE.
     *
     * Data diurutkan dari tahun kelulusan terbaru ke terlama agar
     * alumni angkatan paling baru tampil di bagian atas halaman galeri.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (year, search).
     * @return \Illuminate\Http\JsonResponse         Daftar alumni terpaginasi (15 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $alumni = Alumni::where('is_published', 1)
            ->when($request->year, fn ($q) => $q->where('graduation_year', $request->year))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderByDesc('graduation_year')
            ->paginate(15);

        return response()->json($alumni);
    }
}
