<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Menyediakan data ringkasan statistik untuk halaman utama panel Admin.
 *
 * Seluruh data dikumpulkan dalam satu request tunggal menggunakan multiple
 * query agregat untuk meminimalkan jumlah roundtrip ke database.
 * Hasilnya adalah snapshot kondisi sistem secara real-time.
 */
class DashboardController extends Controller
{
    /**
     * Mengembalikan statistik agregat dan data terkini untuk dashboard admin.
     *
     * Metrik yang disertakan:
     * - Ringkasan status pendaftaran PPDB (total, pending, accepted, rejected).
     * - Ringkasan status pembayaran (total, paid, pending).
     * - Jumlah total artikel berita yang diterbitkan.
     * - Jumlah total akun pengguna admin.
     * - Jumlah pesan formulir yang belum dibaca.
     * - Lima pendaftaran terbaru untuk tabel aktivitas ringkas.
     *
     * @return \Illuminate\Http\JsonResponse  Objek JSON berisi seluruh metrik dashboard.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'registrations' => [
                'total'    => Registration::count(),
                'pending'  => Registration::where('status', 'pending')->count(),
                'accepted' => Registration::where('status', 'accepted')->count(),
                'rejected' => Registration::where('status', 'rejected')->count(),
            ],
            'payments' => [
                'total'  => Payment::count(),
                'paid'   => Payment::where('status', 'paid')->count(),
                'pending'=> Payment::where('status', 'pending')->count(),
            ],
            'posts'            => Post::count(),
            'users'            => User::count(),
            'unread_messages'  => FormSubmission::where('is_read', 0)->count(),
            // Kolom dibatasi untuk payload yang ringan; detail lengkap via endpoint registrations.
            'recent_registrations' => Registration::latest()->take(5)
                ->get(['registration_id', 'registration_number', 'full_name', 'status', 'created_at']),
        ]);
    }
}
