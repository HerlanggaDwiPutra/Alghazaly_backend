<?php

use App\Models\User;
use function Pest\Laravel\{postJson, getJson};

test('login dengan kredensial valid', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['token', 'user']);
});

test('login gagal password salah', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Kredensial tidak valid.']);
});

test('login gagal user tidak aktif', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'is_active' => false,
    ]);

    $response = postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Kredensial tidak valid.']);
});

test('login validasi email wajib', function () {
    $response = postJson('/api/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('login validasi email format', function () {
    $response = postJson('/api/auth/login', [
        'email' => 'bukan-email',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('login validasi password wajib', function () {
    $response = postJson('/api/auth/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['password']);
});

test('logout berhasil', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = postJson('/api/auth/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
             ->assertJson(['message' => 'Berhasil logout.']);
});

test('logout tanpa token', function () {
    $response = postJson('/api/auth/logout');

    $response->assertStatus(401);
});

test('me mengembalikan data user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = getJson('/api/auth/me', [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
             ->assertJson(['id' => $user->id, 'email' => $user->email]);
});

test('me tanpa token', function () {
    $response = getJson('/api/auth/me');

    $response->assertStatus(401);
});
