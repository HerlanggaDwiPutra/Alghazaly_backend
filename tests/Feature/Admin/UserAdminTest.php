<?php

/**
 * Suite Pengujian: Fitur Manajemen Pengguna (Admin Panel).
 *
 * Menguji fungsionalitas CRUD akun pengguna dan siklus otorisasi admin.
 * Memastikan bahwa admin memiliki kendali penuh atas manajemen akun,
 * termasuk pembuatan akun dengan password ter-hash, update profil, 
 * dan kontrol pengecualian (seperti tidak dapat menghapus akun sendiri).
 *
 * ATURAN BISNIS UTAMA:
 * - Seluruh endpoint harus memblokir akses tanpa autentikasi (401).
 * - Pembuatan akun harus mengamankan password menggunakan Hash/Bcrypt.
 * - Email harus unik (tidak boleh ada duplikasi akun).
 * - Admin dilarang keras menghapus akunnya sendiri (safeguard penghapusan mandiri).
 */

use App\Models\User;
use App\Models\Role;
use function Pest\Laravel\{getJson, postJson, patchJson, deleteJson, actingAs};

/**
 * Setup Global: Menggunakan global test helper (actingAs) untuk setiap tes
 * dalam blok admin ini, memastikan siklus otorisasi multi-role berjalan
 * dengan menyiapkan pengguna ber-role 'admin' yang sudah terautentikasi.
 */
beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->role_id, 'is_active' => true]);
});

/**
 * Skenario: Menampilkan Daftar Seluruh Pengguna.
 * Prosedur: Admin mengakses daftar akun pengguna di sistem.
 * Ekspektasi: Sistem mengembalikan data akun dengan HTTP 200.
 */
test('index menampilkan semua user', function () {
    // Arrange: Membuat 3 akun tambahan di database.
    User::factory()->count(3)->create();

    // Act: Mengakses endpoint daftar user menggunakan hak akses admin.
    $response = actingAs($this->adminUser)->getJson('/api/admin/users');

    // Assert: Endpoint dapat diakses (200 OK).
    $response->assertStatus(200);
});

/**
 * Skenario: Pembuatan Akun Baru (Happy Path).
 * Prosedur: Admin membuat akun pengguna baru dengan input yang valid.
 * Ekspektasi: Akun berhasil dibuat (201 Created) dan tersimpan di database.
 */
