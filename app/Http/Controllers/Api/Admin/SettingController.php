<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mengelola pembacaan dan pembaruan konfigurasi global aplikasi di panel Admin.
 *
 * Berbeda dengan {@see \App\Http\Controllers\Api\SettingController} (publik),
 * controller ini memungkinkan admin membaca pengaturan dalam format yang
 * terkelompok per `group` dan memperbarui banyak pengaturan sekaligus
 * menggunakan satu endpoint batch-update.
 *
 * @see \App\Models\Setting
 */
class SettingController extends Controller
{
    /**
     * Mengembalikan seluruh konfigurasi aplikasi yang dikelompokkan berdasarkan `group`.
     *
     * Koleksi dikelompokkan menggunakan `groupBy('group')` sehingga response JSON
     * terstruktur sebagai objek berlapis per kategori pengaturan, contoh:
     * `{"general": [{...}], "contact": [{...}], "social_media": [{...}]}`.
     * Format ini memudahkan frontend untuk merender form pengaturan per-tab.
     *
     * @return \Illuminate\Http\JsonResponse  Seluruh pengaturan yang dikelompokkan berdasarkan field `group`.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->groupBy('group');
        return response()->json($settings);
    }

    /**
     * Memperbarui atau membuat banyak entri pengaturan sekaligus (batch upsert).
     *
     * Menggunakan `updateOrCreate` dengan `key` sebagai identifier unik sehingga:
     * - Jika `key` sudah ada: nilai diperbarui.
     * - Jika `key` belum ada: entri baru dibuat.
     *
     * Ini memungkinkan admin mengirimkan seluruh halaman pengaturan sekaligus
     * tanpa perlu membedakan antara insert dan update.
     *
     * @param  \Illuminate\Http\Request  $request  Array `settings` berisi objek `{key, value}`.
     * @return \Illuminate\Http\JsonResponse         Konfirmasi penyimpanan berhasil.
     *
     * @throws \Illuminate\Validation\ValidationException  Jika format array settings tidak valid.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings'         => 'required|array',
            'settings.*.key'   => 'required|string|max:100',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($request->settings as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'] ?? null]
            );
        }

        return response()->json(['message' => 'Pengaturan disimpan.']);
    }
}
