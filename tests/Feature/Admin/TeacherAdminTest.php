<?php

/**
 * Suite Pengujian: Fitur Manajemen Guru & Staff (Admin Panel).
 *
 * Menguji integrasi kelola informasi (CRUD) entitas profil pengajar/staff sekolah.
 * Termasuk dalam kapabilitasnya adalah operasi edit struktur data personalia
 * serta pengatur status aktif dan posisi struktural.
 *
 * ATURAN BISNIS UTAMA:
 * - Form pendaftaran staf baru membutuhkan isian mutlak pada atribut `name` dan `position`.
 * - Pengaturan urutan jabatan tidak boleh lolos dari validasi (jika di-set).
 */

use App\Models\Teacher;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

/**
 * Setup Global: Pendaftaran status otorisasi admin untuk memutar kunci gerbang
 * modul panel.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Konfirmasi Restriksi Pengunjung Non-Admin.
 * Prosedur: Membaca entri rute tanpa bekal token API (Anonim).
 * Ekspektasi: Diadang lapisan keamanan aplikasi dengan pengembalian stat 401.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Meloloskan request hampa identitas.
    $response = getJson('/api/admin/teachers');

    // Assert: Sesuai harapan standar keamanan Sanctum.
    $response->assertStatus(401);
});

/**
 * Skenario: Eksekusi Pengambilan Tabel Guru/Staff Keseluruhan.
 * Prosedur: Meminta raw array objek seluruh data tenaga didik.
 * Ekspektasi: Daftar tabel utuh turun dari server database untuk disuguhkan ke dashboard.
 */
test('index menampilkan semua guru', function () {
    // Arrange: Persiapkan 3 data sample guru di mysql.
    Teacher::factory()->count(3)->create();

    // Act: Tarik menggunakan endpoint GET guru.
    $response = actingAs($this->adminUser)->getJson('/api/admin/teachers');

    // Assert: Sukses ditarik total 3 record.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

/**
 * Skenario: Pembuatan Lembar Profil Mandiri Pendidik (Happy Path).
 * Prosedur: Memasukkan identitas (Nama dan Jabatan) minimal yang dibutuhkan sistem.
 * Ekspektasi: Profil baru selamat sampai ke disk penyimpan database dan meretur 201 Created.
 */
test('store guru berhasil', function () {
    // Act: Hit form registrasi via POST.
    $response = actingAs($this->adminUser)->postJson('/api/admin/teachers', [
        'name'     => 'Pak Ahmad',
        'position' => 'Guru Matematika',
    ]);

    // Assert: Disetujui masuk baris record DB.
    $response->assertStatus(201);
    $this->assertDatabaseHas('teachers', ['name' => 'Pak Ahmad']);
});

/**
 * Skenario: Penanganan Kekosongan Atribut Vital (Nama).
 * Prosedur: Mengecoh filter validator dengan melowongkan field 'name'.
 * Ekspektasi: Terjegal di pertengahan jalan sebelum mengotori database.
 */
test('store guru validasi name wajib', function () {
    // Act: Mendaftarkan posisi tapi tanpa identitas nama.
    $response = actingAs($this->adminUser)->postJson('/api/admin/teachers', [
        'position' => 'Guru',
    ]);

    // Assert: Gagal tersimpan berkat Unprocessable Entity 422.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

/**
 * Skenario: Penanganan Kekosongan Atribut Vital (Jabatan/Posisi).
 * Prosedur: Mengecoh filter validator dengan melowongkan field 'position'.
 * Ekspektasi: Terjegal validasi Laravel form request.
 */
test('store guru validasi position wajib', function () {
    // Act: Mendaftarkan nama namun posisinya kosong.
    $response = actingAs($this->adminUser)->postJson('/api/admin/teachers', [
        'name' => 'Pak Ahmad',
    ]);

    // Assert: Sistem Validator mengidentifikasi kekurangan parameter.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['position']);
});

/**
 * Skenario: Review Detail Metadata Pengajar (Show).
 * Prosedur: Mengungkit kartu identitas data satu per-satu.
 * Ekspektasi: Menjamin relasi ID URL cocok dengan respons Payload.
 */
test('show guru detail', function () {
    // Arrange: Satu profil hantu.
    $teacher = Teacher::factory()->create();

    // Act: Panggil endpoint show id.
    $response = actingAs($this->adminUser)->getJson('/api/admin/teachers/' . $teacher->teacher_id);

    // Assert: Sesuai harapan (Match).
    $response->assertStatus(200)
             ->assertJsonPath('teacher_id', $teacher->teacher_id);
});

/**
 * Skenario: Menghadapi Permintaan Informasi Hantu (404).
 * Prosedur: ID invalid tak berjejak.
 * Ekspektasi: Tidak merusak layanan, di atasi elegan.
 */
test('show guru tidak ada (404)', function () {
    // Act: Hit route id 9999.
    $response = actingAs($this->adminUser)->getJson('/api/admin/teachers/9999');

    // Assert: Return status wajar Not Found.
    $response->assertStatus(404);
});

/**
 * Skenario: Mutasi Pengubahan Nomenklatur Nama Pendidik.
 * Prosedur: Mengirim verb HTTP merubah parsial sebuah data (Bisa Patch/Put).
 * Ekspektasi: Pergantian mutlak dari value usang ke value kontemporer.
 */
test('update guru berhasil', function () {
    // Arrange: Berikan value kuno "Lama".
    $teacher = Teacher::factory()->create(['name' => 'Lama']);

    // Act: Injeksi nama "Baru" via PUT update.
    $response = actingAs($this->adminUser)->putJson('/api/admin/teachers/' . $teacher->teacher_id, [
        'name' => 'Baru',
    ]);

    // Assert: Konfirmasi jejak pembaruan terekam sukses di MySQL DB.
    $response->assertStatus(200);
    $this->assertDatabaseHas('teachers', ['teacher_id' => $teacher->teacher_id, 'name' => 'Baru']);
});

/**
 * Skenario: Pemusnahan Data Pendidik yang Keluar/Pensiun.
 * Prosedur: Menarik penghapusan (Verb Delete).
 * Ekspektasi: Rekam barisan entitas di-cleansing secara hakiki (Data Missing).
 */
test('destroy guru berhasil', function () {
    // Arrange: Objek tiruan untuk ditendang dari DB.
    $teacher = Teacher::factory()->create();

    // Act: Hit rute delete endpoint API.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/teachers/' . $teacher->teacher_id);

    // Assert: Terjamin lenyap.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('teachers', ['teacher_id' => $teacher->teacher_id]);
});