test('store user berhasil', function () {
    // Arrange: Membuat peran tambahan (editor) untuk di-assign pada user baru.
    $role = Role::factory()->create(['name' => 'editor']);

    // Act: Admin mensubmit payload pendaftaran akun baru.
    $response = actingAs($this->adminUser)->postJson('/api/admin/users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'role_id' => $role->role_id,
        'is_active' => true,
    ]);

    // Assert: HTTP 201 dan verifikasi bahwa email yang didaftarkan ada di database.
    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

/**
 * Skenario: Validasi Duplikasi Akun Berdasarkan Email.
 * Prosedur: Admin membuat akun menggunakan email yang sudah terdaftar.
 * Ekspektasi: Ditolak dengan pesan validasi (422) pada kolom email.
 */
test('store user email duplikat', function () {
    // Arrange: Menyiapkan satu pengguna dengan email yang sudah tersimpan di database.
    User::factory()->create(['email' => 'existing@example.com']);

    // Act: Mencoba mendaftarkan pengguna baru dengan email yang sama persis.
    $response = actingAs($this->adminUser)->postJson('/api/admin/users', [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'role_id' => $this->adminRole->role_id,
    ]);

    // Assert: Menolak dengan status 422 Unprocessable Entity khusus field 'email'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

/**
 * Skenario: Keamanan Kata Sandi — Hashing Password Secara Otomatis.
 * Prosedur: Memastikan password dari akun yang dibuat telah di-hash, tidak plain text.
 * Ekspektasi: Nilai field `password` dalam basis data berbeda dengan input mentah.
 */
test('store user password ter-hash', function () {
    // Arrange: Menyiapkan role 'staff'.
    $role = Role::factory()->create(['name' => 'staff']);

    // Act: Mensubmit registrasi dengan plain text password 'password123'.
    actingAs($this->adminUser)->postJson('/api/admin/users', [
        'name' => 'Hash User',
        'email' => 'hash@example.com',
        'password' => 'password123',
        'role_id' => $role->role_id,
    ]);

    // Assert: Mengambil record dan memastikan nilainya tidak sama dengan input mentah (telah ter-hash).
    $user = User::where('email', 'hash@example.com')->first();
    expect($user->password)->not->toBe('password123');
});

/**
 * Skenario: Pembaruan Informasi Akun.
 * Prosedur: Memperbarui kolom nama pengguna untuk sebuah akun.
 * Ekspektasi: Perubahan sukses dan nama baru diubah dalam sistem basis data.
 */
test('update user berhasil', function () {
    // Arrange: Menyiapkan profil pengguna dummy untuk diubah nilainya.
    $user = User::factory()->create(['name' => 'Old Name']);

    // Act: Admin mengubah nama dari akun tersebut.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/users/' . $user->id, [
        'name' => 'New Name',
    ]);

    // Assert: Respons 200 dan perubahan terdeteksi pada database.
    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});

/**
 * Skenario: Menghapus Akun Pengguna Lain.
 * Prosedur: Admin menghapus sebuah akun di dalam daftar.
 * Ekspektasi: Respons berhasil (200) dan entri terkait terhapus.
 */
test('destroy user berhasil', function () {
    // Arrange: Membuat profil pengguna.
    $user = User::factory()->create();

    // Act: Admin menembak endpoint delete untuk menendang pengguna dari database.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/users/' . $user->id);

    // Assert: Sukses dihapus dan memastikan record tidak dapat ditemukan kembali.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

/**
 * Skenario: Pengambilan Daftar Role.
 * Prosedur: Mengambil daftar tipe hak akses sebagai dropdown referensi form.
 * Ekspektasi: Respons daftar sukses.
 */
test('get roles', function () {
    // Arrange: Mempopulasi data peranan ganda di sistem database.
    Role::factory()->count(2)->create();

    // Act: Melakukan permintaan HTTP GET.
    $response = actingAs($this->adminUser)->getJson('/api/admin/roles');

    // Assert: Permintaan diterima oleh API.
    $response->assertStatus(200);
});

/**
 * Skenario: Detail Spesifik Pengguna.
 * Prosedur: Permintaan informasi detail pada satu akun.
 * Ekspektasi: JSON respons mengembalikan detail ID tersebut.
 */
test('show user detail', function () {
    // Arrange: Dummy pengguna.
    $user = User::factory()->create();

    // Act: Melihat entri spesifik.
    $response = actingAs($this->adminUser)->getJson('/api/admin/users/' . $user->id);

    // Assert: Cek konfirmasi.
    $response->assertStatus(200)
             ->assertJsonPath('id', $user->id);
});

/**
 * Skenario: Reset Password Pengguna oleh Admin.
 * Prosedur: Mengupdate string password baru.
 * Ekspektasi: Sistem menerima update dan melalukan validasi menggunakan fungsi Hash::check.
 */
test('update user dengan password baru', function () {
    // Arrange: Membuat profil akun.
    $user = User::factory()->create();

    // Act: Mereset paksa password.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/users/' . $user->id, [
        'password' => 'newpassword123',
    ]);

    // Assert: Proses berhasil dan verifikasi manual mencocokkan hash di sistem.
    $response->assertStatus(200);
    $updatedUser = User::find($user->id);
    expect(\Illuminate\Support\Facades\Hash::check('newpassword123', $updatedUser->password))->toBeTrue();
});

/**
 * Skenario: Safeguard Pemblokiran Penghapusan Akun Mandiri Admin.
 * Prosedur: Sebuah sesi admin mencoba menendang akunnya sendiri (Bunuh diri secara data).
 * Ekspektasi: Exception ditangani, diblokir oleh endpoint dengan respons error 403.
 *             Ini adalah kontrol kritis keamanan agar sistem tidak lumpuh karena kehilangan admin terakhir.
 */
test('destroy user sendiri ditolak (403)', function () {
    // Arrange: (Data diambil otomatis dari before_each block untuk user sesi ini).

    // Act: Mengirimkan perintah delete atas id diri sendiri secara sadar.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/users/' . $this->adminUser->id);

    // Assert: Memastikan pengecualian (exception) memberikan kode terlarang dan pesan keamanan terkait.
    $response->assertStatus(403)
             ->assertJson(['message' => 'Tidak dapat menghapus akun sendiri.']);
});
