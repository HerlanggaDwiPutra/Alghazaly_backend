<?php

/**
 * Suite Pengujian: Fitur Manajemen Berita dan Artikel Sekolah (Admin Panel).
 *
 * Menguji seluruh kapabilitas pengelolaan CMS konten `Post`, mulai dari proses draf,
 * penerbitan tulisan (publish), pengelompokan kategori (sync), hingga penghapusan arsip.
 *
 * ATURAN BISNIS UTAMA:
 * - Admin secara eksklusif menggunakan sistem ini untuk menaikkan/menurunkan (unpublish) artikel.
 * - Kolom `slug` harus dapat dihasilkan (generate) otomatis berdasarkan judul jika tidak diset,
 *   sekaligus diperbarui ketika judul diubah.
 * - Kolom `published_at` akan terekam otomatis pada saat transisi ke status 'published',
 *   namun tidak boleh tertimpa jika artikel tersebut diedit di lain waktu.
 */

use App\Models\User;
use App\Models\Role;
use App\Models\Post;
use App\Models\Category;
use function Pest\Laravel\{getJson, postJson, patchJson, deleteJson, actingAs};

/**
 * Setup Global: Konfigurasi standar user bersistem admin untuk membuka paksa
 * seluruh limitasi (Otorisasi).
 */
beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->role_id, 'is_active' => true]);
});

/**
 * Skenario: Proteksi Halaman Manajemen Artikel.
 * Prosedur: Bypass tanpa header akses dari token manapun.
 * Ekspektasi: Server menendang pengguna ke status tidak terotorisasi (401).
 */
test('akses tanpa auth ditolak', function () {
    // Arrange: Cukup dengan tidak melakukan mapping auth actingAs().
    
    // Act: Hit ke rute resource.
    $response = getJson('/api/admin/posts');

    // Assert: Kunci gerbang menolak request.
    $response->assertStatus(401);
});

/**
 * Skenario: Tampilan Antrean Seluruh Artikel CMS.
 * Prosedur: Admin membuka daftar riwayat publikasinya.
 * Ekspektasi: Respons paginasi laravel menyajikan data.
 */
test('index menampilkan semua post', function () {
    // Arrange: Memberikan pasokan dummy tulisan dari author admin bersangkutan.
    Post::factory()->count(3)->create(['author_id' => $this->adminUser->id]);

    // Act: Meretur request ke resource list.
    $response = actingAs($this->adminUser)->getJson('/api/admin/posts');

    // Assert: Sesuai harapan paginasi standar.
    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'first_page_url', 'last_page', 'links', 'per_page', 'total']);
});

/**
 * Skenario: Fitur Tab Filtrasi (Semua / Published / Draft).
 * Prosedur: Admin menyaring dan hanya melihat antrean 'draft'.
 * Ekspektasi: Berita/tulisan yang telah diterbitkan disingkirkan dari daftar hasil.
 */
test('index filter by status', function () {
    // Arrange: Dua kombinasi state konten CMS.
    Post::factory()->create(['author_id' => $this->adminUser->id, 'status' => 'draft']);
    Post::factory()->create(['author_id' => $this->adminUser->id, 'status' => 'published']);

    // Act: Membubuhkan query string penyaringan `?status=draft`.
    $response = actingAs($this->adminUser)->getJson('/api/admin/posts?status=draft');

    // Assert: Hanya memuat tulisan berstatus draft.
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('draft');
});

/**
 * Skenario: Eksekusi Fitur Kotak Pencarian Artikel.
 * Prosedur: Mencari spesifik dengan query teks.
 * Ekspektasi: Menyocokkan kata kunci terhadap pangkalan data judul.
 */
test('index filter by search', function () {
    // Arrange: Modifikasi judul beda frasa.
    Post::factory()->create(['author_id' => $this->adminUser->id, 'title' => 'Belajar Pest']);
    Post::factory()->create(['author_id' => $this->adminUser->id, 'title' => 'Tutorial Laravel']);

    // Act: Menggunakan keyword "Pest".
    $response = actingAs($this->adminUser)->getJson('/api/admin/posts?search=Pest');

    // Assert: Algoritma menangkap "Belajar Pest".
    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['title'])->toBe('Belajar Pest');
});

/**
 * Skenario: Pembuatan Artikel Baru (Draft awal).
 * Prosedur: Admin menekan tombol simpan tanpa publikasi.
 * Ekspektasi: Data diterima aman ke DB dengan menyambungkan admin saat ini sebagai kolumnis/authornya.
 */
