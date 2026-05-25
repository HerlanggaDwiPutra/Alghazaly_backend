<?php

use App\Models\Setting;
use function Pest\Laravel\getJson;

test('index menampilkan semua setting sebagai key-value', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Al Ghazaly', 'group' => 'general']);
    Setting::factory()->create(['key' => 'phone', 'value' => '08123456', 'group' => 'contact']);

    $response = getJson('/api/settings');

    $response->assertStatus(200)
             ->assertJsonFragment(['site_name' => 'Al Ghazaly'])
             ->assertJsonFragment(['phone' => '08123456']);
});

test('index filter by group', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Al Ghazaly', 'group' => 'general']);
    Setting::factory()->create(['key' => 'phone', 'value' => '08123456', 'group' => 'contact']);

    $response = getJson('/api/settings?group=general');

    $response->assertStatus(200)
             ->assertJsonFragment(['site_name' => 'Al Ghazaly']);
    // Should NOT contain contact group
    $response->assertJsonMissing(['phone' => '08123456']);
});

test('index kosong jika belum ada data', function () {
    $response = getJson('/api/settings');

    $response->assertStatus(200);
});
