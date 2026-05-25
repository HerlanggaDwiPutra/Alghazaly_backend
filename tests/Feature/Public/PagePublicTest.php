<?php

use App\Models\Page;
use function Pest\Laravel\getJson;

test('index hanya tampilkan halaman published', function () {
    Page::factory()->create(['is_published' => true, 'order' => 1]);
    Page::factory()->create(['is_published' => false, 'order' => 2]);

    $response = getJson('/api/pages');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
});

test('index tidak tampilkan halaman draft', function () {
    Page::factory()->create(['is_published' => false, 'title' => 'Draft Page']);

    $response = getJson('/api/pages');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});

test('show halaman by slug', function () {
    $page = Page::factory()->create(['is_published' => true, 'slug' => 'tentang-kami']);

    $response = getJson('/api/pages/tentang-kami');

    $response->assertStatus(200)
             ->assertJsonPath('slug', 'tentang-kami');
});

test('show halaman slug tidak ada (404)', function () {
    $response = getJson('/api/pages/slug-tidak-ada');

    $response->assertStatus(404);
});

test('show halaman draft tidak bisa diakses (404)', function () {
    Page::factory()->create(['is_published' => false, 'slug' => 'halaman-draft']);

    $response = getJson('/api/pages/halaman-draft');

    $response->assertStatus(404);
});
