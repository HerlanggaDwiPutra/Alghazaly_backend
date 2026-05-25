<?php

use App\Models\Alumni;
use function Pest\Laravel\getJson;

test('index hanya tampilkan alumni published', function () {
    Alumni::factory()->create(['is_published' => true]);
    Alumni::factory()->create(['is_published' => false]);

    $response = getJson('/api/alumni');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('index filter by year', function () {
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2023]);
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2024]);

    $response = getJson('/api/alumni?year=2023');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['graduation_year'])->toBe(2023);
});

test('index filter by search name', function () {
    Alumni::factory()->create(['is_published' => true, 'name' => 'Ahmad Fauzi']);
    Alumni::factory()->create(['is_published' => true, 'name' => 'Budi Santoso']);

    $response = getJson('/api/alumni?search=Ahmad');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['name'])->toBe('Ahmad Fauzi');
});

test('index urut by graduation_year desc', function () {
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2020]);
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2024]);
    Alumni::factory()->create(['is_published' => true, 'graduation_year' => 2022]);

    $response = getJson('/api/alumni');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data[0]['graduation_year'])->toBe(2024);
    expect($data[1]['graduation_year'])->toBe(2022);
    expect($data[2]['graduation_year'])->toBe(2020);
});
