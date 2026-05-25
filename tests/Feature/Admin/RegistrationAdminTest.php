<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Registration;
use function Pest\Laravel\{getJson, patchJson, actingAs};

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->role_id, 'is_active' => true]);
});

test('index menampilkan semua pendaftar', function () {
    Registration::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations');

    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'first_page_url', 'last_page', 'links', 'per_page', 'total']);
});

test('show detail pendaftar', function () {
    $registration = Registration::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations/' . $registration->registration_id);

    $response->assertStatus(200)
             ->assertJsonPath('registration_id', $registration->registration_id);
});

test('update status diterima', function () {
    $registration = Registration::factory()->create(['status' => 'pending']);

    $response = actingAs($this->adminUser)->patchJson('/api/admin/registrations/' . $registration->registration_id . '/status', [
        'status' => 'accepted',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('registrations', [
        'registration_id' => $registration->registration_id,
        'status' => 'accepted',
    ]);
});

test('update status ditolak dengan notes', function () {
    $registration = Registration::factory()->create(['status' => 'pending']);

    $response = actingAs($this->adminUser)->patchJson('/api/admin/registrations/' . $registration->registration_id . '/status', [
        'status' => 'rejected',
        'notes' => 'Umur tidak mencukupi',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('registrations', [
        'registration_id' => $registration->registration_id,
        'status' => 'rejected',
        'notes' => 'Umur tidak mencukupi',
    ]);
});

test('update status validasi enum', function () {
    $registration = Registration::factory()->create(['status' => 'pending']);

    $response = actingAs($this->adminUser)->patchJson('/api/admin/registrations/' . $registration->registration_id . '/status', [
        'status' => 'unknown',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
});

test('index filter by academic_year', function () {
    Registration::factory()->create(['academic_year' => '2024/2025']);
    Registration::factory()->create(['academic_year' => '2025/2026']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations?academic_year=2024/2025');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['academic_year'])->toBe('2024/2025');
});

test('index filter by search nama', function () {
    Registration::factory()->create(['full_name' => 'Ahmad Rizky']);
    Registration::factory()->create(['full_name' => 'Budi Santoso']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations?search=Ahmad');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['full_name'])->toBe('Ahmad Rizky');
});

test('index filter by search nomor registrasi', function () {
    $reg = Registration::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/registrations?search=' . substr($reg->registration_number, 0, 8));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});
