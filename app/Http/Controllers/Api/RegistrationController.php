<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Menangani alur Penerimaan Peserta Didik Baru (PPDB) dari sisi publik/pendaftar.
 *
 * Seluruh endpoint dalam controller ini bersifat publik (tanpa autentikasi)
 * agar calon peserta didik dan orang tua dapat mengakses proses pendaftaran
 * tanpa perlu memiliki akun di sistem.
 *
 * Alur PPDB yang didukung:
 * 1. Pendaftar mengisi formulir → `store()` → mendapat `registration_id` & `registration_number`.
 * 2. Pendaftar mengunggah berkas persyaratan → `uploadDocuments()`.
 * 3. Pendaftar memantau status pendaftarannya → `checkStatus()`.
 * 4. Admin membuat tagihan pembayaran → PaymentController (admin).
 * 5. Webhook Midtrans memperbarui status otomatis → WebhookController.
 */
class RegistrationController extends Controller
{
    /**
     * Menyimpan data pendaftaran baru dan menghasilkan nomor pendaftaran unik.
     *
     * Format nomor pendaftaran: `PPDB-{TAHUN}-{6 karakter acak huruf besar}`,
     * contoh: `PPDB-2025-XK7YFM`. Nomor ini bersifat publik dan digunakan
     * sebagai identitas pendaftar untuk mengecek status tanpa login.
     *
     * Status awal registrasi selalu `pending` hingga diverifikasi admin
     * setelah pembayaran lunas dikonfirmasi melalui webhook Midtrans.
     *
     * @param  \Illuminate\Http\Request  $request  Data formulir pendaftaran siswa baru.
     * @return \Illuminate\Http\JsonResponse         Nomor dan ID pendaftaran yang baru dibuat (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name'       => 'required|string|max:100',
            'birth_date'      => 'required|date',
            'birth_place'     => 'required|string|max:100',
            'gender'          => 'required|in:L,P',
            'address'         => 'required|string',
            'phone'           => 'required|string|max:20',
            'parent_name'     => 'required|string|max:100',
            'parent_phone'    => 'required|string|max:20',
            'previous_school' => 'required|string|max:150',
            'academic_year'   => 'required|string|max:10',
        ]);

        // Gabungkan tahun berjalan dan string acak untuk membentuk nomor unik.
        // Str::random menghasilkan karakter alfanumerik; strtoupper memastikan konsistensi format.
        $number = 'PPDB-' . date('Y') . '-' . strtoupper(Str::random(6));

        $registration = Registration::create(array_merge(
            $request->only([
                'full_name', 'birth_date', 'birth_place', 'gender',
                'address', 'phone', 'parent_name', 'parent_phone',
                'previous_school', 'academic_year',
            ]),
            ['registration_number' => $number, 'status' => 'pending']
        ));

        return response()->json([
            'message'             => 'Pendaftaran berhasil. Simpan nomor pendaftaran Anda.',
            'registration_number' => $registration->registration_number,
            'registration_id'     => $registration->registration_id,
        ], 201);
    }

    /**
     * Menerima dan menyimpan berkas persyaratan pendaftaran (multi-file upload).
     *
     * Setiap berkas disimpan ke disk `public` di direktori `ppdb-documents/`,
     * kemudian diregistrasi ke tabel `medias` sebagai aset, dan dihubungkan
     * ke pendaftaran melalui tabel pivot `registration_documents`.
     *
     * `uploader_id` diisi dengan nilai `0` karena proses upload ini dilakukan
     * oleh pendaftar publik, bukan oleh pengguna admin yang terautentikasi.
     *
     * @param  \Illuminate\Http\Request  $request  Array berkas dokumen (jpg, jpeg, png, pdf, maks 5 MB).
     * @param  int                       $id       Primary key registrasi (`registration_id`).
     * @return \Illuminate\Http\JsonResponse        Pesan konfirmasi keberhasilan upload.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika registrasi tidak ditemukan.
     * @throws \Illuminate\Validation\ValidationException            Jika validasi berkas gagal.
     */
    public function uploadDocuments(Request $request, int $id): JsonResponse
    {
        $registration = Registration::findOrFail($id);

        $request->validate([
            'documents'              => 'required|array|min:1',
            'documents.*.file'       => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.*.type'       => 'required|string|max:50',
        ]);

        foreach ($request->input('documents') as $index => $item) {
            $file  = $request->file("documents.{$index}.file");
            $path  = $file->store('ppdb-documents', 'public');

            $media = Media::create([
                'uploader_id' => 0,
                'filename'    => $file->getClientOriginalName(),
                'path'        => $path,
                'mime_type'   => $file->getMimeType(),
                'size'        => $file->getSize(),
            ]);

            RegistrationDocument::create([
                'registration_id' => $registration->registration_id,
                'document_type'   => $item['type'],
                'media_id'        => $media->media_id,
            ]);
        }

        return response()->json(['message' => 'Dokumen berhasil diupload.']);
    }

    /**
     * Mengecek status pendaftaran berdasarkan nomor pendaftaran publik.
     *
     * Endpoint ini dirancang sebagai fitur self-service bagi pendaftar dan
     * orang tua agar dapat memantau progres PPDB tanpa perlu menghubungi admin.
     *
     * Kolom yang dikembalikan sengaja dibatasi (tidak termasuk data pribadi
     * sensitif seperti alamat dan nomor telepon) untuk menjaga privasi data.
     * Relasi `payment` dimuat untuk menampilkan status pembayaran terkini.
     *
     * @param  string  $number  Nomor pendaftaran publik (format: `PPDB-YYYY-XXXXXX`).
     * @return \Illuminate\Http\JsonResponse  Status pendaftaran dan pembayaran, atau 404 jika tidak ditemukan.
     */
    public function checkStatus(string $number): JsonResponse
    {
        $registration = Registration::where('registration_number', $number)
            ->with('payment:payment_id,registration_id,status,amount,paid_at')
            ->firstOrFail([
                'registration_id', 'registration_number', 'full_name',
                'academic_year', 'status', 'notes', 'created_at',
            ]);

        return response()->json($registration);
    }
}
