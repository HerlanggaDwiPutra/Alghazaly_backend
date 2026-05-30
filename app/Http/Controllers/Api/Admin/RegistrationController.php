<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * Mengelola proses review dan keputusan akhir PPDB dari sisi panel Admin.
 *
 * Admin berinteraksi dengan data pendaftaran yang masuk melalui proses publik,
 * melakukan penelusuran berkas, dan secara manual mengubah status pendaftaran
 * melewati tahap-tahap yang tersedia: `pending` → `verified` → `accepted`/`rejected`.
 *
 * Status juga dapat diubah secara otomatis oleh webhook Midtrans (`pending` → `verified`)
 * saat pembayaran terkonfirmasi lunas. Intervensi admin diperlukan untuk
 * keputusan akhir penerimaan (`accepted` atau `rejected`).
 */
class RegistrationController extends Controller
{
    /**
     * Menampilkan daftar seluruh pendaftaran masuk dengan filter dan paginasi.
     *
     * Mendukung tiga parameter filter opsional melalui query string:
     * - `status`: Menyaring berdasarkan status (pending, verified, accepted, rejected).
     * - `academic_year`: Menyaring berdasarkan tahun ajaran (contoh: `2025/2026`).
     * - `search`: Pencarian pada nama lengkap (`full_name`) atau nomor pendaftaran.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (status, academic_year, search).
     * @return \Illuminate\Http\JsonResponse         Daftar pendaftaran terpaginasi (15 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $registrations = Registration::with('payment:payment_id,registration_id,status,amount')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('registration_number', 'like', "%{$request->search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($registrations);
    }

    /**
     * Menampilkan detail lengkap satu pendaftaran beserta berkas dan pembayarannya.
     *
     * Memuat relasi `documents.media` (eager loading berantai) untuk menampilkan
     * daftar berkas yang diunggah beserta metadata file dari tabel `medias`.
     *
     * @param  int  $id  Primary key dari tabel `registrations` (registration_id).
     * @return \Illuminate\Http\JsonResponse  Detail pendaftaran lengkap, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        $registration = Registration::with('documents.media', 'payment')->findOrFail($id);
        return response()->json($registration);
    }

    /**
     * Memperbarui status keputusan dan catatan tinjauan sebuah pendaftaran.
     *
     * Hanya field `status` dan `notes` yang dapat diubah melalui endpoint ini.
     * Kolom lain seperti data pribadi pendaftar bersifat immutable setelah
     * disubmit untuk menjaga integritas data PPDB.
     *
     * Fitur notifikasi email ke pendaftar (menggunakan Laravel Notification)
     * saat ini dinonaktifkan (kode di-comment) dan dapat diaktifkan setelah
     * Mailable `RegistrationStatusUpdated` selesai diimplementasikan.
     *
     * @param  \Illuminate\Http\Request  $request  Field `status` (wajib) dan `notes` (opsional).
     * @param  int                       $id       Primary key pendaftaran (registration_id).
     * @return \Illuminate\Http\JsonResponse        Data pendaftaran yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika nilai status tidak valid.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika pendaftaran tidak ditemukan.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $registration = Registration::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,verified,accepted,rejected',
            'notes'  => 'nullable|string',
        ]);

        $registration->update($request->only('status', 'notes'));

        // Kirim notifikasi email ke pendaftar
        // Notification::route('mail', $registration->parent_email)
        //     ->notify(new RegistrationStatusUpdated($registration));

        return response()->json(['message' => 'Status pendaftaran diperbarui.', 'data' => $registration]);
    }
}
