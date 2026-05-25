<?php

use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminUser = createAdminUser();
});

test('akses tanpa auth ditolak', function () {
    $response = getJson('/api/admin/albums');
    $response->assertStatus(401);
});

test('index menampilkan semua album', function () {
    Album::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/albums');

    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBe(3);
});

test('store album berhasil', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/albums', [
        'title'        => 'Album Wisuda 2024',
        'description'  => 'Koleksi foto wisuda',
        'is_published' => true,
        'order'        => 1,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('albums', [
        'title' => 'Album Wisuda 2024',
        'slug'  => 'album-wisuda-2024',
    ]);
});

test('store album validasi title wajib', function () {
    $response = actingAs($this->adminUser)->postJson('/api/admin/albums', [
        'description' => 'Deskripsi',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
});

test('show album detail', function () {
    $album = Album::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/albums/' . $album->album_id);

    $response->assertStatus(200)
             ->assertJsonPath('album_id', $album->album_id);
});

test('show album tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->getJson('/api/admin/albums/9999');

    $response->assertStatus(404);
});

test('update album berhasil', function () {
    $album = Album::factory()->create(['title' => 'Lama']);

    $response = actingAs($this->adminUser)->putJson('/api/admin/albums/' . $album->album_id, [
        'title' => 'Baru',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('albums', [
        'album_id' => $album->album_id,
        'title'    => 'Baru',
        'slug'     => 'baru',
    ]);
});

test('update album title memperbarui slug', function () {
    $album = Album::factory()->create(['title' => 'Judul Lama', 'slug' => 'judul-lama']);

    actingAs($this->adminUser)->putJson('/api/admin/albums/' . $album->album_id, [
        'title' => 'Judul Baru Update',
    ]);

    $this->assertDatabaseHas('albums', [
        'album_id' => $album->album_id,
        'slug'     => 'judul-baru-update',
    ]);
});

test('update album sync medias', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create();
    $media1 = Media::factory()->create(['uploader_id' => $user->id]);
    $media2 = Media::factory()->create(['uploader_id' => $user->id]);

    $response = actingAs($this->adminUser)->putJson('/api/admin/albums/' . $album->album_id, [
        'medias' => [$media1->media_id, $media2->media_id],
    ]);

    $response->assertStatus(200);
    expect($album->fresh()->medias()->count())->toBe(2);
});

test('destroy album berhasil', function () {
    $album = Album::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/albums/' . $album->album_id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('albums', ['album_id' => $album->album_id]);
});

test('destroy album tidak ada (404)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/albums/9999');

    $response->assertStatus(404);
});
