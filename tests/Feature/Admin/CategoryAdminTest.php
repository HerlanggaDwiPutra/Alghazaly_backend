<?php

use App\Models\Category;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/categories');
    $response->assertStatus(401);
});

test('index menampilkan root categories dengan children', function () {
    $parent = Category::factory()->create(['parent_id' => null]);
    Category::factory()->create(['parent_id' => $parent->category_id]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/categories');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0])->toHaveKey('children');
});

test('store kategori berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', [
        'category_name' => 'Teknologi',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('categories', [
        'category_name' => 'Teknologi',
        'slug'          => 'teknologi',
    ]);
});

test('store kategori validasi name wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['category_name']);
});

test('store kategori dengan parent_id', function () {
    $parent = Category::factory()->create();

    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', [
        'category_name' => 'Sub Kategori',
        'parent_id'     => $parent->category_id,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('categories', [
        'category_name' => 'Sub Kategori',
        'parent_id'     => $parent->category_id,
    ]);
});

test('store kategori parent_id invalid (422)', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/categories', [
        'category_name' => 'Test',
        'parent_id'     => 9999,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['parent_id']);
});

test('update kategori berhasil', function () {
    $category = Category::factory()->create(['category_name' => 'Lama']);

    $response = actingAs($this->adminUser)->putJson('/api/admin/categories/' . $category->category_id, [
        'category_name' => 'Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('categories', [
        'category_id'   => $category->category_id,
        'category_name' => 'Baru',
        'slug'          => 'baru',
    ]);
});

test('update kategori name memperbarui slug', function () {
    $category = Category::factory()->create(['category_name' => 'Old Name', 'slug' => 'old-name']);

    actingAs($this->adminUser)->putJson('/api/admin/categories/' . $category->category_id, [
        'category_name' => 'New Updated Name',
    ]);

    $this->assertDatabaseHas('categories', [
        'category_id' => $category->category_id,
        'slug'        => 'new-updated-name',
    ]);
});

test('destroy kategori berhasil', function () {
    $category = Category::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/categories/' . $category->category_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('categories', ['category_id' => $category->category_id]);
});

test('destroy kategori tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/categories/9999');

    $response->assertStatus(404);
});
