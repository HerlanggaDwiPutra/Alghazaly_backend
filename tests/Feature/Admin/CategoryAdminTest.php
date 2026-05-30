<?php

/**
 * Suite Pengujian: Fitur Manajemen Metadata Kategori (Admin Panel).
 *
 * Menguji integrasi pembuatan node label (Kategori) dan penyusunan rantai struktur
 * taksonomi beranting (Hierarchical / Parent-Child Tree).
 *
 * ATURAN BISNIS UTAMA:
 * - API harus mampu mengakomodasi penarikan pohon rekursif via parameter `children`.
 * - Sistem generator Slug harus mengait ke atribut `category_name`.
 * - Referensi `parent_id` ke sub-root kategori harus bernilai valid, bukan arbitrary ID.
 */

use App\Models\Category;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

/**
 * Setup Global: Meloloskan kunci gerbang panel dengan mendaftarkan admin fiktif.
 */
beforeEach(function () {
    $this->adminUser = createAdminUser();
});

/**
 * Skenario: Pertahanan Layer Sistem Terhadap Hacker / Anonim.
 * Prosedur: Seseorang menyelinap ke url API tanpa mengantongi ID Token Sanctum.
 * Ekspektasi: Laravel mencegat lalu lintas dan meretur 401 Unauthenticated.
 */
test('akses tanpa auth ditolak', function () {
    // Act: Mencoba meraih struktur daftar hirarki kategori secara ilegal.
    $response = getJson('/api/admin/categories');

    // Assert: Status diblok.
    $response->assertStatus(401);
});

/**
 * Skenario: Merender Akar Node beserta Anaknya (Hierarki Terstruktur).
 * Prosedur: Admin membuka dashboard daftar label kategori artikel.
 * Ekspektasi: Objek Root kategori memiliki tangkai referensi (Key object 'children').
 */
test('index menampilkan root categories dengan children', function () {
    // Arrange: Mendesain pohon silsilah, Induk tak punya parent_id, Anak menginduk ke ID parent-nya.
    $parent = Category::factory()->create(['parent_id' => null]);
    Category::factory()->create(['parent_id' => $parent->category_id]);

    // Act: Hit request get.
    $response = actingAs($this->adminUser)->getJson('/api/admin/categories');

    // Assert: Harus mengembalikan 1 elemen basis atas (Root) yang membawa properti sisipan turunan ('children').
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0])->toHaveKey('children');
});

/**
 * Skenario: Penetapan Label Root Kategori Pertama.
 * Prosedur: Admin menyusun subjek klasifikasi "Teknologi" (parent_id = null by default).
 * Ekspektasi: Generator laravel memformat entitas nama menjadi string slug bersih "teknologi".
 */
test('store kategori berhasil', function () {
    // Act: Mengumpankan value "Teknologi" dari body form POST.
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', [
        'category_name' => 'Teknologi',
    ]);

    // Assert: Data memori persisten sukses ditenun dengan rapih di DB.
    $response->assertStatus(201);
    $this->assertDatabaseHas('categories', [
        'category_name' => 'Teknologi',
        'slug'          => 'teknologi',
    ]);
});

/**
 * Skenario: Proteksi Pencegahan Label Kategori Palsu (Anonymous).
 * Prosedur: Men-submit pembuatan form tanpa nama.
 * Ekspektasi: Dilarang keras oleh constraint rule input database.
 */
test('store kategori validasi name wajib', function () {
    // Act: Meloloskan form dengan array property string body yang berlubang/kosong.
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', []);

    // Assert: Modul Request Form memproteksi secara aktif (422 error logic).
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['category_name']);
});

/**
 * Skenario: Eksekusi Penanaman Benih Label Kategori Berantai (Sub-Kategori).
 * Prosedur: Mendaftarkan payload yang membawa argumen Foreign Key `parent_id` miliki induk sah.
 * Ekspektasi: Tersusun secara terikat oleh basis relasi referensial MySQL.
 */
