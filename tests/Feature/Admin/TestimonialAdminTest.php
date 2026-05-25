<?php

use App\Models\Testimonial;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/testimonials');
    $response->assertStatus(401);
});

test('index menampilkan semua testimonial', function () {
    Testimonial::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/testimonials');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

test('store testimonial berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'name'    => 'Budi Santoso',
        'content' => 'Sekolah ini sangat baik.',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('testimonials', ['name' => 'Budi Santoso']);
});

test('store testimonial validasi name wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'content' => 'Isi testimonial',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

test('store testimonial validasi content wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'name' => 'Test',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['content']);
});

test('store testimonial validasi rating min 1 max 5', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/testimonials', [
        'name'    => 'Test',
        'content' => 'Isi',
        'rating'  => 0,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['rating']);
});

test('update testimonial berhasil', function () {
    $testimonial = Testimonial::factory()->create(['name' => 'Lama']);

    $response = actingAs($this->adminUser)->putJson('/api/admin/testimonials/' . $testimonial->testimonial_id, [
        'name' => 'Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('testimonials', ['testimonial_id' => $testimonial->testimonial_id, 'name' => 'Baru']);
});

test('destroy testimonial berhasil', function () {
    $testimonial = Testimonial::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/testimonials/' . $testimonial->testimonial_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('testimonials', ['testimonial_id' => $testimonial->testimonial_id]);
});

test('destroy testimonial tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/testimonials/9999');

    $response->assertStatus(404);
});
