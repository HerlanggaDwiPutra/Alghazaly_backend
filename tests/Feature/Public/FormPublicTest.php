<?php

use App\Models\Form;
use App\Models\FormSubmission;
use function Pest\Laravel\{getJson, postJson};

test('show form aktif by slug', function () {
    Form::factory()->create(['slug' => 'kontak-kami', 'is_active' => true]);

    $response = getJson('/api/forms/kontak-kami');

    $response->assertStatus(200)
             ->assertJsonPath('slug', 'kontak-kami');
});

test('show form tidak aktif (404)', function () {
    Form::factory()->create(['slug' => 'form-nonaktif', 'is_active' => false]);

    $response = getJson('/api/forms/form-nonaktif');

    $response->assertStatus(404);
});

test('show form slug tidak ada (404)', function () {
    $response = getJson('/api/forms/slug-tidak-ada');

    $response->assertStatus(404);
});

test('submit form berhasil', function () {
    $form = Form::factory()->create(['slug' => 'hubungi-kami', 'is_active' => true]);

    $response = postJson('/api/forms/hubungi-kami/submit', [
        'data' => ['nama' => 'Test', 'email' => 'test@mail.com', 'pesan' => 'Halo'],
    ]);

    $response->assertStatus(201)
             ->assertJson(['message' => 'Pesan berhasil dikirim.']);

    $this->assertDatabaseHas('form_submissions', [
        'form_id' => $form->form_id,
    ]);
});

test('submit form validasi data wajib', function () {
    Form::factory()->create(['slug' => 'form-validasi', 'is_active' => true]);

    $response = postJson('/api/forms/form-validasi/submit', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['data']);
});

test('submit form slug tidak aktif (404)', function () {
    Form::factory()->create(['slug' => 'form-mati', 'is_active' => false]);

    $response = postJson('/api/forms/form-mati/submit', [
        'data' => ['nama' => 'Test'],
    ]);

    $response->assertStatus(404);
});

test('submit form dengan email submitter', function () {
    $form = Form::factory()->create(['slug' => 'form-email', 'is_active' => true]);

    $response = postJson('/api/forms/form-email/submit', [
        'data'            => ['nama' => 'Test'],
        'submitter_email' => 'submitter@mail.com',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('form_submissions', [
        'form_id'         => $form->form_id,
        'submitter_email' => 'submitter@mail.com',
    ]);
});
