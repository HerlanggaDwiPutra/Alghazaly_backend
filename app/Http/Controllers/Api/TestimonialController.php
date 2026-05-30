<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

/**
 * Menyediakan akses read-only ke data testimonial untuk ditampilkan di halaman publik.
 *
 * Hanya mengembalikan testimonial dengan `is_published = true`. Operasi CRUD
 * dikelola melalui {@see \App\Http\Controllers\Api\Admin\TestimonialController}.
 *
 * @see \App\Models\Testimonial
 */
class TestimonialController extends Controller
{
    /**
     * Mengembalikan seluruh testimonial yang dipublikasikan sesuai urutan tampil.
     *
     * Kolom `content` dikurangi hingga tingkat yang diperlukan untuk keperluan
     * tampilan kartu testimonial. Kolom `rating` diikutsertakan agar frontend
     * dapat merender komponen bintang tanpa request tambahan.
     *
     * Urutan berdasarkan field `order` (ascending) memungkinkan admin
     * menentukan testimonial mana yang tampil paling menonjol di halaman beranda.
     *
     * @return \Illuminate\Http\JsonResponse  Daftar testimonial yang dipublikasikan sesuai urutan manual.
     */
    public function index(): JsonResponse
    {
        $testimonials = Testimonial::where('is_published', 1)
            ->orderBy('order')
            ->get(['testimonial_id', 'name', 'role', 'content', 'photo', 'rating']);

        return response()->json($testimonials);
    }
}
