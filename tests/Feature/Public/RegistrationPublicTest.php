<?php

/**
 * Suite Pengujian: Pendaftaran Siswa Baru (PPDB) — Akses Publik Tanpa Autentikasi.
 *
 * Berkas ini menguji seluruh alur pendaftaran siswa baru (PPDB) yang dapat dilakukan
 * langsung oleh calon siswa atau orang tua melalui endpoint publik.
 *
 * ALUR PENDAFTARAN YANG DIUJI:
 * 1. Submit data diri calon siswa → mendapatkan nomor registrasi unik.
 * 2. Mengunggah dokumen persyaratan (KK, ijazah, dll.) menggunakan nomor registrasi.
 * 3. Memeriksa status pendaftaran menggunakan nomor registrasi.
 *
 * CATATAN ARSITEKTUR:
 * - Storage::fake('public') digunakan untuk mengisolasi operasi file dari
 *   disk sungguhan, mencegah file tes mencemari storage production.
 * - Nomor registrasi di-generate otomatis oleh sistem dan harus unik untuk
 *   setiap pendaftar agar tidak terjadi konflik pengambilan data.
 * - Status awal pendaftaran selalu 'pending' dan hanya dapat diubah oleh admin.
 */

use App\Models\Registration;
use App\Models\Media;
use App\Models\RegistrationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{postJson, getJson};

/**
 * Skenario: Pendaftaran PPDB Lengkap — Happy Path.
 * Prosedur: Mengirimkan seluruh field wajib pendaftaran dengan data yang valid.
 * Ekspektasi: Sistem membuat record registrasi dengan status 'pending' dan
 *             mengembalikan nomor registrasi unik yang dapat digunakan untuk
 *             upload dokumen dan pengecekan status.
 */
test('registrasi ppdb berhasil', function () {
    // Arrange: Menyiapkan data pendaftaran lengkap sesuai field yang diwajibkan
    //          oleh aturan bisnis PPDB (full_name, birth_date, gender, dll.).
    $data = [
        'full_name'       => 'Siswa Baru',
        'birth_date'      => '2010-01-01',
        'birth_place'     => 'Jakarta',
        'gender'          => 'L',
        'address'         => 'Jl. Pendidikan No. 1',
        'phone'           => '081234567890',
        'parent_name'     => 'Orang Tua Siswa',
        'parent_phone'    => '081234567891',
        'previous_school' => 'SMP Negeri 1',
        'academic_year'   => '2024/2025',
    ];

    // Act: Mengirimkan POST request ke endpoint pendaftaran publik.
    $response = postJson('/api/registrations', $data);

    // Assert: HTTP 201 dan response mengandung 'registration_number' yang akan
    //         digunakan calon siswa untuk mengecek status dan mengunggah dokumen.
    //         Database juga diverifikasi untuk memastikan persistensi data.
    $response->assertStatus(201)
             ->assertJsonStructure(['message', 'registration_number', 'registration_id']);

    $this->assertDatabaseHas('registrations', [
        'full_name' => 'Siswa Baru',
        'status' => 'pending'
    ]);
});

/**
 * Skenario: Validasi Input — Field Nama Lengkap Wajib Diisi.
 * Prosedur: Mengirimkan request pendaftaran tanpa field 'full_name'.
 * Ekspektasi: HTTP 422 dengan error validasi yang menunjuk ke field 'full_name'.
 *             Sistem tidak boleh membuat record registrasi yang tidak lengkap.
 */
test('registrasi ppdb validasi full name wajib', function () {
    // Arrange: Tidak ada setup; tes ini hanya menguji aturan validasi field.

    // Act: Mengirim payload yang tidak menyertakan 'full_name'.
    $response = postJson('/api/registrations', [
        'birth_date' => '2010-01-01',
    ]);

    // Assert: HTTP 422 dengan error validasi spesifik untuk field 'full_name'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['full_name']);
});

/**
 * Skenario: Validasi Input — Field Gender Harus Nilai Enum yang Valid.
 * Prosedur: Mengirimkan nilai 'X' untuk field 'gender' yang hanya menerima 'L' atau 'P'.
 * Ekspektasi: HTTP 422 karena 'X' bukan nilai yang terdaftar dalam aturan `in:L,P`.
 *             Ini memastikan data jenis kelamin tersimpan dalam format yang konsisten.
 */
