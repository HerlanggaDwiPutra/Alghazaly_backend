<?php

use App\Models\Teacher;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/teachers');
    $response->assertStatus(401);
});

test('index menampilkan semua guru', function () {
    Teacher::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/teachers');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

test('store guru berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/teachers', [
        'name'     => 'Pak Ahmad',
        'position' => 'Guru Matematika',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('teachers', ['name' => 'Pak Ahmad']);
});

test('store guru validasi name wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/teachers', [
        'position' => 'Guru',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

test('store guru validasi position wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/teachers', [
        'name' => 'Pak Ahmad',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['position']);
});

test('show guru detail', function () {
    $teacher = Teacher::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/teachers/' . $teacher->teacher_id);

    $response->assertStatus(200)
             ->assertJsonPath('teacher_id', $teacher->teacher_id);
});

test('show guru tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/teachers/9999');

    $response->assertStatus(404);
});

test('update guru berhasil', function () {
    $teacher = Teacher::factory()->create(['name' => 'Lama']);

    $response = actingAs($this->adminUser)->putJson('/api/admin/teachers/' . $teacher->teacher_id, [
        'name' => 'Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('teachers', ['teacher_id' => $teacher->teacher_id, 'name' => 'Baru']);
});

test('destroy guru berhasil', function () {
    $teacher = Teacher::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/teachers/' . $teacher->teacher_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('teachers', ['teacher_id' => $teacher->teacher_id]);
});
