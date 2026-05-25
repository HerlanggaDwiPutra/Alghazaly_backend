<?php

use App\Models\Form;
use App\Models\FormSubmission;
use function Pest\Laravel\{getJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/messages');
    $response->assertStatus(401);
});

test('index menampilkan semua pesan (paginated)', function () {
    FormSubmission::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/messages');

    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

test('index filter by form_id', function () {
    $form1 = Form::factory()->create();
    $form2 = Form::factory()->create();
    FormSubmission::factory()->create(['form_id' => $form1->form_id]);
    FormSubmission::factory()->create(['form_id' => $form2->form_id]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/messages?form_id=' . $form1->form_id);

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('index filter by is_read', function () {
    FormSubmission::factory()->create(['is_read' => false]);
    FormSubmission::factory()->create(['is_read' => true]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/messages?is_read=0');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('show pesan detail dan otomatis tandai sudah dibaca', function () {
    $submission = FormSubmission::factory()->create(['is_read' => false]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/messages/' . $submission->submission_id);

    $response->assertStatus(200)
             ->assertJsonPath('submission_id', $submission->submission_id);

    $this->assertDatabaseHas('form_submissions', [
        'submission_id' => $submission->submission_id,
        'is_read'       => true,
    ]);
});

test('show pesan tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/messages/9999');

    $response->assertStatus(404);
});

test('destroy pesan berhasil', function () {
    $submission = FormSubmission::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/messages/' . $submission->submission_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('form_submissions', ['submission_id' => $submission->submission_id]);
});

test('destroy pesan tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/messages/9999');

    $response->assertStatus(404);
});
