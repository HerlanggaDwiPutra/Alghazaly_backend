<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Mengelola operasi CRUD artikel/berita sekolah Al Ghazaly di panel Admin.
 *
 * Model Post mendukung sistem kategori many-to-many melalui tabel pivot
 * `post_categories`, dan memiliki field SEO bawaan (meta_title, meta_description,
 * meta_keywords) untuk optimasi mesin pencari.
 *
 * Slug dihasilkan secara otomatis dari judul artikel dan digunakan sebagai
 * URL-friendly identifier di endpoint publik.
 *
 * @see \App\Models\Post
 * @see \App\Http\Controllers\Api\PostController  Versi read-only untuk konsumsi publik.
 */
class PostController extends Controller
{
    /**
     * Menampilkan daftar artikel dengan filter dan paginasi untuk panel admin.
     *
     * @param  \Illuminate\Http\Request  $request  Query string filter (status, search).
     * @return \Illuminate\Http\JsonResponse         Daftar artikel terpaginasi (15 per halaman).
     */
    public function index(Request $request): JsonResponse
    {
        $posts = Post::with('author:id,name', 'categories:category_id,category_name')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($posts);
    }

    /**
     * Membuat artikel baru dan menghubungkannya ke kategori pilihan.
     *
     * Slug di-generate dari judul menggunakan `Str::slug` (URL-safe, huruf kecil,
     * spasi menjadi tanda hubung). `published_at` hanya diisi jika status langsung
     * `published`; jika masih `draft`, nilai ini tetap null hingga dipublikasikan.
     *
     * Relasi kategori disinkronkan (bukan ditambahkan) menggunakan `sync()`,
     * sehingga kategori lama yang tidak disertakan dalam request akan dihapus.
     *
     * @param  \Illuminate\Http\Request  $request  Data artikel beserta array ID kategori (opsional).
     * @return \Illuminate\Http\JsonResponse         Artikel yang baru dibuat beserta kategorinya (HTTP 201).
     *
     * @throws \Illuminate\Validation\ValidationException  Jika validasi input gagal.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'required|string',
            'excerpt'          => 'nullable|string',
            'thumbnail'        => 'nullable|string|max:255',
            'status'           => 'required|in:draft,published,archived',
            'categories'       => 'nullable|array',
            'categories.*'     => 'exists:categories,category_id',
            'meta_title'       => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        $post = Post::create([
            ...$request->only([
                'title', 'content', 'excerpt', 'thumbnail', 'status',
                'meta_title', 'meta_description', 'meta_keywords',
            ]),
            'author_id'    => $request->user()->id,
            'slug'         => Str::slug($request->title),
            // Catat waktu publikasi pertama kali; null jika masih berstatus draft.
            'published_at' => $request->status === 'published' ? now() : null,
        ]);

        if ($request->filled('categories')) {
            $post->categories()->sync($request->categories);
        }

        return response()->json($post->load('categories'), 201);
    }

    /**
     * Menampilkan detail satu artikel beserta penulis dan kategorinya.
     *
     * @param  int  $id  Primary key artikel (post_id).
     * @return \Illuminate\Http\JsonResponse  Detail artikel lengkap, atau 404 jika tidak ditemukan.
     */
    public function show(int $id): JsonResponse
    {
        $post = Post::with('author:id,name', 'categories')->findOrFail($id);
        return response()->json($post);
    }

    /**
     * Memperbarui data artikel yang sudah ada secara parsial (PATCH semantics).
     *
     * Slug diperbarui secara otomatis setiap kali judul berubah untuk menjaga
     * konsistensi URL. Namun perlu diperhatikan bahwa perubahan slug dapat
     * memutus tautan yang sudah beredar (broken link).
     *
     * `published_at` hanya diisi sekali (saat pertama kali dipublikasikan);
     * kondisi `! $post->published_at` mencegah penimpaan timestamp publikasi
     * asli jika artikel dipublikasikan ulang setelah di-archive.
     *
     * Sinkronisasi kategori menggunakan `$request->has('categories')` (bukan
     * `filled`) agar kategori dapat dikosongkan dengan mengirim array kosong `[]`.
     *
     * @param  \Illuminate\Http\Request  $request  Field yang ingin diperbarui (semua opsional).
     * @param  int                       $id       Primary key artikel yang akan diperbarui.
     * @return \Illuminate\Http\JsonResponse        Artikel yang telah diperbarui.
     *
     * @throws \Illuminate\Validation\ValidationException            Jika validasi gagal.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Jika artikel tidak ditemukan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        $request->validate([
            'title'            => 'sometimes|string|max:255',
            'content'          => 'sometimes|string',
            'excerpt'          => 'nullable|string',
            'thumbnail'        => 'nullable|string|max:255',
            'status'           => 'sometimes|in:draft,published,archived',
            'categories'       => 'nullable|array',
            'categories.*'     => 'exists:categories,category_id',
            'meta_title'       => 'nullable|string|max:160',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'    => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'title', 'content', 'excerpt', 'thumbnail', 'status',
            'meta_title', 'meta_description', 'meta_keywords',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Jangan timpa published_at jika artikel sebelumnya sudah pernah dipublikasikan.
        if (isset($data['status']) && $data['status'] === 'published' && ! $post->published_at) {
            $data['published_at'] = now();
        }

        $post->update($data);

        if ($request->has('categories')) {
            $post->categories()->sync($request->categories ?? []);
        }

        return response()->json($post->load('categories'));
    }

    /**
     * Menghapus artikel secara permanen beserta relasi kategorinya.
     *
     * Relasi pivot `post_categories` akan dihapus otomatis melalui event
     * Eloquent `deleting` jika dikonfigurasi, atau perlu dihapus manual
     * dengan `$post->categories()->detach()` sebelum `delete()` jika tidak.
     *
     * @param  int  $id  Primary key artikel yang akan dihapus.
     * @return \Illuminate\Http\JsonResponse  Konfirmasi penghapusan, atau 404 jika tidak ditemukan.
     */
    public function destroy(int $id): JsonResponse
    {
        Post::findOrFail($id)->delete();
        return response()->json(['message' => 'Post dihapus.']);
    }
}