test('store post berhasil', function () {
    // Arrange: Tanpa Setup.
    
    // Act: Payload form pembuatan artikel baru disebar ke POST.
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Judul Baru',
        'content' => 'Isi konten post baru.',
        'status' => 'draft',
    ]);

    // Assert: Berhasil diterima, auto assign author_id, dan generator bekerja baik.
    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', [
        'title' => 'Judul Baru',
        'slug' => 'judul-baru',
        'status' => 'draft',
        'author_id' => $this->adminUser->id,
    ]);
});

/**
 * Skenario: Mutasi URL Slug Otomatis Saat Pembuatan.
 * Prosedur: Membuat artikel tanpa mengisi field slug.
 * Ekspektasi: Sistem mengambil inisiatif melalui class Str::slug() merubah string spasi ke garis penghubung.
 */
test('store post slug digenerate otomatis', function () {
    // Arrange: Blank data.

    // Act: Pengunggahan judul penuh spasi.
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Judul Test',
        'content' => 'Isi konten',
        'status' => 'draft',
    ]);

    // Assert: Spasi dikonversi aman sehingga cocok untuk param routing SEO friendly.
    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', ['slug' => 'judul-test']);
});

/**
 * Skenario: Tolakan Pembuatan Artikel Anonim (Tanpa Judul).
 * Prosedur: Memaksa penerbitan CMS polos.
 * Ekspektasi: Aturan require melarangnya.
 */
test('store post validasi title wajib', function () {
    // Arrange: Kosong.
    
    // Act: Membiarkan objek properti title bolong.
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'content' => 'Isi',
        'status' => 'draft',
    ]);

    // Assert: Validator membentengi dengan 422 Unprocessable Entity.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
});

/**
 * Skenario: Pengekangan Status Entitas Berita.
 * Prosedur: Menyuntikkan status aneh yang tidak termap 'enum' ('draft', 'published', 'archived').
 * Ekspektasi: Kesalahan logika UI diblokir controller secara aman.
 */
test('store post validasi status enum', function () {
    // Arrange: Kosong.

    // Act: Status unknown yang salah koding secara front-end.
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Test',
        'content' => 'Isi',
        'status' => 'unknown',
    ]);

    // Assert: Menghasilkan teguran enum invalid.
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
});

/**
 * Skenario: Pengaitan Relasi (Many to Many) Artikel Terhadap Kategori.
 * Prosedur: Saat post dicreate, label kategori dilampirkan menggunakan list ID pada parameter `categories`.
 * Ekspektasi: Laravel secara rapi membedah array ID tersebut untuk disimpan pada tabel pivot (Pivot Sync).
 */
test('store post dengan kategori', function () {
    // Arrange: 2 klasifikasi (Kategori) palsu.
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();

    // Act: Menyisipkan payload id kategori sebagai relasi array.
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Post Kategori',
        'content' => 'Isi konten',
        'status' => 'draft',
        'categories' => [$category1->category_id, $category2->category_id],
    ]);

    // Assert: Record pada tabel perantara tercipta sesuai jumlahnya (2 items).
    $response->assertStatus(201);
    $post = Post::where('title', 'Post Kategori')->first();
    expect($post->categories()->count())->toBe(2);
});

/**
 * Skenario: Edit Panel Admin Pada Postingan Khusus.
 * Prosedur: Mengambil balasan rincian data (raw response detail).
 * Ekspektasi: Payload lengkap dimuat demi mengisi kotak textbox UI form secara pre-filled.
 */
test('show post admin', function () {
    // Arrange: Inisiasi data milik admin.
    $post = Post::factory()->create(['author_id' => $this->adminUser->id]);

    // Act: Get spesifik url.
    $response = actingAs($this->adminUser)->getJson('/api/admin/posts/' . $post->post_id);

    // Assert: Id terkonfirmasi matching.
    $response->assertStatus(200)
             ->assertJsonPath('post_id', $post->post_id);
});

/**
 * Skenario: Penanganan Error Detail Tak Berjejak.
 * Prosedur: ID invalid di hit secara sembarangan oleh user di URL.
 * Ekspektasi: Gagal mengambil form yang tidak pernah ada tanpa 500 error exception.
 */
test('show post tidak ada', function () {
    // Act: Asumsi form ke 9999 diklik.
    $response = actingAs($this->adminUser)->getJson('/api/admin/posts/9999');

    // Assert: Response clean 404.
    $response->assertStatus(404);
});

/**
 * Skenario: Penggantian Subyek Artikel (Update).
 * Prosedur: Mengganti parameter parsial dengan verb PATCH.
 * Ekspektasi: Basis data terupdate lancar dan efisien.
 */
