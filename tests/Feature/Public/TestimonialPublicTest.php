<?php

use App\Models\Testimonial;
use function Pest\Laravel\getJson;

test('index hanya tampilkan testimonial published', function () {
    Testimonial::factory()->create(['is_published' => true]);
    Testimonial::factory()->create(['is_published' => false]);

    $response = getJson('/api/testimonials');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(1);
});

test('index tidak tampilkan testimonial unpublished', function () {
    Testimonial::factory()->create(['is_published' => false]);

    $response = getJson('/api/testimonials');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(0);
});

test('index urut by order', function () {
    Testimonial::factory()->create(['is_published' => true, 'order' => 3, 'name' => 'Testi C']);
    Testimonial::factory()->create(['is_published' => true, 'order' => 1, 'name' => 'Testi A']);
    Testimonial::factory()->create(['is_published' => true, 'order' => 2, 'name' => 'Testi B']);

    $response = getJson('/api/testimonials');

    $response->assertStatus(200);
    $data = $response->json();
    expect($data[0]['name'])->toBe('Testi A');
    expect($data[1]['name'])->toBe('Testi B');
    expect($data[2]['name'])->toBe('Testi C');
});
