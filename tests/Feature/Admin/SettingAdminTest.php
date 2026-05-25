<?php

use App\Models\Setting;
use function Pest\Laravel\{getJson, putJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/settings');
    $response->assertStatus(401);
});

test('index menampilkan settings grouped', function () {
    Setting::factory()->create(['key' => 'site_name', 'group' => 'general']);
    Setting::factory()->create(['key' => 'phone', 'group' => 'contact']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/settings');

    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveKey('general');
    expect($data)->toHaveKey('contact');
});

test('update settings berhasil (batch)', function () {
    $response = actingAs($this->adminUser)->putJson('/api/admin/settings', [
        'settings' => [
            ['key' => 'site_name', 'value' => 'SMA Al Ghazaly'],
            ['key' => 'phone', 'value' => '08123456789'],
        ],
    ]);

    $response->assertStatus(200)
             ->assertJson(['message' => 'Pengaturan disimpan.']);

    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'SMA Al Ghazaly']);
    $this->assertDatabaseHas('settings', ['key' => 'phone', 'value' => '08123456789']);
});

test('update settings validasi required array', function () {
    $response = actingAs($this->adminUser)->putJson('/api/admin/settings', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['settings']);
});

test('update settings validasi key required', function () {
    $response = actingAs($this->adminUser)->putJson('/api/admin/settings', [
        'settings' => [
            ['value' => 'test'],
        ],
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['settings.0.key']);
});

test('update settings update existing key', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Lama']);

    actingAs($this->adminUser)->putJson('/api/admin/settings', [
        'settings' => [
            ['key' => 'site_name', 'value' => 'Baru'],
        ],
    ]);

    $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Baru']);
    // Should not create duplicate
    expect(Setting::where('key', 'site_name')->count())->toBe(1);
});
