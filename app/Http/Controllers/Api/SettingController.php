<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menyediakan akses read-only ke konfigurasi global aplikasi untuk konsumsi publik.
 *
 * Digunakan oleh frontend untuk membaca pengaturan seperti nama sekolah,
 * alamat, kontak, dan link media sosial tanpa autentikasi. Operasi update
 * pengaturan hanya tersedia di panel admin melalui
 * {@see \App\Http\Controllers\Api\Admin\SettingController}.
 *
 * @see \App\Models\Setting
 */
class SettingController extends Controller
{
    /**
     * Mengembalikan konfigurasi aplikasi sebagai objek key-value datar.
     *
     * Mendukung parameter `group` pada query string untuk menyaring pengaturan
     * berdasarkan kelompok tertentu (contoh: `?group=contact`).
     *
     * Transformasi `keyBy('key')->map(fn => $s->value)` mengubah Eloquent collection
     * dari format array-of-objects menjadi objek JSON datar `{key: value, ...}`
     * yang lebih mudah dikonsumsi langsung oleh frontend tanpa pemrosesan tambahan.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (group: string opsional).
     * @return \Illuminate\Http\JsonResponse         Objek JSON flat `{setting_key: "setting_value", ...}`.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = Setting::when($request->group, fn ($q) => $q->where('group', $request->group))
            ->get(['key', 'value', 'group'])
            ->keyBy('key')
            ->map(fn ($s) => $s->value);

        return response()->json($settings);
    }
}
