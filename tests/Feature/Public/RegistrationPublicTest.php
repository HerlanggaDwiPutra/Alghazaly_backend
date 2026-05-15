<?php

use App\Models\Registration;
use App\Models\Media;
use App\Models\RegistrationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\{postJson, getJson};

test('registrasi ppdb berhasil', function () {
    $data = [
        'full_name'       => 'Siswa Baru',
        'birth_date'      => '2010-01-01',
        'birth_place'     => 'Jakarta',
        'gender'          => 'L',
        'address'         => 'Jl. Pendidikan No. 1',
        'phone'           => '081234567890',
        'parent_name'     => 'Orang Tua Siswa',
        'parent_phone'    => '081234567891',
        'previous_school' => 'SMP Negeri 1',
        'academic_year'   => '2024/2025',
    ];

    $response = postJson('/api/registrations', $data);

    $response->assertStatus(201)
             ->assertJsonStructure(['message', 'registration_number', 'registration_id']);

    $this->assertDatabaseHas('registrations', [
        'full_name' => 'Siswa Baru',
        'status' => 'pending'
    ]);
});

test('registrasi ppdb validasi full name wajib', function () {
    $response = postJson('/api/registrations', [
        'birth_date' => '2010-01-01',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['full_name']);
});

test('registrasi ppdb validasi gender enum', function () {
    $response = postJson('/api/registrations', [
        'full_name'       => 'Siswa Baru',
        'birth_date'      => '2010-01-01',
        'birth_place'     => 'Jakarta',
        'gender'          => 'X', // Invalid
        'address'         => 'Jl. Pendidikan No. 1',
        'phone'           => '081234567890',
        'parent_name'     => 'Orang Tua Siswa',
        'parent_phone'    => '081234567891',
        'previous_school' => 'SMP Negeri 1',
        'academic_year'   => '2024/2025',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['gender']);
});

test('registrasi ppdb validasi birth date format', function () {
    $response = postJson('/api/registrations', [
        'full_name'       => 'Siswa Baru',
        'birth_date'      => 'bukan-tanggal',
        'birth_place'     => 'Jakarta',
        'gender'          => 'L',
        'address'         => 'Jl. Pendidikan No. 1',
        'phone'           => '081234567890',
        'parent_name'     => 'Orang Tua Siswa',
        'parent_phone'    => '081234567891',
        'previous_school' => 'SMP Negeri 1',
        'academic_year'   => '2024/2025',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['birth_date']);
});

test('registrasi ppdb nomor unik digenerate', function () {
    $data = [
        'full_name'       => 'Siswa Satu',
        'birth_date'      => '2010-01-01',
        'birth_place'     => 'Jakarta',
        'gender'          => 'L',
        'address'         => 'Alamat',
        'phone'           => '123',
        'parent_name'     => 'Ortu',
        'parent_phone'    => '123',
        'previous_school' => 'SMP',
        'academic_year'   => '2024',
    ];

    $response1 = postJson('/api/registrations', $data);
    $response2 = postJson('/api/registrations', array_merge($data, ['full_name' => 'Siswa Dua']));

    $regNum1 = $response1->json('registration_number');
    $regNum2 = $response2->json('registration_number');

    expect($regNum1)->not->toBe($regNum2);
});

test('cek status registrasi valid', function () {
    $registration = Registration::factory()->create();

    $response = getJson('/api/registrations/' . $registration->registration_number . '/status');

    $response->assertStatus(200)
             ->assertJson(['registration_number' => $registration->registration_number]);
});

test('cek status registrasi tidak ada', function () {
    $response = getJson('/api/registrations/TIDAK-ADA/status');

    $response->assertStatus(404);
});

test('upload dokumen berhasil', function () {
    Storage::fake('public');
    User::factory()->create(['id' => 0]);
    $registration = Registration::factory()->create();

    $file = UploadedFile::fake()->image('kk.jpg');

    $response = postJson('/api/registrations/' . $registration->registration_id . '/documents', [
        'documents' => [
            [
                'file' => $file,
                'type' => 'kartu_keluarga'
            ]
        ]
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('medias', [
        'filename' => 'kk.jpg'
    ]);

    $this->assertDatabaseHas('registration_documents', [
        'registration_id' => $registration->registration_id,
        'document_type' => 'kartu_keluarga'
    ]);
});

test('upload dokumen validasi tipe file', function () {
    Storage::fake('public');
    User::factory()->create(['id' => 0]);
    $registration = Registration::factory()->create();

    $file = UploadedFile::fake()->create('app.exe', 100, 'application/x-msdownload');

    $response = postJson('/api/registrations/' . $registration->registration_id . '/documents', [
        'documents' => [
            [
                'file' => $file,
                'type' => 'ijazah'
            ]
        ]
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['documents.0.file']);
});

test('upload dokumen registrasi tidak ada', function () {
    Storage::fake('public');
    User::factory()->create(['id' => 0]);
    $file = UploadedFile::fake()->image('kk.jpg');

    $response = postJson('/api/registrations/9999/documents', [
        'documents' => [
            [
                'file' => $file,
                'type' => 'kartu_keluarga'
            ]
        ]
    ]);

    $response->assertStatus(404);
});
