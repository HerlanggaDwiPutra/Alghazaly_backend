<?php

use App\Models\Teacher;
use function Pest\Laravel\getJson;

test('index hanya tampilkan guru aktif', function () {
    Teacher::factory()->create(['is_active' => true, 'order' => 1]);
    Teacher::factory()->create(['is_active' => false, 'order' => 2]);

    $response = getJson('/api/teachers');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
});

test('index tidak tampilkan guru non-aktif', function () {
    Teacher::factory()->create(['is_active' => false]);

    $response = getJson('/api/teachers');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});

test('index urut by order', function () {
    Teacher::factory()->create(['is_active' => true, 'order' => 3, 'name' => 'Guru C']);
    Teacher::factory()->create(['is_active' => true, 'order' => 1, 'name' => 'Guru A']);
    Teacher::factory()->create(['is_active' => true, 'order' => 2, 'name' => 'Guru B']);

    $response = getJson('/api/teachers');

    $response->assertStatus(200);
    $data = $response->json();
    expect($data[0]['name'])->toBe('Guru A');
    expect($data[1]['name'])->toBe('Guru B');
    expect($data[2]['name'])->toBe('Guru C');
});
