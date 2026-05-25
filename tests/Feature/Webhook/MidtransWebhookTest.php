<?php

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\Config;
use function Pest\Laravel\postJson;

test('webhook signature valid settlement', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $registration = Registration::factory()->create(['status' => 'pending']);
    $payment = Payment::factory()->create([
        'registration_id' => $registration->registration_id,
        'order_id' => 'ORDER-123',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-123';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'settlement',
        'signature_key' => $signatureKey,
    ]);

    $response->assertStatus(200)
             ->assertJson(['message' => 'OK']);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'paid',
    ]);
});

test('webhook signature invalid', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => 'ORDER-123',
        'status_code' => '200',
        'gross_amount' => '500000.00',
        'transaction_status' => 'settlement',
        'signature_key' => 'invalid-signature',
    ]);

    $response->assertStatus(403)
             ->assertJson(['message' => 'Invalid signature.']);
});

test('webhook cancel mengubah status failed', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $payment = Payment::factory()->create([
        'order_id' => 'ORDER-CANCEL',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-CANCEL';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'cancel',
        'signature_key' => $signatureKey,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'failed',
    ]);
});

test('webhook expire mengubah status expired', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $payment = Payment::factory()->create([
        'order_id' => 'ORDER-EXPIRE',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-EXPIRE';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'expire',
        'signature_key' => $signatureKey,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'expired',
    ]);
});

test('webhook payment lunas update registrasi', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $registration = Registration::factory()->create(['status' => 'pending']);
    $payment = Payment::factory()->create([
        'registration_id' => $registration->registration_id,
        'order_id' => 'ORDER-UPDATE',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-UPDATE';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'settlement',
        'signature_key' => $signatureKey,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('registrations', [
        'registration_id' => $registration->registration_id,
        'status' => 'verified',
    ]);
});

test('webhook order id tidak ditemukan', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $orderId = 'ORDER-NOTFOUND';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'settlement',
        'signature_key' => $signatureKey,
    ]);

    $response->assertStatus(404)
             ->assertJson(['message' => 'Order tidak ditemukan.']);
});

test('webhook status pending tidak mengubah status payment', function () {
    Config::set('services.midtrans.server_key', 'test-server-key');

    $payment = Payment::factory()->create([
        'order_id' => 'ORDER-PENDING',
        'status' => 'pending',
    ]);

    $orderId = 'ORDER-PENDING';
    $statusCode = '200';
    $grossAmount = '500000.00';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . 'test-server-key');

    $response = postJson('/api/webhooks/midtrans', [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => 'pending',
        'signature_key' => $signatureKey,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('payments', [
        'payment_id' => $payment->payment_id,
        'status' => 'pending',
    ]);
});
