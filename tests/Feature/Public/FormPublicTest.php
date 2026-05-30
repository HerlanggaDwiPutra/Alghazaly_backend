<?php

/**
 * Suite Pengujian: Endpoint Publik Formulir Kontak & Submisi (Dynamic Form).
 *
 * Menguji sistem formulir dinamis yang dikelola melalui CMS admin, mencakup
 * pengambilan form aktif dan pengiriman data oleh pengunjung publik.
 *
 * ARSITEKTUR CMS YANG DIUJI:
 * - Form dikelola di panel admin dan diaktifkan/nonaktifkan via flag `is_active`.
 * - Pengunjung publik mengambil form berdasarkan slug dan mengirimkan data
 *   dalam format JSON fleksibel (kolom `data` bertipe JSON).
 * - `submitter_email` bersifat opsional — berguna untuk mengirim konfirmasi via email.
 */

use App\Models\Form;
use App\Models\FormSubmission;
use function Pest\Laravel\{getJson, postJson};

/**
 * Skenario: Menampilkan Form Aktif — Happy Path.
 * Prosedur: Membuat form dengan is_active = true dan mengaksesnya via slug.
 * Ekspektasi: HTTP 200 dengan data form yang sesuai slug yang diminta.
 */
test('show form aktif by slug', function () {
    // Arrange: Membuat form aktif yang sudah siap digunakan oleh pengunjung.
    Form::factory()->create(['slug' => 'kontak-kami', 'is_active' => true]);

    // Act: GET request ke endpoint form berdasarkan slug.
    $response = getJson('/api/forms/kontak-kami');

    // Assert: HTTP 200 dengan slug yang cocok dalam response.
    $response->assertStatus(200)
             ->assertJsonPath('slug', 'kontak-kami');
});

/**
 * Skenario: Keamanan — Form Tidak Aktif Tidak Bisa Diakses (404).
 * Prosedur: Membuat form dengan is_active = false dan mencoba mengaksesnya.
 * Ekspektasi: HTTP 404 — form nonaktif disembunyikan dari pengunjung publik,
 *             mencegah akses ke form yang sedang dalam maintenance atau drafting.
 */
test('show form tidak aktif (404)', function () {
    // Arrange: Form yang sudah dinonaktifkan oleh admin.
    Form::factory()->create(['slug' => 'form-nonaktif', 'is_active' => false]);

    // Act: Mencoba mengakses form nonaktif via slug.
    $response = getJson('/api/forms/form-nonaktif');

    // Assert: HTTP 404 — form nonaktif diperlakukan sebagai tidak ditemukan.
    $response->assertStatus(404);
});

/**
 * Skenario: Form Tidak Ditemukan — Slug Tidak Ada (404).
 * Prosedur: Mengakses slug form yang tidak terdaftar di database.
 * Ekspektasi: HTTP 404 — slug tidak valid menghasilkan not found bukan error 500.
 */
test('show form slug tidak ada (404)', function () {
    // Arrange: Tidak ada form yang dibuat.

    // Act: GET request dengan slug yang tidak ada di database.
    $response = getJson('/api/forms/slug-tidak-ada');

    // Assert: HTTP 404.
    $response->assertStatus(404);
});

/**
 * Skenario: Submit Form — Pengiriman Data Berhasil.
 * Prosedur: Pengunjung mengirimkan data ke form yang aktif.
 * Ekspektasi: HTTP 201, pesan sukses, dan record FormSubmission tersimpan di database.
 *             Kolom `data` menyimpan payload JSON dari pengunjung secara fleksibel.
 */
test('submit form berhasil', function () {
    // Arrange: Membuat form aktif yang siap menerima submisi.
    $form = Form::factory()->create(['slug' => 'hubungi-kami', 'is_active' => true]);

    // Act: POST request ke endpoint submit form dengan data JSON.
    $response = postJson('/api/forms/hubungi-kami/submit', [
        'data' => ['nama' => 'Test', 'email' => 'test@mail.com', 'pesan' => 'Halo'],
    ]);

    // Assert: HTTP 201 dengan pesan sukses dan record submisi tersimpan di database.
    $response->assertStatus(201)
             ->assertJson(['message' => 'Pesan berhasil dikirim.']);

    $this->assertDatabaseHas('form_submissions', [
        'form_id' => $form->form_id,
    ]);
});

/**
 * Skenario: Validasi Submit — Field `data` Wajib Ada dalam Payload.
 * Prosedur: Mengirimkan request submit tanpa field 'data'.
 * Ekspektasi: HTTP 422 — field 'data' adalah required karena merupakan isi submisi form.
 */
test('submit form validasi data wajib', function () {
    // Arrange: Membuat form aktif.
    Form::factory()->create(['slug' => 'form-validasi', 'is_active' => true]);

    // Act: Mengirim payload kosong ke endpoint submit.
    $response = postJson('/api/forms/form-validasi/submit', []);

    // Assert: HTTP 422 dengan error validasi untuk field 'data'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['data']);
});

/**
 * Skenario: Submit ke Form Nonaktif — Ditolak (404).
 * Prosedur: Mencoba submit ke form yang sudah dinonaktifkan oleh admin.
 * Ekspektasi: HTTP 404 — endpoint submit juga memeriksa status aktif form,
 *             mencegah data masuk ke form yang sudah ditutup.
 */
test('submit form slug tidak aktif (404)', function () {
    // Arrange: Form sudah dinonaktifkan.
    Form::factory()->create(['slug' => 'form-mati', 'is_active' => false]);

    // Act: Mencoba submit data ke form yang nonaktif.
    $response = postJson('/api/forms/form-mati/submit', [
        'data' => ['nama' => 'Test'],
    ]);

    // Assert: HTTP 404 — form nonaktif menolak submisi baru.
    $response->assertStatus(404);
});

/**
 * Skenario: Submit Form dengan Email Pengirim (Opsional).
 * Prosedur: Pengunjung menyertakan `submitter_email` sebagai field tambahan opsional.
 * Ekspektasi: HTTP 201 dan `submitter_email` tersimpan di database untuk keperluan
 *             pengiriman konfirmasi email kepada pengunjung.
 */
test('submit form dengan email submitter', function () {
    // Arrange: Form aktif yang memungkinkan pengisian email pengirim.
    $form = Form::factory()->create(['slug' => 'form-email', 'is_active' => true]);

    // Act: Submit form dengan menyertakan field opsional submitter_email.
    $response = postJson('/api/forms/form-email/submit', [
        'data'            => ['nama' => 'Test'],
        'submitter_email' => 'submitter@mail.com',
    ]);

    // Assert: HTTP 201 dan email pengirim tersimpan untuk keperluan notifikasi.
    $response->assertStatus(201);

    $this->assertDatabaseHas('form_submissions', [
        'form_id'         => $form->form_id,
        'submitter_email' => 'submitter@mail.com',
    ]);
});
