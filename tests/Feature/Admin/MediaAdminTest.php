<?php

/**
 * Suite Pengujian: Fitur Manajemen Media File System (Admin Panel).
 *
 * Menguji integrasi unggah dan hapus file yang menggunakan disk storage lokal (Isolated).
 * Admin dapat memanajemen aset statis untuk Post, Album, maupun Registration.
 *
 * ATURAN BISNIS UTAMA:
 * - Penggunaan isolasi sistem file `Storage::fake('public')` agar memori HDD
 *   server test tidak tercemari dan tes dapat dieksekusi berkali-kali (Clean State).
 * - Pemastian filter media berdasar tipe file (image vs pdf dll).
 */

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{getJson, postJson, deleteJson, actingAs};

/**
 * Setup Global: Otorisasi pengguna admin.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Penolakan Akses Anonim.
 * Prosedur: Akses tabel rute tanpa login.
 * Ekspektasi: Sistem mengunci endpoint dengan 401.
 */
test('akses tanpa auth ditolak', function () {
    // Arrange: Tanpa sesi.
    // Act: GET payload anonim.
    $response = getJson('/api/admin/medias');
    // Assert: Terkunci.
    $response->assertStatus(401);
});

/**
 * Skenario: Menampilkan Seluruh Berkas Media Secara Ter-paginasi.
 * Prosedur: Admin membuka galeri penyimpanan file.
 * Ekspektasi: Susunan data ditampilkan dengan paginasi Laravel standar.
 */
test('index menampilkan semua media (paginated)', function () {
    // Arrange: Mempersiapkan beberapa media dummy milik admin ini.
    Media::factory()->count(3)->create(['uploader_id' => $this->adminUser->id]);

    // Act: Endpoint permintaan daftar asset media.
    $response = actingAs($this->adminUser)->getJson('/api/admin/medias');

    // Assert: Endpoint mendukung skema array paginasi dengan tepat.
    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

/**
 * Skenario: Filter Koleksi Berdasarkan Ekstensi File.
 * Prosedur: Menarik media spesifik yang berjenis gambar semata.
 * Ekspektasi: Modul media menyingkirkan file bertipe PDF pada balasan respons.
 */
test('index filter by type', function () {
    // Arrange: Mock ekstensi campuran.
    Media::factory()->create(['uploader_id' => $this->adminUser->id, 'mime_type' => 'image/jpeg']);
    Media::factory()->create(['uploader_id' => $this->adminUser->id, 'mime_type' => 'application/pdf']);

    // Act: Request dengan menempelkan flag argumen ?type=image.
    $response = actingAs($this->adminUser)->getJson('/api/admin/medias?type=image');

    // Assert: Ter-isolasi ke 1 berkas.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

/**
 * Skenario: Penanganan Isolasi Sistem File (Happy Path).
 * Prosedur: Admin menguji proses unggah dokumen gambar dengan fungsi Storage::fake().
 * Ekspektasi: Media masuk ke tabel database bersama metadata uploader tanpa menyisakan
 *             residu junk image pada disk local storage sebenarnya (Isolated State).
 */
test('store upload file berhasil', function () {
    // Arrange: Membuat isolasi sistem file semu 'public'.
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test-image.jpg', 640, 480);

    // Act: Form submit untuk file baru.
    $response = actingAs($this->adminUser)->postJson('/api/admin/medias', [
        'file' => $file,
    ]);

    // Assert: Media tervalidasi dan nama test file terekam di DB.
    $response->assertStatus(201);
    $this->assertDatabaseHas('medias', [
        'filename'    => 'test-image.jpg',
        'uploader_id' => $this->adminUser->id,
    ]);
});

/**
 * Skenario: Penolakan Payload Media Kosong.
 * Prosedur: Submisi dengan array null (Tidak mencantumkan file).
 * Ekspektasi: Diingatkan via error format array.
 */
test('store validasi file wajib', function () {
    // Arrange: Tanpa inisiasi memori.
    
    // Act: Hit kosong.
    $response = actingAs($this->adminUser)->postJson('/api/admin/medias', []);

    // Assert: Validasi mencegah file sampah (junk record).
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['file']);
});

/**
 * Skenario: Sinkronisasi Pemusnahan File Ganda.
 * Prosedur: Admin menghapus sebuah aset dari dashboard.
 * Ekspektasi: Berkas pada disk test otomatis lenyap (unlink) berbarengan dengan hilangnya
 *             catatan baris relasi yang ada di skema database.
 */
test('destroy media berhasil', function () {
    // Arrange: Membuat tiruan disk (isolasi file) dan menyuntikkan file dummy.
    Storage::fake('public');
    $media = Media::factory()->create([
        'uploader_id' => $this->adminUser->id,
        'path'        => 'uploads/test.jpg',
    ]);
    Storage::disk('public')->put('uploads/test.jpg', 'fake content');

    // Act: Minta penghapusan.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/medias/' . $media->media_id);

    // Assert: Baik rujukan DB maupun fisik file, sukses dinetralisir dari storage public test.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('medias', ['media_id' => $media->media_id]);
    Storage::disk('public')->assertMissing('uploads/test.jpg');
});

/**
 * Skenario: Tanggapan Pemusnahan Aset Fiktif.
 * Prosedur: Mencoba delete pada ID yang tidak ditemukan.
 * Ekspektasi: Status 404 tanpa fatal error.
 */
test('destroy media tidak ada (404)', function () {
    // Arrange: Bersih.
    // Act: Paksaan delete.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/medias/9999');

    // Assert: Respons hilang dengan tepat.
    $response->assertStatus(404);
});
