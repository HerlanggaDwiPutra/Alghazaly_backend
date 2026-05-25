<?php

use App\Models\FormSubmission;
use App\Models\Post;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use function Pest\Laravel\{getJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/dashboard');
    $response->assertStatus(401);
});

test('index menampilkan statistik dashboard', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'registrations' => ['total', 'pending', 'accepted', 'rejected'],
                 'payments'      => ['total', 'paid', 'pending'],
                 'posts',
                 'users',
                 'unread_messages',
                 'recent_registrations',
             ]);
});

test('index menghitung registrations by status', function () {
    Registration::factory()->count(2)->create(['status' => 'pending']);
    Registration::factory()->create(['status' => 'accepted']);
    Registration::factory()->create(['status' => 'rejected']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    $response->assertStatus(200);
    $data = $response->json('registrations');
    expect($data['total'])->toBe(4);
    expect($data['pending'])->toBe(2);
    expect($data['accepted'])->toBe(1);
    expect($data['rejected'])->toBe(1);
});

test('index menghitung payments by status', function () {
    Payment::factory()->count(2)->create(['status' => 'pending']);
    Payment::factory()->create(['status' => 'paid']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    $response->assertStatus(200);
    $data = $response->json('payments');
    expect($data['total'])->toBe(3);
    expect($data['paid'])->toBe(1);
    expect($data['pending'])->toBe(2);
});

test('index menampilkan recent registrations (max 5)', function () {
    Registration::factory()->count(7)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/dashboard');

    $response->assertStatus(200);
    $recent = $response->json('recent_registrations');
    expect(count($recent))->toBeLessThanOrEqual(5);
});
