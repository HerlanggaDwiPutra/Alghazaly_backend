<?php

use App\Models\Alumni;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/alumni');
    $response->assertStatus(401);
});

test('index menampilkan semua alumni (paginated)', function () {
    Alumni::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/alumni');

    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

test('index filter by graduation year', function () {
    Alumni::factory()->create(['graduation_year' => 2023]);
    Alumni::factory()->create(['graduation_year' => 2024]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/alumni?year=2023');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['graduation_year'])->toBe(2023);
});

test('store alumni berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/alumni', [
        'name'            => 'Ahmad Fauzi',
        'graduation_year' => 2023,
        'is_published'    => true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('alumni', ['name' => 'Ahmad Fauzi']);
});

test('store alumni validasi name wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/alumni', [
        'graduation_year' => 2023,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

test('store alumni validasi graduation_year 4 digit', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/alumni', [
        'name'            => 'Test',
        'graduation_year' => 99,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['graduation_year']);
});

test('update alumni berhasil', function () {
    $alumni = Alumni::factory()->create(['name' => 'Nama Lama']);

    $response = actingAs($this->adminUser)->putJson('/api/admin/alumni/' . $alumni->alumni_id, [
        'name' => 'Nama Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('alumni', ['alumni_id' => $alumni->alumni_id, 'name' => 'Nama Baru']);
});

test('destroy alumni berhasil', function () {
    $alumni = Alumni::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/alumni/' . $alumni->alumni_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('alumni', ['alumni_id' => $alumni->alumni_id]);
});

test('destroy alumni tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/alumni/9999');

    $response->assertStatus(404);
});
