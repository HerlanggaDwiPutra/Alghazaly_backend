<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menangani rendering formulir dinamis dan penyimpanan kiriman data dari publik.
 *
 * Sistem formulir Al Ghazaly bersifat schema-driven: struktur field formulir
 * (label, tipe input, validasi) disimpan sebagai JSON di kolom `fields` pada
 * tabel `forms`. Frontend merender UI formulir berdasarkan schema ini,
 * sedangkan data kiriman disimpan sebagai JSON bebas di kolom `data`
 * pada tabel `form_submissions`.
 *
 * Pola ini memungkinkan admin membuat formulir kustom (kontak, pendaftaran
 * kegiatan, dll.) tanpa perlu mengubah kode aplikasi.
 *
 * @see \App\Models\Form
 * @see \App\Models\FormSubmission
 */
class FormController extends Controller
{
    /**
     * Mengembalikan definisi (schema) sebuah formulir aktif berdasarkan slug-nya.
     *
     * Hanya formulir dengan `is_active = true` yang dapat diakses publik.
     * Kolom yang dikembalikan dibatasi pada field yang dibutuhkan frontend
     * untuk merender UI ({@see \App\Models\Form::$fillable}).
     *
     * @param  string  $slug  Identifier URL-friendly dari formulir (contoh: `kontak-sekolah`).
     * @return \Illuminate\Http\JsonResponse  Schema formulir aktif, atau 404 jika tidak ada/nonaktif.
     */
    public function show(string $slug): JsonResponse
    {
        $form = Form::where('slug', $slug)
            ->where('is_active', 1)
            ->firstOrFail(['form_id', 'name', 'slug', 'fields']);

        return response()->json($form);
    }

    /**
     * Menerima dan menyimpan data kiriman dari sebuah formulir aktif.
     *
     * Field `data` menerima array asosiatif bebas-struktur yang merepresentasikan
     * jawaban pengguna, sesuai dengan skema field yang didefinisikan admin.
     * Tidak ada validasi per-field di sisi server; validasi diserahkan kepada
     * frontend berdasarkan schema dari method `show()`.
     *
     * IP pengirim (`submitter_ip`) dicatat secara otomatis dari request
     * untuk keperluan audit dan deteksi spam.
     *
     * @param  \Illuminate\Http\Request  $request  Payload kiriman formulir (`data` array, `submitter_email` opsional).
     * @param  string                    $slug     Slug formulir tujuan.
     * @return \Illuminate\Http\JsonResponse        Konfirmasi penerimaan kiriman (HTTP 201).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika formulir tidak ditemukan atau tidak aktif.
     * @throws \Illuminate\Validation\ValidationException            Jika validasi input gagal.
     */
    public function submit(Request $request, string $slug): JsonResponse
    {
        $form = Form::where('slug', $slug)->where('is_active', 1)->firstOrFail();

        $request->validate([
            'data'            => 'required|array',
            'submitter_email' => 'nullable|email|max:100',
        ]);

        FormSubmission::create([
            'form_id'         => $form->form_id,
            'data'            => $request->data,
            'submitter_ip'    => $request->ip(),
            'submitter_email' => $request->submitter_email,
            'is_read'         => 0,
        ]);

        return response()->json(['message' => 'Pesan berhasil dikirim.'], 201);
    }
}
