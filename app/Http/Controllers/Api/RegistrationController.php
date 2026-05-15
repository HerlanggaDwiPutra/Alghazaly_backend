<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'full_name'       => 'required|string|max:100',
            'birth_date'      => 'required|date',
            'birth_place'     => 'required|string|max:100',
            'gender'          => 'required|in:L,P',
            'address'         => 'required|string',
            'phone'           => 'required|string|max:20',
            'parent_name'     => 'required|string|max:100',
            'parent_phone'    => 'required|string|max:20',
            'previous_school' => 'required|string|max:150',
            'academic_year'   => 'required|string|max:10',
        ]);

        $number = 'PPDB-' . date('Y') . '-' . strtoupper(Str::random(6));

        $registration = Registration::create(array_merge(
            $request->only([
                'full_name', 'birth_date', 'birth_place', 'gender',
                'address', 'phone', 'parent_name', 'parent_phone',
                'previous_school', 'academic_year',
            ]),
            ['registration_number' => $number, 'status' => 'pending']
        ));

        return response()->json([
            'message'             => 'Pendaftaran berhasil. Simpan nomor pendaftaran Anda.',
            'registration_number' => $registration->registration_number,
            'registration_id'     => $registration->registration_id,
        ], 201);
    }

    public function uploadDocuments(Request $request, int $id): JsonResponse
    {
        $registration = Registration::findOrFail($id);

        $request->validate([
            'documents'              => 'required|array|min:1',
            'documents.*.file'       => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'documents.*.type'       => 'required|string|max:50',
        ]);

        foreach ($request->input('documents') as $index => $item) {
            $file  = $request->file("documents.{$index}.file");
            $path  = $file->store('ppdb-documents', 'public');

            $media = Media::create([
                'uploader_id' => 0,
                'filename'    => $file->getClientOriginalName(),
                'path'        => $path,
                'mime_type'   => $file->getMimeType(),
                'size'        => $file->getSize(),
            ]);

            RegistrationDocument::create([
                'registration_id' => $registration->registration_id,
                'document_type'   => $item['type'],
                'media_id'        => $media->media_id,
            ]);
        }

        return response()->json(['message' => 'Dokumen berhasil diupload.']);
    }

    public function checkStatus(string $number): JsonResponse
    {
        $registration = Registration::where('registration_number', $number)
            ->with('payment:payment_id,registration_id,status,amount,paid_at')
            ->firstOrFail([
                'registration_id', 'registration_number', 'full_name',
                'academic_year', 'status', 'notes', 'created_at',
            ]);

        return response()->json($registration);
    }
}
