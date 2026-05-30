<?php

/**
 * Suite Pengujian: Fitur Kurasi Testimonial (Admin Panel).
 *
 * Menguji fungsionalitas admin dalam memoderasi, memanipulasi, atau mengizinkan ulasan 
 * dan feedback pihak eksternal/internal (Testimonial).
 *
 * ATURAN BISNIS UTAMA:
 * - Kredibilitas review (testimonial) diregulasi admin (CRUD access penuh).
 * - Terdapat constraint/batas skala hitung pada `rating` (Minimal bintang 1, Maksimal 5).
 * - Isi parameter content (narasi) dan name adalah wajib mutlak bagi sebuah testimonial.
 */

use App\Models\Testimonial;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

/**
 * Setup Global: Pendaftaran status otorisasi admin untuk memutar kunci gerbang
 * antarmuka panel administrasi.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Konfirmasi Restriksi Pengunjung Eksternal/Non-Admin.
 * Prosedur: Membaca entri rute tanpa bekal token dari sistem (Anonim).
 * Ekspektasi: Diblokir dari pengambilan list kontrol moderasi 401.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Memancing gerbang sekuriti dengan request GET mentah.
    $response = getJson('/api/admin/testimonials');

    // Assert: Tertahan dengan aman.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Panel Antrean Ulasan Moderasi.
 * Prosedur: Admin menarik arsip database testimonial secara masif.
 * Ekspektasi: Daftar data array ditarik lancar dengan HTTP 200.
 */
test('index menampilkan semua testimonial', function () {
    // Arrange: Masukkan 3 ulasan palsu via Model Factory.
    Testimonial::factory()->count(3)->create();

    // Act: Hit ke rute direktori utama testimonials.
    $response = actingAs($this->adminUser)->getJson('/api/admin/testimonials');

    // Assert: Tiga baris respons terbaca dari json array.
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

/**
 * Skenario: Rekayasa Publikasi Testimonial dari Admin (Happy Path).
 * Prosedur: Menambahkan ulasan baru milik wali murid secara tertulis via dashboard.
 * Ekspektasi: Sukses disimpan di server (Created 201).
 */
test('store testimonial berhasil', function () {
    // Act: Melakukan metode post data berupa field name dan content.
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'name'    => 'Budi Santoso',
        'content' => 'Sekolah ini sangat baik.',
    ]);

    // Assert: Jejak operasi persisten berhasil terpindai DB.
    $response->assertStatus(201);
    $this->assertDatabaseHas('testimonials', ['name' => 'Budi Santoso']);
});

/**
 * Skenario: Penolakan Rekayasa Data Hantu (Atribut Nama Kosong).
 * Prosedur: Meniadakan objek `name` dari transmisi JSON POST.
 * Ekspektasi: Penjegalan data tak bernama sebelum di-insert.
 */
test('store testimonial validasi name wajib', function () {
    // Act: Submisi dengan isi narasi namun identitas pengulas absen.
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'content' => 'Isi testimonial',
    ]);

    // Assert: FormRequest validasi berteriak error 422 khusus array indeks 'name'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

/**
 * Skenario: Penolakan Ulasan Hampa/Bisu (Atribut Narasi Kosong).
 * Prosedur: Membuat baris ulasan namun membuang atribut `content` text-nya.
 * Ekspektasi: API mencegah karena content (Isi) adalah inti dari sebuah testimonial.
 */
test('store testimonial validasi content wajib', function () {
    // Act: Mensubmit nama tetapi review teks tak terlampir.
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'name' => 'Test',
    ]);

    // Assert: Pengecualian Validator muncul pada flag 'content'.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['content']);
});

/**
 * Skenario: Skala Rating Over/Underflow (Atribut Out of Bounds).
 * Prosedur: Admin menyodorkan bintang bernilai '0' (Atau 6,7) diluar skala rating baku.
 * Ekspektasi: Sistem membentengi data sampah, parameter rating dipaksa dalam range numerik 1-5.
 */
test('store testimonial validasi rating min 1 max 5', function () {
    // Act: Mensubmit value int (0) untuk menjebol batas logika skala di DB.
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'name'    => 'Test',
        'content' => 'Isi',
        'rating'  => 0,
    ]);

    // Assert: Filter rentang angka (Min:1, Max:5) menggagalkannya (422).
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['rating']);
});

/**
 * Skenario: Penggantian Subyek Penulis Ulasan (Modifikasi).
 * Prosedur: Merevisi nama pengulas yang barangkali ada typo ejaan huruf.
 * Ekspektasi: Skema relasional baris mysql ter-sinkronisasi sempurna dan nilai field berganti.
 */
test('update testimonial berhasil', function () {
    // Arrange: Berikan value target edit "Lama".
    $testimonial = Testimonial::factory()->create(['name' => 'Lama']);

    // Act: Menyuntikkan pengganti nama via PUT verb payload.
    $response = actingAs($this->adminUser)->putJson('/api/admin/testimonials/' . $testimonial->testimonial_id, [
        'name' => 'Baru',
    ]);

    // Assert: Pengecekan silang membuktikan kata "Baru" sukses diimplan pada ID bersangkutan.
    $response->assertStatus(200);
    $this->assertDatabaseHas('testimonials', ['testimonial_id' => $testimonial->testimonial_id, 'name' => 'Baru']);
});

/**
 * Skenario: Pencabutan Feedback dan Ulasan dari Server.
 * Prosedur: Admin menganggap sebuah review usang/spam, lalu melakukan tembakan penghapusan.
 * Ekspektasi: Data sirna tanpa ada rekaman tersisa pada antrean Testimonial (Data Missing).
 */
test('destroy testimonial berhasil', function () {
    // Arrange: Dummy untuk obyek penghapusan murni.
    $testimonial = Testimonial::factory()->create();

    // Act: Hit target memakai jalur DELETE method.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/testimonials/' . $testimonial->testimonial_id);

    // Assert: Hilang, tak ditemukan kembali di DB (200 OK).
    $response->assertStatus(200);
    $this->assertDatabaseMissing('testimonials', ['testimonial_id' => $testimonial->testimonial_id]);
});

/**
 * Skenario: Perintah Hapus Fana.
 * Prosedur: ID invalid di hit secara DELETE verb.
 * Ekspektasi: Gagal menghapus tanpa menyebabkan sistem down (Safe Fail).
 */
test('destroy testimonial tidak ada (404)', function () {
    // Act: Hit pada zona kosong indeks.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/testimonials/9999');

    // Assert: Penanganan wajar API Rest standar 404.
    $response->assertStatus(404);
});
