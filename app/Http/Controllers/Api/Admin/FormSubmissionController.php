<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola kotak masuk pesan formulir dinamis di panel Admin.
 *
 * Kiriman dari semua formulir aktif (kontak, pertanyaan, dll.) masuk ke
 * satu antarmuka terpusat ini. Admin dapat menyaring berdasarkan formulir
 * asal dan status baca, menandai pesan sebagai terbaca, serta menghapusnya.
 *
 * @see \App\Http\Controllers\Api\FormController  Controller sisi publik untuk pengiriman pesan.
 * @see \App\Models\FormSubmission
 */
class FormSubmissionController extends Controller
{
    /**
     * Menampilkan daftar pesan masuk dari seluruh formulir dengan paginasi.
     *
     * Mendukung dua parameter filter opsional melalui query string:
     * - `form_id`: Menyaring pesan dari formulir tertentu.
     * - `is_read`: Menyaring berdasarkan status baca (`true`/`false`/`1`/`0`).
     *   Menggunakan `$request->boolean()` untuk normalisasi nilai truthy/falsy.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (form_id, is_read).
     * @return \Illuminate\Http\JsonResponse         Daftar pesan terpaginasi (15 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $submissions = FormSubmission::with('form:form_id,name')
            ->when($request->form_id, fn ($q) => $q->where('form_id', $request->form_id))
            // Pengecekan !== null diperlukan karena is_read=0 (falsy) harus tetap diproses sebagai filter aktif.
            ->when($request->is_read !== null, fn ($q) => $q->where('is_read', $request->boolean('is_read')))
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($submissions);
    }

    /**
     * Menampilkan detail pesan dan secara otomatis menandainya sebagai sudah dibaca.
     *
     * Pembaruan `is_read = 1` dilakukan secara implisit saat endpoint ini dipanggil,
     * mengikuti pola "mark-on-open" agar admin tidak perlu melakukan aksi terpisah.
     *
     * @param  int  $id  Primary key dari tabel `form_submissions` (submission_id).
     * @return \Illuminate\Http\JsonResponse  Detail pesan beserta relasi formulir, atau 404.
     */
    public function show(int $id): JsonResponse
    {
        $submission = FormSubmission::with('form')->findOrFail($id);
        // Tandai pesan sebagai terbaca secara implisit saat admin membuka detailnya.
        $submission->update(['is_read' => 1]);

        return response()->json($submission);
    }

    /**
     * Menghapus sebuah pesan secara permanen dari database.
     *
     * Operasi ini bersifat hard delete (tidak menggunakan soft delete).
     * Pastikan pesan sudah ditinjau sebelum dihapus karena tindakan ini
     * tidak dapat dibatalkan.
     *
     * @param  int  $id  Primary key pesan (submission_id).
     * @return \Illuminate\Http\JsonResponse  Pesan konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        FormSubmission::findOrFail($id)->delete();
        return response()->json(['message' => 'Pesan dihapus.']);
    }
}
