<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;

/**
 * Menyediakan akses read-only ke data profil guru/tenaga pengajar untuk halaman publik.
 *
 * Hanya mengembalikan guru dengan `is_active = true`. Data guru yang
 * dinonaktifkan hanya terlihat di panel admin melalui
 * {@see \App\Http\Controllers\Api\Admin\TeacherController}.
 *
 * @see \App\Models\Teacher
 */
class TeacherController extends Controller
{
    /**
     * Mengembalikan seluruh daftar guru aktif untuk ditampilkan di halaman profil sekolah.
     *
     * Filter `is_active = 1` memastikan guru yang dinonaktifkan admin tidak muncul
     * di halaman publik tanpa perlu menghapus datanya dari database.
     *
     * Urutan tampil dikontrol manual oleh admin melalui field `order` (ascending),
     * bukan berdasarkan waktu input, sehingga Kepala Sekolah dapat diprioritaskan
     * untuk tampil di posisi pertama.
     *
     * Kolom `bio` sengaja disertakan agar frontend dapat menampilkan tooltip/modal
     * biografi singkat tanpa memerlukan request API tambahan.
     *
     * @return \Illuminate\Http\JsonResponse  Daftar guru aktif terurut beserta kolom presentasi.
     */
    public function index(): JsonResponse
    {
        $teachers = Teacher::where('is_active', 1)
            ->orderBy('order')
            ->get(['teacher_id', 'name', 'position', 'subject', 'photo', 'bio']);

        return response()->json($teachers);
    }
}