test('update post berhasil', function () {
    // Arrange: Menyusun draf dengan title "Lama".
    $post = Post::factory()->create(['author_id' => $this->adminUser->id, 'title' => 'Lama', 'status' => 'draft']);

    // Act: Membubuhkan judul baru via PATCH.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/posts/' . $post->post_id, [
        'title' => 'Baru',
    ]);

    // Assert: Rekaman berubah tuntas menjadi "Baru".
    $response->assertStatus(200);
    $this->assertDatabaseHas('posts', [
        'post_id' => $post->post_id,
        'title' => 'Baru',
    ]);
});

/**
 * Skenario: Penangkapan Pemicu (Trigger) Transisi Publikasi "Published".
 * Prosedur: Merubah state 'draft' ke 'published'.
 * Ekspektasi: Selain mengubah flag 'status', log `published_at` harus terekam secara otomatis
 *             berdasarkan waktu server detik ini (Now/Carbon).
 */
test('update post ubah status ke published set published_at', function () {
    // Arrange: State draft dengan belum adanya timing published_at.
    $post = Post::factory()->create(['author_id' => $this->adminUser->id, 'status' => 'draft']);

    // Act: Melepaskan konten ke publik (Patch).
    $response = actingAs($this->adminUser)->patchJson('/api/admin/posts/' . $post->post_id, [
        'status' => 'published',
    ]);

    // Assert: Log waktu tercantum dan bukan lagi null.
    $response->assertStatus(200);
    $updatedPost = Post::find($post->post_id);
    expect($updatedPost->status)->toBe('published');
    expect($updatedPost->published_at)->not->toBeNull();
});

/**
 * Skenario: Permusnahan Arsip Lama Oleh Redaksi.
 * Prosedur: Menghapus seluruh riwayat entitas spesifik.
 * Ekspektasi: Tidak lagi tercetak pada memori server dan menghapus cascade relasinya.
 */
test('destroy post berhasil', function () {
    // Arrange: Menyediakan contoh tumbal data hapus.
    $post = Post::factory()->create(['author_id' => $this->adminUser->id]);

    // Act: Menghajarnya lewat metode DELETE.
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/posts/' . $post->post_id);

    // Assert: Hilang di sistem (Data Missing).
    $response->assertStatus(200);
    $this->assertDatabaseMissing('posts', ['post_id' => $post->post_id]);
});

/**
 * Skenario: Pengawetan Tanggal Rilis Asli Artikel.
 * Prosedur: Artikel yang sudah tayang di waktu lama di-edit ulang di waktu terbaru tanpa merubah status.
 * Ekspektasi: `published_at` tetap merujuk pada hari konten pertama kali mengudara, 
 *             bukan me-reset menjadi rilis baru lagi. (Mencegah pengelabuan tanggal berita).
 */
test('update post published_at tidak di-replace jika sudah ada', function () {
    // Arrange: Berita lama yang sudah release 5 hari lampau.
    $originalDate = now()->subDays(5);
    $post = Post::factory()->create([
        'author_id' => $this->adminUser->id,
        'status' => 'published',
        'published_at' => $originalDate,
    ]);

    // Act: Redaksi admin menekan tombol resave/patch perbaikan ejaan tetapi state tetap published.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/posts/' . $post->post_id, [
        'status' => 'published',
    ]);

    // Assert: Waktu edar artikel harus kokoh menancap di 5 hari lalu sesuai histori awalnya.
    $response->assertStatus(200);
    $updatedPost = Post::find($post->post_id);
    expect($updatedPost->published_at->toDateTimeString())->toBe($originalDate->toDateTimeString());
});

/**
 * Skenario: Pemutusan Hubungan Post dengan Seluruh Kategori.
 * Prosedur: Mengupdate/melepas (Detach) seluruh pengelompokan yang pernah ada menjadi kosong tanpa label.
 * Ekspektasi: Pivot sync menenggelamkan catatan kategori ini, menyisakan list kategori 0 item.
 */
test('update post dengan sync categories kosong', function () {
    // Arrange: Membuat tulisan dengan minimal 1 pengelompokan subyek.
    $category = Category::factory()->create();
    $post = Post::factory()->create(['author_id' => $this->adminUser->id]);
    $post->categories()->attach($category->category_id);
    expect($post->categories()->count())->toBe(1);

    // Act: Men-submit field kategori berupa array kosong.
    $response = actingAs($this->adminUser)->patchJson('/api/admin/posts/' . $post->post_id, [
        'categories' => [],
    ]);

    // Assert: Terlepas semua cantolan kategori yang menempel (Sync Detach).
    $response->assertStatus(200);
    expect($post->fresh()->categories()->count())->toBe(0);
});
