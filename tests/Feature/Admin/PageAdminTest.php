<?php

use App\Models\Page;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/pages');
    $response->assertStatus(401);
});

test('index menampilkan semua halaman', function () {
    Page::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/pages');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

test('store halaman berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/pages', [
        'title'   => 'Tentang Kami',
        'content' => 'Isi halaman tentang kami.',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('pages', [
        'title' => 'Tentang Kami',
        'slug'  => 'tentang-kami',
    ]);
});

test('store halaman validasi title wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/pages', [
        'content' => 'Isi konten',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
});

test('store halaman validasi content wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/pages', [
        'title' => 'Test Page',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['content']);
});

test('show halaman detail', function () {
    $page = Page::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/pages/' . $page->page_id);

    $response->assertStatus(200)
             ->assertJsonPath('page_id', $page->page_id);
});

test('show halaman tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/pages/9999');

    $response->assertStatus(404);
});

test('update halaman berhasil', function () {
    $page = Page::factory()->create(['title' => 'Lama']);

    $response = actingAs($this->adminUser)->putJson('/api/admin/pages/' . $page->page_id, [
        'title' => 'Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('pages', ['page_id' => $page->page_id, 'title' => 'Baru', 'slug' => 'baru']);
});

test('update halaman title memperbarui slug', function () {
    $page = Page::factory()->create(['title' => 'Old Title', 'slug' => 'old-title']);

    actingAs($this->adminUser)->putJson('/api/admin/pages/' . $page->page_id, [
        'title' => 'New Title Update',
    ]);

    $this->assertDatabaseHas('pages', ['page_id' => $page->page_id, 'slug' => 'new-title-update']);
});

test('destroy halaman berhasil', function () {
    $page = Page::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/pages/' . $page->page_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('pages', ['page_id' => $page->page_id]);
});
