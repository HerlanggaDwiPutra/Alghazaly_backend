<?php

/**
 * Suite Pengujian: Fitur Manajemen Pendaftar PPDB (Admin Panel).
 *
 * Menguji administrasi sistem pendataan formulir pendaftaran PPDB.
 * Sistem memastikan bahwa administrator dapat mengubah status pendaftar
 * serta memfilter daftar registrasi berdasarkan kebutuhan operasional (Tahun/Nama).
 */

use App\Models\User;
use App\Models\Role;
use App\Models\Registration;
use function Pest\Laravel\{getJson, patchJson, actingAs};

/**
 * Setup Global: Menyisipkan sesi perizinan admin untuk seluruh tes ini.
 */
beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->role_id, 'is_active' => true]);
});

/**
 * Skenario: Evaluasi Pagination Untuk Registrasi Berlimpah.
 * Prosedur: Pengaksesan antrean daftar murid.
 * Ekspektasi: Respons harus berupa format JSON paginasi.
 */
test('index menampilkan semua pendaftar', function () {
    // Arrange: Mempersiapkan daftar entri murid baru.
    Registration::factory()->count(3)->create();

    // Act: Hit the index pendaftaran.
    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations');

    // Assert: Struktur standar laravel memuat page navigasi.
    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'first_page_url', 'last_page', 'links', 'per_page', 'total']);
});

/**
 * Skenario: Detail Spesifik Calon Siswa.
 * Prosedur: Admin menarik arsip satu siswa secara tunggal.
 * Ekspektasi: Detail memuat kunci primer dan metadata registrasinya.
 */
test('show detail pendaftar', function () {
    // Arrange: Menyiapkan satu profil tunggal.
    $registration = Registration::factory()->create();

    // Act: Hit by primary key `registration_id`.
    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations/' . $registration->registration_id);

    // Assert: ID pendaftaran identik.
    $response->assertStatus(200)
             ->assertJsonPath('registration_id', $registration->registration_id);
});

/**
 * Skenario: Transisi Status Berhasil Diterima.
 * Prosedur: Admin meloloskan/mengizinkan siswa di sistem, merubah parameter `pending`.
 * Ekspektasi: Status siswa beralih ke format valid.
 */
test('update status diterima', function () {
    // Arrange: Menyiapkan murid yang dalam zona 'pending'.
    $registration = Registration::factory()->create(['status' => 'pending']);

    // Act: Menambal parameter status (PATCH).
    $response = actingAs($this->adminUser)->patchJson('/api/admin/registrations/' . $registration->registration_id . '/status', [
        'status' => 'accepted',
    ]);

    // Assert: Database mendeteksi perubahan flag status di pendaftar.
    $response->assertStatus(200);
    $this->assertDatabaseHas('registrations', [
        'registration_id' => $registration->registration_id,
        'status' => 'accepted',
    ]);
});

/**
 * Skenario: Transisi Status Ditolak Berserta Alasan Penolakan.
 * Prosedur: Mengupdate string penolakan jika calon siswa tak lolos.
 * Ekspektasi: Catatan kegagalan tersambung ke `notes` pada respon.
 */
test('update status ditolak dengan notes', function () {
    // Arrange: Data penguji 'pending'.
    $registration = Registration::factory()->create(['status' => 'pending']);

    // Act: Merubah state ke penolakan dengan imbuhan argumen tambahan.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/registrations/' . $registration->registration_id . '/status', [
        'status' => 'rejected',
        'notes' => 'Umur tidak mencukupi',
    ]);

    // Assert: Baris DB memperlihatkan argumen tambahan secara eksplisit.
    $response->assertStatus(200);
    $this->assertDatabaseHas('registrations', [
        'registration_id' => $registration->registration_id,
        'status' => 'rejected',
        'notes' => 'Umur tidak mencukupi',
    ]);
});

/**
 * Skenario: Pembatasan Aturan Input Enum Status.
 * Prosedur: Memberi enum status asing, diluar (pending, verified, accepted, rejected).
 * Ekspektasi: Menjamin status tidak bisa diubah-ubah asal-asalan oleh pengguna akhir.
 */
test('update status validasi enum', function () {
    // Arrange: Menyertakan murid valid.
    $registration = Registration::factory()->create(['status' => 'pending']);

    // Act: Hit menggunakan status "unknown" fiktif.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/registrations/' . $registration->registration_id . '/status', [
        'status' => 'unknown',
    ]);

    // Assert: Harus mental pada validation 422.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
});

/**
 * Skenario: Penyaringan Koleksi Tahun Ajaran PPDB.
 * Prosedur: Melakukan inspeksi kelompok siswa yang mendaftar pada sebuah kalender pendidikan.
 * Ekspektasi: Respons terisolasi ke rentang tahun tersebut (Mis: 2024/2025).
 */
test('index filter by academic_year', function () {
    // Arrange: Buat pendaftar angkatan terpisah.
    Registration::factory()->create(['academic_year' => '2024/2025']);
    Registration::factory()->create(['academic_year' => '2025/2026']);

    // Act: Query parameter pada argumen academic_year disematkan.
    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations?academic_year=2024/2025');

    // Assert: Data bersih terpotong sesuai yang dibutuhkan admin.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['academic_year'])->toBe('2024/2025');
});

/**
 * Skenario: Fungsi Temu String Cepat pada Nama Calon.
 * Prosedur: Admin meresearch parameter "Ahmad" pada nama siswa di koleksi.
 * Ekspektasi: Array dikerucutkan secara instan berbasis LIKE keyword.
 */
test('index filter by search nama', function () {
    // Arrange: Mock data perbedaan kontras.
    Registration::factory()->create(['full_name' => 'Ahmad Rizky']);
    Registration::factory()->create(['full_name' => 'Budi Santoso']);

    // Act: Melempar kata kunci.
    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations?search=Ahmad');

    // Assert: Sesuai yang diharapkan.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['full_name'])->toBe('Ahmad Rizky');
});

/**
 * Skenario: Fungsi Temu Melalui Nomor Reg Unik Sistem.
 * Prosedur: Meneliti pencarian yang didasarkan prefix karakter registrasi (Unique identifier).
 * Ekspektasi: Mengantisipasi validasi dari dokumen pembayaran dan verifikasi fisik.
 */
test('index filter by search nomor registrasi', function () {
    // Arrange: Satu data dengan format reg tertentu.
    $reg = Registration::factory()->create();

    // Act: Membedah dengan hanya mengambil potongan nomor prefix-nya.
    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations?search=' . substr($reg->registration_number, 0, 8));

    // Assert: Hasil bisa lebih besar sama dengan karena pemotongan kemiripan prefix bisa berdampak pada banyak data, walau biasanya presisi 1.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});
