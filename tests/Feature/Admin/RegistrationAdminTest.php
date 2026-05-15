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