test('registrasi ppdb validasi gender enum', function () {
    // Arrange: Menyiapkan data pendaftaran lengkap dengan gender tidak valid ('X').
    $response = postJson('/api/registrations', [
        'full_name'       => 'Siswa Baru',
        'birth_date'      => '2010-01-01',
        'birth_place'     => 'Jakarta',
        'gender'          => 'X', // Invalid
        'address'         => 'Jl. Pendidikan No. 1',
        'phone'           => '081234567890',
        'parent_name'     => 'Orang Tua Siswa',
        'parent_phone'    => '081234567891',
        'previous_school' => 'SMP Negeri 1',
        'academic_year'   => '2024/2025',
    ]);

    // Assert: HTTP 422 — 'X' bukan nilai enum yang valid untuk field gender.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['gender']);
});

/**
 * Skenario: Validasi Input — Field Tanggal Lahir Harus Format Date.
 * Prosedur: Mengirimkan string arbitrer bukan format tanggal untuk field 'birth_date'.
 * Ekspektasi: HTTP 422 karena nilai tidak memenuhi aturan validasi `date`.
 *             Format yang diterima adalah ISO 8601 (YYYY-MM-DD).
 */
test('registrasi ppdb validasi birth date format', function () {
    // Arrange: Menyiapkan data dengan 'birth_date' berisi string bukan tanggal.
    $response = postJson('/api/registrations', [
        'full_name'       => 'Siswa Baru',
        'birth_date'      => 'bukan-tanggal',
        'birth_place'     => 'Jakarta',
        'gender'          => 'L',
        'address'         => 'Jl. Pendidikan No. 1',
        'phone'           => '081234567890',
        'parent_name'     => 'Orang Tua Siswa',
        'parent_phone'    => '081234567891',
        'previous_school' => 'SMP Negeri 1',
        'academic_year'   => '2024/2025',
    ]);

    // Assert: HTTP 422 — string 'bukan-tanggal' tidak valid sebagai nilai date.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['birth_date']);
});

/**
 * Skenario: Nomor Registrasi Unik — Tidak Ada Duplikasi Antar Pendaftar.
 * Prosedur: Mendaftarkan dua calon siswa berbeda dan membandingkan nomor registrasi
 *           yang di-generate oleh sistem.
 * Ekspektasi: Setiap pendaftar mendapatkan nomor registrasi yang berbeda.
 *             Nomor yang sama akan menyebabkan konflik saat admin memeriksa berkas.
 */
test('registrasi ppdb nomor unik digenerate', function () {
    // Arrange: Menyiapkan data dasar pendaftaran yang akan digunakan untuk dua pendaftar.
    $data = [
        'full_name'       => 'Siswa Satu',
        'birth_date'      => '2010-01-01',
        'birth_place'     => 'Jakarta',
        'gender'          => 'L',
        'address'         => 'Alamat',
        'phone'           => '123',
        'parent_name'     => 'Ortu',
        'parent_phone'    => '123',
        'previous_school' => 'SMP',
        'academic_year'   => '2024',
    ];

    // Act: Mendaftarkan dua pendaftar dengan nama berbeda secara berurutan.
    $response1 = postJson('/api/registrations', $data);
    $response2 = postJson('/api/registrations', array_merge($data, ['full_name' => 'Siswa Dua']));

    $regNum1 = $response1->json('registration_number');
    $regNum2 = $response2->json('registration_number');

    // Assert: Kedua nomor registrasi harus berbeda satu sama lain.
    expect($regNum1)->not->toBe($regNum2);
});

/**
 * Skenario: Pengecekan Status Pendaftaran — Nomor Registrasi Valid.
 * Prosedur: Pendaftar menggunakan nomor registrasi yang valid untuk memeriksa statusnya.
 * Ekspektasi: Sistem mengembalikan data status pendaftaran beserta nomor registrasinya.
 *             Ini adalah satu-satunya cara calon siswa melacak progres pendaftarannya.
 */
test('cek status registrasi valid', function () {
    // Arrange: Membuat record registrasi yang sudah ada di database.
    $registration = Registration::factory()->create();

    // Act: GET request ke endpoint status menggunakan nomor registrasi.
    $response = getJson('/api/registrations/' . $registration->registration_number . '/status');

    // Assert: HTTP 200 dan response mengandung 'registration_number' yang sesuai.
    $response->assertStatus(200)
             ->assertJson(['registration_number' => $registration->registration_number]);
});

/**
 * Skenario: Pengecekan Status — Nomor Registrasi Tidak Ditemukan (404).
 * Prosedur: Memasukkan nomor registrasi yang tidak terdaftar di sistem.
 * Ekspektasi: HTTP 404 agar pendaftar mengetahui nomor yang dimasukkan salah,
 *             tanpa membocorkan informasi tentang data pendaftar lain.
 */
