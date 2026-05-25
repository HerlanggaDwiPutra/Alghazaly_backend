<?php

use App\Models\Payment;
use App\Models\Registration;
use function Pest\Laravel\{getJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/payments');
    $response->assertStatus(401);
});

test('index menampilkan semua payment (paginated)', function () {
    Payment::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/payments');

    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

test('index filter by status', function () {
    Payment::factory()->create(['status' => 'paid']);
    Payment::factory()->create(['status' => 'pending']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/payments?status=paid');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('paid');
});

test('index filter by search order_id', function () {
    Payment::factory()->create(['order_id' => 'ORDER-SEARCH-TEST']);
    Payment::factory()->create(['order_id' => 'ORDER-OTHER-123']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/payments?search=SEARCH-TEST');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('show payment detail', function () {
    $payment = Payment::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/payments/' . $payment->payment_id);

    $response->assertStatus(200)
             ->assertJsonPath('payment_id', $payment->payment_id);
});

test('show payment tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/payments/9999');

    $response->assertStatus(404);
});