test('store kategori dengan parent_id', function () {
    // Arrange: Dummy rujukan Induk.
    $parent = Category::factory()->create();

    // Act: Penyisipan value Induk-Tangkai Anak (Child Node Submission).
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', [
        'category_name' => 'Sub Kategori',
        'parent_id'     => $parent->category_id,
    ]);

    // Assert: Record taksonomi terdaftar mulus tanpa penolakan foreign constraint constraint.
    $response->assertStatus(201);
    $this->assertDatabaseHas('categories', [
        'category_name' => 'Sub Kategori',
        'parent_id'     => $parent->category_id,
    ]);
});

/**
 * Skenario: Penolakan Rujukan Induk Asing (Invalid Parent Node).
 * Prosedur: Mencoba mendaftarkan anak dengan menancapkannya ke ID kategori yang fana/ngawur (9999).
 * Ekspektasi: Rule `exists:categories,category_id` melempar validator merah, mencegah yatim-data (Orphan Records).
 */
test('store kategori parent_id invalid (422)', function () {
    // Act: Injeksi param ID 9999 fiktif.
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', [
        'category_name' => 'Test',
        'parent_id'     => 9999,
    ]);

    // Assert: Kesalahan relasi dideteksi dini (422 Unprocessable Entity).
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['parent_id']);
});

/**
 * Skenario: Modifikasi Judul Node (Edit Label).
 * Prosedur: Melemparkan verb PUT menggantikan instansi lama.
 * Ekspektasi: Kedua entitas properti (name & slug) ikut berganti di background dan front-face.
 */
test('update kategori berhasil', function () {
    // Arrange: Kategori orisinil usang.
    $category = Category::factory()->create(['category_name' => 'Lama']);

    // Act: Tembakan update PUT.
    $response = actingAs($this->adminUser)->putJson('/api/admin/categories/' . $category->category_id, [
        'category_name' => 'Baru',
    ]);

    // Assert: Sinkronisasi pembaruan DB mutlak sukses (Slug pun auto-berubah menjadi 'baru').
    $response->assertStatus(200);
    $this->assertDatabaseHas('categories', [
        'category_id'   => $category->category_id,
        'category_name' => 'Baru',
        'slug'          => 'baru',
    ]);
});

/**
 * Skenario: Sinkronisasi Transformasi String-Slug.
 * Prosedur: Hanya mengupdate nama yang berbeda format/frasanya.
 * Ekspektasi: Field tersembunyi parameter `slug` otomatis ter-regenerate di model observer/boot trait secara diam-diam.
 */
test('update kategori name memperbarui slug', function () {
    // Arrange: Skenario dummy title dan slug bawaan.
    $category = Category::factory()->create(['category_name' => 'Old Name', 'slug' => 'old-name']);

    // Act: Merombak properti teksnya melalui endpoint API.
    actingAs($this->adminUser)->putJson('/api/admin/categories/' . $category->category_id, [
        'category_name' => 'New Updated Name',
    ]);

    // Assert: Memastikan string spasi diubah murni ke susunan strip untuk menghindari link buntu (Broken Link).
    $this->assertDatabaseHas('categories', [
        'category_id' => $category->category_id,
        'slug'        => 'new-updated-name',
    ]);
});

/**
 * Skenario: Pelenyapan Tabel Label/Klasifikasi (Delete Row).
 * Prosedur: Admin menghapus sebuah folder kategori (Contoh: Menghapus label "Kegiatan").
 * Ekspektasi: Hilang terbuang dari kumpulan tabel di MySql.
 */
test('destroy kategori berhasil', function () {
    // Arrange: Dummy taksonomi yang menjadi target tumbal.
    $category = Category::factory()->create();

    // Act: Kirim surat izin kematian (DELETE request).
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/categories/' . $category->category_id);

    // Assert: Sukses dihapus, dan row tidak tertinggal.
    $response->assertStatus(200);
    $this->assertDatabaseMissing('categories', ['category_id' => $category->category_id]);
});

/**
 * Skenario: Kesalahan Perintah Delete Terhadap Hantu Entitas.
 * Prosedur: Mencoba menghapus baris dari indeks fiktif yang tidak ada.
 * Ekspektasi: Diterjang balasan error UI/UX sopan (404 Not Found), bukan Exception sistem internal fatal (500).
 */
test('destroy kategori tidak ada (404)', function () {
    // Act: Hit tak bertuan.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/categories/9999');

    // Assert: Handle model missing exception tereksekusi mulus.
    $response->assertStatus(404);
});