test('cek status registrasi tidak ada', function () {
    // Arrange: Tidak ada registrasi yang dibuat; nomor 'TIDAK-ADA' tidak akan ditemukan.

    // Act: Mengirim GET request dengan nomor yang tidak ada di database.
    $response = getJson('/api/registrations/TIDAK-ADA/status');

    // Assert: HTTP 404 membuktikan validasi keberadaan nomor registrasi berjalan.
    $response->assertStatus(404);
});

/**
 * Skenario: Upload Dokumen — Berhasil dengan File Gambar Valid.
 * Prosedur: Calon siswa mengunggah file gambar (JPG) sebagai dokumen 'kartu_keluarga'.
 * Ekspektasi: File tersimpan di storage, record Media dibuat, dan relasi
 *             RegistrationDocument yang menghubungkan dokumen ke pendaftaran juga dibuat.
 */
test('upload dokumen berhasil', function () {
    // Arrange: Mengisolasi storage dengan fake disk untuk mencegah file nyata tersimpan.
    //          User::factory(['id' => 0]) diperlukan untuk memenuhi FK 'uploader_id'.
    Storage::fake('public');
    User::factory()->create(['id' => 0]);
    $registration = Registration::factory()->create();

    // Membuat file gambar palsu menggunakan UploadedFile::fake() — tidak memerlukan
    // file sungguhan, cukup untuk melewati validasi mime type.
    $file = UploadedFile::fake()->image('kk.jpg');

    // Act: POST request multipart ke endpoint upload dokumen dengan tipe 'kartu_keluarga'.
    $response = postJson('/api/registrations/' . $registration->registration_id . '/documents', [
        'documents' => [
            [
                'file' => $file,
                'type' => 'kartu_keluarga'
            ]
        ]
    ]);

    // Assert: HTTP 200, record Media tersimpan di database, dan relasi
    //         RegistrationDocument terbentuk dengan tipe dokumen yang benar.
    $response->assertStatus(200);

    $this->assertDatabaseHas('medias', [
        'filename' => 'kk.jpg'
    ]);

    $this->assertDatabaseHas('registration_documents', [
        'registration_id' => $registration->registration_id,
        'document_type' => 'kartu_keluarga'
    ]);
});

/**
 * Skenario: Upload Dokumen — Tipe File Tidak Diizinkan (Executable).
 * Prosedur: Calon siswa mencoba mengunggah file .exe yang bukan dokumen yang valid.
 * Ekspektasi: HTTP 422 karena tipe MIME 'application/x-msdownload' tidak termasuk
 *             dalam daftar tipe file yang diizinkan (hanya gambar/PDF).
 *             Ini mencegah eksekusi file berbahaya di server.
 */
test('upload dokumen validasi tipe file', function () {
    // Arrange: Mengisolasi storage dan membuat file executable palsu.
    //          Ukuran 100KB dipilih agar tidak memicu batasan ukuran file.
    Storage::fake('public');
    User::factory()->create(['id' => 0]);
    $registration = Registration::factory()->create();

    $file = UploadedFile::fake()->create('app.exe', 100, 'application/x-msdownload');

    // Act: Mencoba mengunggah file .exe sebagai dokumen ijazah.
    $response = postJson('/api/registrations/' . $registration->registration_id . '/documents', [
        'documents' => [
            [
                'file' => $file,
                'type' => 'ijazah'
            ]
        ]
    ]);

    // Assert: HTTP 422 dengan error validasi menunjuk ke field 'documents.0.file'.
    //         Notasi array dot-notation memperlihatkan elemen pertama dari array dokumen.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['documents.0.file']);
});

/**
 * Skenario: Upload Dokumen — Registrasi Tidak Ditemukan (404).
 * Prosedur: Mengirimkan upload dokumen ke ID registrasi yang tidak ada di database.
 * Ekspektasi: HTTP 404 — tidak boleh ada dokumen yang tersimpan tanpa registrasi induk.
 *             Ini mencegah akumulasi file orphan di storage.
 */
test('upload dokumen registrasi tidak ada', function () {
    // Arrange: Mengisolasi storage; tidak ada registrasi yang dibuat.
    Storage::fake('public');
    User::factory()->create(['id' => 0]);
    $file = UploadedFile::fake()->image('kk.jpg');

    // Act: POST ke ID registrasi '9999' yang tidak ada di database.
    $response = postJson('/api/registrations/9999/documents', [
        'documents' => [
            [
                'file' => $file,
                'type' => 'kartu_keluarga'
            ]
        ]
    ]);

    // Assert: HTTP 404 membuktikan bahwa sistem memeriksa keberadaan registrasi
    //         sebelum memproses unggahan dokumen.
    $response->assertStatus(404);
});

