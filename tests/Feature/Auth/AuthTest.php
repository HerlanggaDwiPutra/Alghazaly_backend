<?php

/**
 * Suite Pengujian: Autentikasi API — Gerbang Keamanan dan Hak Akses.
 *
 * Berkas ini menguji seluruh alur autentikasi berbasis token Sanctum yang
 * melindungi endpoint panel admin SMA Al Ghazaly.
 *
 * MEKANISME YANG DIUJI:
 * Sistem menggunakan Laravel Sanctum dengan Personal Access Tokens (PAT).
 * Setiap login yang berhasil menerbitkan token Bearer yang harus disertakan
 * pada header `Authorization` di setiap request ke endpoint yang dilindungi.
 *
 * SKENARIO KEAMANAN TAMBAHAN:
 * - Akun dengan `is_active = false` ditolak meskipun password-nya benar,
 *   memberikan kontrol blokir akun tanpa perlu menghapus data pengguna.
 * - Token dicabut (di-revoke) saat logout, sehingga token lama tidak dapat
 *   digunakan kembali untuk mengakses endpoint yang dilindungi.
 */

use App\Models\User;
use function Pest\Laravel\{postJson, getJson};

/**
 * Skenario: Login Berhasil — Happy Path dengan Kredensial Valid.
 * Prosedur: Memberikan email dan password yang tepat untuk akun yang aktif.
 * Ekspektasi: Sistem menerbitkan token Bearer Sanctum dan mengembalikan data user
 *             sebagai konfirmasi bahwa autentikasi berjalan dengan benar.
 */
test('login dengan kredensial valid', function () {
    // Arrange: Membuat user dengan password yang di-hash menggunakan bcrypt.
    //          Flag is_active = true memastikan akun tidak dalam status diblokir.
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    // Act: Mengirimkan POST request ke endpoint login dengan kredensial yang valid.
    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    // Assert: Respons berisi 'token' (Bearer Sanctum) dan 'user' (data akun).
    //         Kehadiran kedua kunci ini membuktikan login berhasil dan token diterbitkan.
    $response->assertStatus(200)
             ->assertJsonStructure(['token', 'user']);
});

/**
 * Skenario: Login Gagal — Password Salah (Sad Path).
 * Prosedur: Menggunakan email yang benar tetapi password yang berbeda dari yang tersimpan.
 * Ekspektasi: Sistem mengembalikan HTTP 401 dengan pesan error generik untuk mencegah
 *             enumerasi akun (tidak memberi tahu apakah email atau password yang salah).
 */
test('login gagal password salah', function () {
    // Arrange: Membuat user valid, tetapi request akan menggunakan password yang salah.
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    // Act: Mengirim request login dengan password yang tidak cocok.
    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    // Assert: HTTP 401 dengan pesan generik — tidak membocorkan field mana yang salah.
    $response->assertStatus(401)
             ->assertJson(['message' => 'Kredensial tidak valid.']);
});

/**
 * Skenario: Login Ditolak — Akun Dinonaktifkan (Kontrol Blokir Akun).
 * Prosedur: Mencoba login dengan password yang benar, tetapi akun berstatus is_active = false.
 * Ekspektasi: Sistem menolak akses meskipun password benar, membuktikan bahwa mekanisme
 *             pemblokiran akun aktif dan berfungsi sebagai lapisan keamanan tambahan.
 */
test('login gagal user tidak aktif', function () {
    // Arrange: Membuat user dengan password yang valid tetapi is_active = false.
    //          Skenario ini mensimulasikan admin yang menonaktifkan akun yang bermasalah.
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    // Act: Mengirim request login dengan password yang benar.
    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    // Assert: HTTP 401 — akun non-aktif diperlakukan sama dengan kredensial salah
    //         untuk menghindari kebocoran informasi status akun.
    $response->assertStatus(401)
             ->assertJson(['message' => 'Kredensial tidak valid.']);
});

/**
 * Skenario: Validasi Input — Field Email Wajib Diisi.
 * Prosedur: Mengirimkan request login tanpa field 'email' sama sekali.
 * Ekspektasi: HTTP 422 dengan pesan validasi khusus untuk field 'email'.
 */
