<?php

use App\Models\Album;
use App\Models\Media;
use App\Models\User;
use function Pest\Laravel\getJson;

test('index hanya tampilkan album published', function () {
    Album::factory()->create(['is_published' => true]);
    Album::factory()->create(['is_published' => false]);

    $response = getJson('/api/albums');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
});

test('index tidak tampilkan album unpublished', function () {
    Album::factory()->create(['is_published' => false]);

    $response = getJson('/api/albums');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(0);
});

test('show album by slug dengan medias', function () {
    $user = User::factory()->create();
    $album = Album::factory()->create(['is_published' => true, 'slug' => 'galeri-wisuda']);
    $media = Media::factory()->create(['uploader_id' => $user->id]);
    $album->medias()->attach($media->media_id, ['order' => 0]);

    $response = getJson('/api/albums/galeri-wisuda');

    $response->assertStatus(200)
             ->assertJsonPath('slug', 'galeri-wisuda');
    expect($response->json('medias'))->toHaveCount(1);
});

test('show album slug tidak ada (404)', function () {
    $response = getJson('/api/albums/slug-tidak-ada');

    $response->assertStatus(404);
});

test('show album unpublished tidak bisa diakses (404)', function () {
    Album::factory()->create(['is_published' => false, 'slug' => 'album-draft']);

    $response = getJson('/api/albums/album-draft');

    $response->assertStatus(404);
});
