<?php

use App\Models\Category;
use function Pest\Laravel\getJson;

test('index menampilkan root categories dengan children', function () {
    $parent = Category::factory()->create(['parent_id' => null]);
    Category::factory()->create(['parent_id' => $parent->category_id]);

    $response = getJson('/api/categories');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0])->toHaveKey('children');
    expect(count($data[0]['children']))->toBe(1);
});

test('index kosong jika belum ada data', function () {
    $response = getJson('/api/categories');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});
