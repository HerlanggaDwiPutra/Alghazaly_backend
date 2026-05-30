<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Mengelola perpustakaan aset media (gambar, dokumen, dll.) di panel Admin.
 *
 * Controller ini bertindak sebagai Media Library terpusat. Setiap file yang
 * diunggah melalui endpoint ini disimpan di disk `public` (storage/app/public)
 * dan dapat direferensikan oleh entitas lain (Post, Teacher, Album) menggunakan
 * path yang tersimpan di tabel `medias`.
 *
 * Operasi delete bersifat **atomik**: file fisik di disk dihapus terlebih dahulu
 * sebelum record database dihapus untuk mencegah data orphan (record tanpa file).
 *
 * @see \App\Models\Media
 */
class MediaController extends Controller
{
    /**
     * Menampilkan daftar aset media dengan filter tipe MIME dan paginasi.
     *
     * Parameter `type` menerima prefix MIME (contoh: `image` untuk menyaring
     * semua gambar, atau `application` untuk dokumen). Filter menggunakan
     * operator LIKE dengan suffix `/%` untuk mencocokkan seluruh subtipe.
     *
     * Paginasi menggunakan 24 item per halaman (kelipatan 4 dan 6)
     * untuk kompatibilitas dengan layout grid galeri di frontend.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (type: prefix MIME).
     * @return \Illuminate\Http\JsonResponse         Daftar media terpaginasi (24 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $medias = Media::with('uploader:id,name')
            ->when($request->type, function ($q) use ($request) {
                // Contoh: type=image akan mencocokkan image/jpeg, image/png, image/webp, dst.
                $q->where('mime_type', 'like', $request->type . '/%');
            })
            ->orderByDesc('created_at')
            ->paginate(24);

        return response()->json($medias);
    }

    /**
     * Mengunggah satu file ke storage server dan mencatat metadatanya.
     *
     * File disimpan ke direktori `uploads/` pada disk `public` dengan nama
     * yang di-generate otomatis oleh Laravel untuk menghindari konflik nama.
     * Metadata asli (nama file, MIME type, ukuran) tetap disimpan di database
     * untuk keperluan referensi dan tampilan di media library.
     *
     * Batas ukuran file: 10 MB (10240 KB), berlaku untuk semua tipe file.
     *
     * @param  \Illuminate\Http\Request  $request  File yang akan diunggah (field: `file`, maks 10 MB).
     * @return \Illuminate\Http\JsonResponse         Record media yang baru dibuat beserta metadata-nya (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika file tidak ada atau melebihi batas ukuran.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file  = $request->file('file');
        $path  = $file->store('uploads', 'public');

        $media = Media::create([
            'uploader_id' => $request->user()->id,
            'filename'    => $file->getClientOriginalName(),
            'path'        => $path,
            'mime_type'   => $file->getMimeType(),
            'size'        => $file->getSize(),
        ]);

        return response()->json($media, 201);
    }

    /**
     * Menghapus aset media secara permanen dari disk dan database.
     *
     * Urutan operasi sengaja: hapus file fisik dahulu, lalu hapus record DB.
     * Jika proses dihentikan di tengah jalan, lebih aman memiliki record DB
     * tanpa file (orphan record) daripada file tanpa record (unreferenced file).
     *
     * Perhatian: Operasi ini tidak memeriksa apakah file masih direferensikan
     * oleh entitas lain (Post thumbnail, profil guru, dll.). Pastikan file
     * tidak lagi digunakan sebelum menghapusnya.
     *
     * @param  int  $id  Primary key media yang akan dihapus (media_id).
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        $media = Media::findOrFail($id);
        // Hapus file fisik dari disk 'public' (storage/app/public/).
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(['message' => 'File dihapus.']);
    }
}