test('login validasi email wajib', function () {
    // Arrange: Tidak ada user yang perlu dibuat; tes ini murni validasi input.

    // Act: Mengirim request login tanpa field email.
    $response = postJson('/api/auth/login', [
        'password' => 'password123',
    ]);

    // Assert: HTTP 422 dengan error validasi yang menunjuk ke field 'email'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

/**
 * Skenario: Validasi Input — Format Email Harus Valid (RFC 5321).
 * Prosedur: Mengirimkan string yang bukan format email sebagai nilai field 'email'.
 * Ekspektasi: HTTP 422 — sistem menolak format email yang tidak valid.
 */
test('login validasi email format', function () {
    // Arrange: Tidak ada user yang diperlukan; tes murni tentang validasi format.

    // Act: Mengirim 'bukan-email' sebagai nilai email — string tanpa karakter '@'.
    $response = postJson('/api/auth/login', [
        'email' => 'bukan-email',
        'password' => 'password123',
    ]);

    // Assert: HTTP 422 karena nilai tidak memenuhi aturan validasi `email` Laravel.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

/**
 * Skenario: Validasi Input — Field Password Wajib Diisi.
 * Prosedur: Mengirimkan request login tanpa field 'password'.
 * Ekspektasi: HTTP 422 dengan pesan validasi khusus untuk field 'password'.
 */
test('login validasi password wajib', function () {
    // Arrange: Tidak ada user yang diperlukan.

    // Act: Mengirim request dengan email saja, tanpa password.
    $response = postJson('/api/auth/login', [
        'email' => 'test@example.com',
    ]);

    // Assert: HTTP 422 dengan error validasi menunjuk ke field 'password'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['password']);
});

/**
 * Skenario: Logout Berhasil — Pencabutan (Revocation) Token Sanctum.
 * Prosedur: User yang sudah login (memiliki token valid) meminta logout.
 * Ekspektasi: Token dicabut dari database dan sistem mengkonfirmasi logout berhasil.
 *             Token yang sama tidak dapat digunakan lagi untuk request berikutnya.
 */
test('logout berhasil', function () {
    // Arrange: Membuat user dan menerbitkan token Sanctum secara manual
    //          untuk mensimulasikan sesi login yang sedang aktif.
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    // Act: Mengirim request logout dengan menyertakan token Bearer yang valid.
    $response = postJson('/api/auth/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    // Assert: HTTP 200 dan pesan konfirmasi logout berhasil.
    //         Di balik layar, token dihapus dari tabel 'personal_access_tokens'.
    $response->assertStatus(200)
             ->assertJson(['message' => 'Berhasil logout.']);
});

/**
 * Skenario: Logout Ditolak — Request Tanpa Token (Unauthenticated).
 * Prosedur: Mengirimkan request logout tanpa menyertakan header Authorization.
 * Ekspektasi: HTTP 401 — middleware Sanctum menolak akses sebelum mencapai handler logout.
 */
test('logout tanpa token', function () {
    // Arrange: Tidak diperlukan setup apapun.

    // Act: Mengirim request ke endpoint logout tanpa header Authorization.
    $response = postJson('/api/auth/logout');

    // Assert: HTTP 401 — Sanctum middleware memblokir request tanpa Bearer token.
    $response->assertStatus(401);
});

/**
 * Skenario: Endpoint `/me` — Mendapatkan Profil User yang Sedang Login.
 * Prosedur: User yang sudah terotentikasi meminta data profilnya sendiri.
 * Ekspektasi: Sistem mengembalikan data user yang sesuai dengan pemilik token Bearer.
 */
test('me mengembalikan data user', function () {
    // Arrange: Membuat user dan menerbitkan token Sanctum yang valid.
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    // Act: Mengirim GET request ke endpoint `/me` dengan Bearer token yang valid.
    $response = getJson('/api/auth/me', [
        'Authorization' => 'Bearer ' . $token,
    ]);

    // Assert: Respons berisi 'id' dan 'email' yang cocok dengan user pemilik token.
    //         Ini memverifikasi bahwa Sanctum me-resolve user yang benar dari token.
    $response->assertStatus(200)
             ->assertJson(['id' => $user->id, 'email' => $user->email]);
});

/**
 * Skenario: Endpoint `/me` Ditolak — Akses Tanpa Token.
 * Prosedur: Mengirimkan GET request ke endpoint `/me` tanpa header Authorization.
 * Ekspektasi: HTTP 401 — endpoint profil terproteksi oleh middleware Sanctum.
 */
test('me tanpa token', function () {
    // Arrange: Tidak ada setup.

    // Act: Mengirim GET request ke endpoint profil tanpa autentikasi.
    $response = getJson('/api/auth/me');

    // Assert: HTTP 401 membuktikan bahwa endpoint tidak dapat diakses secara anonim.
    $response->assertStatus(401);
});
