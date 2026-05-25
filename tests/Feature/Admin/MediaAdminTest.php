<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{getJson, postJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/medias');
    $response->assertStatus(401);
});

test('index menampilkan semua media (paginated)', function () {
    Media::factory()->count(3)->create(['uploader_id' => $this->adminUser->id]);

    $response = actingAs($this->adminUser)->getJson('/api/admin/medias');

    $response->assertStatus(200)
             ->assertJsonStructure(['current_page', 'data', 'per_page', 'total']);
});

test('index filter by type', function () {
    Media::factory()->create(['uploader_id' => $this->adminUser->id, 'mime_type' => 'image/jpeg']);
    Media::factory()->create(['uploader_id' => $this->adminUser->id, 'mime_type' => 'application/pdf']);

    $response = actingAs($this->adminUser)->getJson('/api/admin/medias?type=image');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('store upload file berhasil', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test-image.jpg', 640, 480);

    $response = actingAs($this->adminUser)->postJson('/api/admin/medias', [
        'file' => $file,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('medias', [
        'filename'    => 'test-image.jpg',
        'uploader_id' => $this->adminUser->id,
    ]);
});

test('store validasi file wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/medias', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['file']);
});

test('destroy media berhasil', function () {
    Storage::fake('public');
    $media = Media::factory()->create([
        'uploader_id' => $this->adminUser->id,
        'path'        => 'uploads/test.jpg',
    ]);
    Storage::disk('public')->put('uploads/test.jpg', 'fake content');

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/medias/' . $media->media_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('medias', ['media_id' => $media->media_id]);
    Storage::disk('public')->assertMissing('uploads/test.jpg');
});

test('destroy media tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/medias/9999');

    $response->assertStatus(404);
});
