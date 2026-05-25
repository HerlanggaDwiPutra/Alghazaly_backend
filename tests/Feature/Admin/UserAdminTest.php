<?php

use App\Models\User;
use App\Models\Role;
use function Pest\Laravel\{getJson, postJson, patchJson, deleteJson, actingAs};

beforeEach(function () {
    $this->adminRole = Role::factory()->create(['name' => 'admin']);
    $this->adminUser = User::factory()->create(['role_id' => $this->adminRole->role_id, 'is_active' => true]);
});

test('index menampilkan semua user', function () {
    User::factory()->count(3)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/users');

    $response->assertStatus(200);
});

test('store user berhasil', function () {
    $role = Role::factory()->create(['name' => 'editor']);

    $response = actingAs($this->adminUser)->postJson('/api/admin/users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'role_id' => $role->role_id,
        'is_active' => true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('store user email duplikat', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = actingAs($this->adminUser)->postJson('/api/admin/users', [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'role_id' => $this->adminRole->role_id,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('store user password ter-hash', function () {
    $role = Role::factory()->create(['name' => 'staff']);

    actingAs($this->adminUser)->postJson('/api/admin/users', [
        'name' => 'Hash User',
        'email' => 'hash@example.com',
        'password' => 'password123',
        'role_id' => $role->role_id,
    ]);

    $user = User::where('email', 'hash@example.com')->first();
    expect($user->password)->not->toBe('password123');
});

test('update user berhasil', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $response = actingAs($this->adminUser)->patchJson('/api/admin/users/' . $user->id, [
        'name' => 'New Name',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});

test('destroy user berhasil', function () {
    $user = User::factory()->create();

    $response = actingAs($this->adminUser)->deleteJson('/api/admin/users/' . $user->id);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('get roles', function () {
    Role::factory()->count(2)->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/roles');

    $response->assertStatus(200);
});

test('show user detail', function () {
    $user = User::factory()->create();

    $response = actingAs($this->adminUser)->getJson('/api/admin/users/' . $user->id);

    $response->assertStatus(200)
             ->assertJsonPath('id', $user->id);
});

test('update user dengan password baru', function () {
    $user = User::factory()->create();

    $response = actingAs($this->adminUser)->patchJson('/api/admin/users/' . $user->id, [
        'password' => 'newpassword123',
    ]);

    $response->assertStatus(200);
    $updatedUser = User::find($user->id);
    expect(\Illuminate\Support\Facades\Hash::check('newpassword123', $updatedUser->password))->toBeTrue();
});

test('destroy user sendiri ditolak (403)', function () {
    $response = actingAs($this->adminUser)->deleteJson('/api/admin/users/' . $this->adminUser->id);

    $response->assertStatus(403)
             ->assertJson(['message' => 'Tidak dapat menghapus akun sendiri.']);
});
