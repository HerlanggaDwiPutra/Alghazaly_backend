<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Post;
use App\Models\Category;
use function Pest\Laravel\{getJson, postJson, patchJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->role_id, 'is_active' => true]);
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/posts');
    $response->assertStatus(401);
});

test('index menampilkan semua post', function () {
    Post::factory()->count(3)->create(['author_id' => $this->adminUser->id]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/posts');

    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'first_page_url', 'last_page', 'links', 'per_page', 'total']);
});

test('index filter by status', function () {
    Post::factory()->create(['author_id' => $this->adminUser->id, 'status' => 'draft']);
    Post::factory()->create(['author_id' => $this->adminUser->id, 'status' => 'published']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/posts?status=draft');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('draft');
});

test('index filter by search', function () {
    Post::factory()->create(['author_id' => $this->adminUser->id, 'title' => 'Belajar Pest']);
    Post::factory()->create(['author_id' => $this->adminUser->id, 'title' => 'Tutorial Laravel']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/posts?search=Pest');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['title'])->toBe('Belajar Pest');
});

test('store post berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Judul Baru',
        'content' => 'Isi konten post baru.',
        'status' => 'draft',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', [
        'title' => 'Judul Baru',
        'slug' => 'judul-baru',
        'status' => 'draft',
        'author_id' => $this->adminUser->id,
    ]);
});

test('store post slug digenerate otomatis', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Judul Test',
        'content' => 'Isi konten',
        'status' => 'draft',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', ['slug' => 'judul-test']);
});

test('store post validasi title wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'content' => 'Isi',
        'status' => 'draft',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
});

test('store post validasi status enum', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Test',
        'content' => 'Isi',
        'status' => 'unknown',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
});

test('store post dengan kategori', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();

    $response = actingAs($this->adminUser)->postJson('/api/admin/posts', [
        'title' => 'Post Kategori',
        'content' => 'Isi konten',
        'status' => 'draft',
        'categories' => [$category1->category_id, $category2->category_id],
    ]);

    $response->assertStatus(201);
    
    $post = Post::where('title', 'Post Kategori')->first();
    expect($post->categories()->count())->toBe(2);
});

test('show post admin', function () {
    $post = Post::factory()->create(['author_id' => $this->adminUser->id]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/posts/' . $post->post_id);

    $response->assertStatus(200)
             ->assertJsonPath('post_id', $post->post_id);
});

test('show post tidak ada', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/posts/9999');

    $response->assertStatus(404);
});

test('update post berhasil', function () {
    $post = Post::factory()->create(['author_id' => $this->adminUser->id, 'title' => 'Lama', 'status' => 'draft']);

    $response = actingAs($this->adminUser)->patchJson('/api/admin/posts/' . $post->post_id, [
        'title' => 'Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('posts', [
        'post_id' => $post->post_id,
        'title' => 'Baru',
    ]);
});

test('update post ubah status ke published set published_at', function () {
    $post = Post::factory()->create(['author_id' => $this->adminUser->id, 'status' => 'draft']);

    $response = actingAs($this->adminUser)->patchJson('/api/admin/posts/' . $post->post_id, [
        'status' => 'published',
    ]);

    $response->assertStatus(200);
    $updatedPost = Post::find($post->post_id);
    expect($updatedPost->status)->toBe('published');
    expect($updatedPost->published_at)->not->toBeNull();
});

test('destroy post berhasil', function () {
    $post = Post::factory()->create(['author_id' => $this->adminUser->id]);

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/posts/' . $post->post_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('posts', ['post_id' => $post->post_id]);
});
