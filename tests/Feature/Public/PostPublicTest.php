<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use function Pest\Laravel\getJson;

test('index hanya tampilkan post published', function () {
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
    Post::factory()->create(['author_id' => $user->id, 'status' => 'draft']);

    $response = getJson('/api/posts');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('published');
});

test('index filter by category slug', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['slug' => 'teknologi']);
    $post = Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
    $post->categories()->attach($category->category_id);

    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

    $response = getJson('/api/posts?category=teknologi');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('index filter by search', function () {
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'title' => 'Belajar Laravel', 'status' => 'published', 'published_at' => now()]);
    Post::factory()->create(['author_id' => $user->id, 'title' => 'Tutorial React', 'status' => 'published', 'published_at' => now()]);

    $response = getJson('/api/posts?search=Laravel');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['title'])->toBe('Belajar Laravel');
});

test('index urut by published_at desc', function () {
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDays(2)]);
    Post::factory()->create(['author_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

    $response = getJson('/api/posts');

    $response->assertStatus(200);
    $data = $response->json('data');
    // First should be the newest
    expect($data[0]['published_at'] >= $data[1]['published_at'])->toBeTrue();
});

test('show post by slug', function () {
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'slug' => 'post-pertama', 'status' => 'published', 'published_at' => now()]);

    $response = getJson('/api/posts/post-pertama');

    $response->assertStatus(200)
             ->assertJsonPath('slug', 'post-pertama');
});

test('show post slug tidak ada (404)', function () {
    $response = getJson('/api/posts/slug-tidak-ada');

    $response->assertStatus(404);
});

test('show post draft tidak bisa diakses (404)', function () {
    $user = User::factory()->create();
    Post::factory()->create(['author_id' => $user->id, 'slug' => 'post-draft', 'status' => 'draft']);

    $response = getJson('/api/posts/post-draft');

    $response->assertStatus(404);
});
