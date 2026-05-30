API Reference — SMA Al Ghazaly 

## **API Reference** 

## **SMA Al Ghazaly** 

_Dokumentasi Endpoint untuk Tim Frontend_ 

Base URL: **`https://alghazaly.erri.online/api`** 

72 Endpoints  ·  12 Fitur  ·  Laravel 12 + Sanctum 

Versi 1.0  ·  Mei 2026 

Halaman 1  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **Daftar Isi** 

Daftar Isi .................................................................................................................................................. 2 1. Informasi Umum .................................................................................................................................. 4 1.1 Base URL ...................................................................................................................................... 4 1.2 Request Headers ........................................................................................................................... 4 1.3 Format Autentikasi ......................................................................................................................... 4 1.4 Format Response .......................................................................................................................... 4 1.5 Format Pagination ......................................................................................................................... 4 1.6 HTTP Status Codes ....................................................................................................................... 5 2. Autentikasi ........................................................................................................................................... 6 2.1 Login .............................................................................................................................................. 6 2.2 Logout ............................................................................................................................................ 6 2.3 Profil User Login ............................................................................................................................ 6 3. Company Profile (Pengaturan Sekolah) ............................................................................................. 7 3.1 Daftar Pengaturan (Public) ............................................................................................................ 7 3.2 Update Pengaturan ....................................................................................................................... 7 4. Berita & Artikel (Posts) ........................................................................................................................ 8 4.1 Endpoint Public .............................................................................................................................. 8 4.2 Endpoint Admin ............................................................................................................................. 8 5. Kategori ............................................................................................................................................... 9 5.1 Endpoint Public .............................................................................................................................. 9 5.2 Endpoint Admin ............................................................................................................................. 9 6. Halaman Statis (Pages) .................................................................................................................... 10 6.1 Endpoint Public ............................................................................................................................ 10 6.2 Endpoint Admin ........................................................................................................................... 10 7. Guru & Staff (Teachers) .................................................................................................................... 11 7.1 Endpoint Public ............................................................................................................................ 11 7.2 Endpoint Admin ........................................................................................................................... 11 8. Testimoni ........................................................................................................................................... 12 8.1 Endpoint Public ............................................................................................................................ 12 8.2 Endpoint Admin ........................................................................................................................... 12 9. Alumni ............................................................................................................................................... 13 9.1 Endpoint Public ............................................................................................................................ 13 9.2 Endpoint Admin ........................................................................................................................... 13 10. Galeri (Gallery) ................................................................................................................................ 14 10.1 Endpoint Public .......................................................................................................................... 14 10.2 Endpoint Admin ......................................................................................................................... 14 11. PPDB (Penerimaan Peserta Didik Baru) ........................................................................................ 15 11.1 Daftar Endpoint PPDB ............................................................................................................... 15 11.2 Submit Pendaftaran (Public) ..................................................................................................... 15 11.3 Cek Status Pendaftaran (Public) ............................................................................................... 15 

Halaman 2  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

11.4 Update Status (Admin) .............................................................................................................. 16 12. Formulir Kontak ............................................................................................................................... 17 12.1 Daftar Endpoint Form Kontak .................................................................................................... 17 12.2 Kirim Pesan Kontak (Public) ...................................................................................................... 17 13. Dashboard (Admin) ......................................................................................................................... 18 13.1 Statistik Utama .......................................................................................................................... 18 14. Manajemen User & Role ................................................................................................................. 19 14.1 Daftar Endpoint User ................................................................................................................. 19 14.2 Buat User Baru .......................................................................................................................... 19 14.3 Daftar Role ................................................................................................................................ 19 15. Akun Test ........................................................................................................................................ 20 16. Ringkasan Seluruh Endpoint .......................................................................................................... 21 

Halaman 3  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **1. Informasi Umum** 

## **1.1 Base URL** 

```
https://alghazaly.erri.online/api
```

## **1.2 Request Headers** 

**Header** `Nilai` Content-Type `application/json` Accept `application/json` Authorization `Bearer {token}  (untuk endpoint yang memerlukan autentikasi)` 

## **1.3 Format Autentikasi** 

API menggunakan Laravel Sanctum (token-based). Login untuk mendapatkan token, lalu sertakan di setiap request yang memerlukan autentikasi. 

```
POST /api/login
{ "email": "admin@alghazaly.sch.id", "password": "password" }
// Response
{ "token": "1|abcdefg...", "user": { ... } }
// Penggunaan
Authorization: Bearer 1|abcdefg...
```

## **1.4 Format Response** 

Semua response menggunakan format JSON: 

```
// Success
{
  "message": "Data berhasil diambil",
  "data": { ... }   // atau array []
}
// Error
{
  "message": "Unauthenticated.",
  "errors": { "field": ["pesan error"] }  // hanya untuk 422
}
```

## **1.5 Format Pagination** 

```
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

Query params pagination: ?page=1&per_page=15 

Halaman 4  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **1.6 HTTP Status Codes** 

|**Status**|`Arti`|
|---|---|
|200 OK|`Request berhasil`|
|201 Created|`Data berhasil dibuat`|
|204 No Content|`Data berhasil dihapus`|
|400 Bad Request|`Request tidak valid`|
|401 Unauthorized|`Token tidak ada atau expired`|
|403 Forbidden|`Tidak punya izin untuk resource ini`|
|404 Not Found|`Data tidak ditemukan`|
|422 Unprocessable|`Validasi gagal — lihat field "errors"`|
|500 Internal Error|`Kesalahan server`|



Halaman 5  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **2. Autentikasi** 

## **2.1 Login** 

**`POST`** `/api/login` — Mendapatkan token akses **Request Body:** 

```
{
  "email": "admin@alghazaly.sch.id",
  "password": "password"
}
```

**Response 200:** 

```
{
  "token": "1|AbCdEfG...",
  "user": {
    "id": 1,
    "name": "Admin Utama",
    "email": "admin@alghazaly.sch.id",
    "role": "superadmin"
  }
}
```

## **2.2 Logout** 

**`POST`** `/api/logout` — Invalidasi token (Auth required) **Response 200:** 

```
{ "message": "Logged out successfully" }
```

## **2.3 Profil User Login** 

**`GET`** `/api/me` — Data user yang sedang login (Auth required) 

**Response 200:** 

```
{
  "data": {
    "id": 1,
    "name": "Admin Utama",
    "email": "admin@alghazaly.sch.id",
    "role": "superadmin"
  }
}
```

Halaman 6  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **3. Com an Profile Pen aturan Sekolah p y ( g )** 

Mengatur identitas dan informasi umum sekolah (nama, alamat, logo, dsb). 

## **3.1 Daftar Pengaturan (Public)** 

**`GET`** `/api/settings` — Ambil semua pengaturan — Public 

**Response 200:** 

```
{
  "data": [
    { "key": "site_name", "value": "SMA Al Ghazaly" },
    { "key": "site_tagline", "value": "Islami, Cerdas, Berprestasi" },
    { "key": "address", "value": "Jl. Raya Al Ghazaly No. 1, Bandung" },
    { "key": "phone", "value": "(022) 1234-5678" },
    { "key": "email", "value": "info@alghazaly.sch.id" },
    { "key": "logo", "value": "https://..." }
  ]
}
```

## **3.2 Update Pengaturan** 

**`PUT`** `/api/admin/settings` — Update pengaturan (Auth: superadmin) 

**Request Body:** 

```
{
  "settings": [
    { "key": "site_name", "value": "SMA Al Ghazaly Bandung" },
    { "key": "address",   "value": "Jl. Baru No. 2, Bandung" }
  ]
}
```

**Response 200:** 

```
{ "message": "Settings updated successfully" }
```

Halaman 7  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **4. Berita & Artikel (Posts)** 

CRUD berita/artikel sekolah. Data publik tersedia tanpa auth; CRUD memerlukan auth admin. 

## **4.1 Endpoint Public** 

**`GET`** `/api/posts` — Daftar berita (Public, paginated) **Query Params:** 

**Param** `Keterangan` page `Nomor halaman (default: 1)` per_page `Jumlah per halaman (default: 15)` category_id `Filter by category ID` search `Cari berdasarkan judul` 

**`GET`** `/api/posts/{slug}` — Detail berita berdasarkan slug (Public) **Response 200:** 

```
{
  "data": {
    "id": 1,
    "title": "Judul Berita",
    "slug": "judul-berita",
    "content": "<p>Isi berita...</p>",
    "thumbnail": "https://...",
    "is_published": true,
    "published_at": "2026-05-01T08:00:00.000000Z",
    "category": { "id": 1, "name": "Berita Sekolah" },
    "author": { "id": 1, "name": "Admin" }
  }
}
```

## **4.2 Endpoint Admin** 

**`GET`** `/api/admin/posts` — Daftar semua berita (Auth: admin) **`POST`** `/api/admin/posts` — Buat berita baru (Auth: admin) **Request Body (POST):** `{ "title": "Judul Berita Baru", "content": "<p>Isi berita...</p>", "category_id": 1, "thumbnail": "file (multipart/form-data)", "is_published": true, "published_at": "2026-05-01 08:00:00" }` _Catatan: Upload thumbnail menggunakan Content-Type: multipart/form-data, bukan application/json._ 

**`GET`** `/api/admin/posts/{id}` — Detail berita by ID (Auth: admin) **`PUT`** `/api/admin/posts/{id}` — Update berita (Auth: admin) **`DELETE`** `/api/admin/posts/{id}` — Hapus berita (Auth: admin) 

Halaman 8  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **5. Kate ori g** 

Kategori untuk pengelompokan berita dan konten. 

## **5.1 Endpoint Public** 

**`GET`** `/api/categories` — Daftar semua kategori (Public) **Response 200:** 

```
{
  "data": [
    { "id": 1, "name": "Berita Sekolah", "slug": "berita-sekolah", "parent_id":
null },
    { "id": 4, "name": "Kegiatan", "slug": "kegiatan", "parent_id": null },
    { "id": 7, "name": "Lomba", "slug": "lomba", "parent_id": 4 }
  ]
}
```

**`GET`** `/api/categories/{id}` — Detail kategori (Public) 

## **5.2 Endpoint Admin** 

**`GET`** `/api/admin/categories` — Daftar kategori (Auth: admin) **`POST`** `/api/admin/categories` — Buat kategori baru (Auth: admin) **Request Body (POST):** 

```
{
  "category_name": "Lomba Nasional",
  "slug": "lomba-nasional",
  "parent_id": 4
}
```

**`PUT`** `/api/admin/categories/{id}` — Update kategori (Auth: admin) **`DELETE`** `/api/admin/categories/{id}` — Hapus kategori (Auth: admin) 

Halaman 9  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **6. Halaman Statis (Pages)** 

Halaman seperti Tentang Kami, Fasilitas, Ekstrakurikuler, Kontak. 

## **6.1 Endpoint Public** 

**`GET`** `/api/pages` — Daftar semua halaman (Public) **`GET`** `/api/pages/{slug}` — Detail halaman by slug (Public) **Response 200 (detail):** 

```
{
  "data": {
    "id": 1,
    "title": "Tentang Kami",
    "slug": "tentang-kami",
    "content": "<h2>Sejarah...</h2>",
    "is_published": true,
    "order": 1
  }
}
```

## **6.2 Endpoint Admin** 

**`GET`** `/api/admin/pages` — Daftar halaman (Auth: admin) **`POST`** `/api/admin/pages` — Buat halaman baru (Auth: admin) **Request Body (POST):** 

```
{
  "title": "Visi Misi",
  "slug": "visi-misi",
  "content": "<p>Visi kami...</p>",
  "is_published": true,
  "order": 5
}
```

**`GET`** `/api/admin/pages/{id}` — Detail halaman by ID (Auth: admin) **`PUT`** `/api/admin/pages/{id}` — Update halaman (Auth: admin) **`DELETE`** `/api/admin/pages/{id}` — Hapus halaman (Auth: admin) 

Halaman 10  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **7. Guru & Staff (Teachers)** 

Direktori guru dan staf sekolah. 

## **7.1 Endpoint Public** 

**`GET`** `/api/teachers` — Daftar guru (Public, paginated) **Response 200:** 

```
{
  "data": [
    {
      "id": 1,
      "name": "Drs. Ahmad Fauzi, M.Pd",
      "position": "Kepala Sekolah",
      "subject": "Manajemen Pendidikan",
      "photo": "https://...",
      "email": "kepsek@alghazaly.sch.id",
      "is_published": true
    }
  ]
}
```

**`GET`** `/api/teachers/{id}` — Detail guru (Public) 

## **7.2 Endpoint Admin** 

**`GET`** `/api/admin/teachers` — Daftar guru (Auth: admin) **`POST`** `/api/admin/teachers` — Tambah guru (Auth: admin) **Request Body (POST):** 

```
{
  "name": "Ir. Budi Santoso, M.T",
  "position": "Guru Tetap",
  "subject": "Matematika",
  "email": "budi@alghazaly.sch.id",
  "phone": "081234567890",
  "photo": "file (multipart/form-data)",
  "bio": "Lulusan ITB jurusan Matematika...",
  "is_published": true,
  "order": 1
}
```

**`GET`** `/api/admin/teachers/{id}` — Detail guru by ID (Auth: admin) **`PUT`** `/api/admin/teachers/{id}` — Update guru (Auth: admin) **`DELETE`** `/api/admin/teachers/{id}` — Hapus guru (Auth: admin) 

Halaman 11  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **8. Testimoni** 

Testimonial dari siswa, orang tua, atau alumni. 

## **8.1 Endpoint Public** 

**`GET`** `/api/testimonials` — Daftar testimoni (Public, hanya is_published=true) **Response 200:** 

```
{
  "data": [
    {
      "id": 1,
      "name": "Bapak Hendra Kusuma",
      "role": "Orang Tua Siswa",
      "content": "SMA Al Ghazaly luar biasa...",
      "rating": 5,
      "photo": "https://...",
      "is_published": true,
      "order": 1
    }
  ]
}
```

## **8.2 Endpoint Admin** 

**`GET`** `/api/admin/testimonials` — Daftar semua testimoni (Auth: admin) **`POST`** `/api/admin/testimonials` — Tambah testimoni (Auth: admin) **Request Body (POST):** `{ "name": "Ibu Sari Dewi", "role": "Orang Tua Siswa", "content": "Alhamdulillah anak saya berkembang sangat baik...", "rating": 5, "photo": "file (multipart/form-data)", "is_published": true, "order": 3 }` **`GET`** `/api/admin/testimonials/{id}` — Detail testimoni (Auth: admin) **`PUT`** `/api/admin/testimonials/{id}` — Update testimoni (Auth: admin) **`DELETE`** `/api/admin/testimonials/{id}` — Hapus testimoni (Auth: admin) 

Halaman 12  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **9. Alumni** 

Profil alumni berprestasi SMA Al Ghazaly. 

## **9.1 Endpoint Public** 

**`GET`** `/api/alumni` — Daftar alumni (Public, hanya is_published=true) **Response 200:** 

```
{
  "data": [
    {
      "id": 1,
      "name": "Aditya Prasetyo",
      "graduation_year": 2023,
      "current_institution": "Institut Teknologi Bandung",
      "major": "Teknik Informatika",
      "achievement": "Diterima di ITB melalui jalur SNBP...",
      "photo": "https://...",
      "is_published": true
    }
  ]
}
```

**`GET`** `/api/alumni/{id}` — Detail alumni (Public) 

## **9.2 Endpoint Admin** 

**`GET`** `/api/admin/alumni` — Daftar semua alumni (Auth: admin) **`POST`** `/api/admin/alumni` — Tambah alumni (Auth: admin) **Request Body (POST):** `{ "name": "Nabila Zahra Putri", "graduation_year": 2022, "current_institution": "Universitas Padjadjaran", "major": "Kedokteran", "achievement": "Lulus SNBT dengan nilai tertinggi angkatannya...", "photo": "file (multipart/form-data)", "is_published": true }` **`GET`** `/api/admin/alumni/{id}` — Detail alumni by ID (Auth: admin) **`PUT`** `/api/admin/alumni/{id}` — Update alumni (Auth: admin) **`DELETE`** `/api/admin/alumni/{id}` — Hapus alumni (Auth: admin) 

Halaman 13  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **10. Galeri (Gallery)** 

Galeri foto kegiatan sekolah. 

## **10.1 Endpoint Public** 

**`GET`** `/api/gallery` — Daftar galeri (Public) 

**Query Params:** 

**Param** `Keterangan` category_id `Filter by category ID` page `Nomor halaman` 

**Response 200:** 

```
{
  "data": [
    {
      "id": 1,
      "title": "Wisuda Angkatan 2024",
      "image": "https://...",
      "category": { "id": 3, "name": "Wisuda" },
      "description": "Acara wisuda angkatan 2024...",
      "is_published": true
    }
  ]
}
```

**`GET`** `/api/gallery/{id}` — Detail galeri (Public) 

## **10.2 Endpoint Admin** 

**`GET`** `/api/admin/gallery` — Daftar galeri (Auth: admin) **`POST`** `/api/admin/gallery` — Upload foto galeri (Auth: admin) **Request Body (multipart/form-data):** 

```
title:         "Wisuda Angkatan 2024"
image:         [file foto]
category_id:   3
description:   "Acara wisuda..."
is_published:  1
order:         1
```

**`GET`** `/api/admin/gallery/{id}` — Detail galeri by ID (Auth: admin) **`PUT`** `/api/admin/gallery/{id}` — Update galeri (Auth: admin) **`DELETE`** `/api/admin/gallery/{id}` — Hapus galeri (Auth: admin) 

Halaman 14  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **11. PPDB (Penerimaan Peserta Didik Baru)** 

Sistem pendaftaran siswa baru online. Pendaftar mengisi form publik, admin mengelola dan memverifikasi. 

## **11.1 Daftar Endpoint PPDB** 

**Endpoint** **`Akses` Keterangan** POST /api/registrations `Public` Submit formulir pendaftaran baru GET /api/registrations/{number} `Public` Cek status pendaftaran by nomor GET /api/admin/registrations `Auth: admin` Daftar semua pendaftar GET /api/admin/registrations/{id} `Auth: admin` Detail pendaftar by ID PATCH `Auth: admin` Update status pendaftar /api/admin/registrations/{id}/status DELETE `Auth: admin` Hapus data pendaftar /api/admin/registrations/{id} 

## **11.2 Submit Pendaftaran (Public)** 

**`POST`** `/api/registrations` — Daftar peserta didik baru **Request Body:** 

```
{
  "full_name":       "Ahmad Rizqi Maulana",
  "birth_date":      "2009-03-15",
  "birth_place":     "Bandung",
  "gender":          "L",
  "address":         "Jl. Merdeka No. 12, Bandung",
  "phone":           "081234567001",
  "parent_name":     "Hendra Maulana",
  "parent_phone":    "081234560001",
  "previous_school": "SMP Negeri 1 Bandung",
  "academic_year":   "2025/2026"
}
```

**Response 201:** 

```
{
  "message": "Pendaftaran berhasil",
  "data": {
    "registration_number": "PPDB-2025-0006",
    "status": "pending",
    "full_name": "Ahmad Rizqi Maulana"
  }
}
```

## **11.3 Cek Status Pendaftaran (Public)** 

**`GET`** `/api/registrations/{registration_number}` — Cek status by nomor pendaftaran **Contoh:** 

`GET /api/registrations/PPDB-2025-0001` **Response 200:** 

```
{
  "data": {
    "registration_number": "PPDB-2025-0001",
    "full_name": "Ahmad Rizqi Maulana",
    "status": "verified",
```

Halaman 15  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

```
    "notes": null
  }
}
```

## **11.4 Update Status (Admin)** 

**`PATCH`** `/api/admin/registrations/{id}/status` — Ubah status pendaftar 

**Request Body:** 

```
{
  "status": "accepted",
  "notes": "Diterima jalur prestasi, nilai rapor sangat baik"
}
```

**Nilai status yang valid:** 

**Status** `Keterangan` pending `Menunggu verifikasi (default)` verified `Dokumen telah diverifikasi` accepted `Diterima sebagai siswa baru` rejected `Tidak diterima` 

Halaman 16  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **12. Formulir Kontak** 

Form pesan/pertanyaan dari pengunjung website. 

## **12.1 Daftar Endpoint Form Kontak** 

**Endpoint** **`Akses` Keterangan** POST /api/contact `Public` Kirim pesan kontak GET /api/admin/contact `Auth: admin` Daftar semua pesan masuk GET /api/admin/contact/{id} `Auth: admin` Detail pesan PATCH `Auth: admin` Tandai pesan sebagai dibaca /api/admin/contact/{id}/read DELETE /api/admin/contact/{id} `Auth: admin` Hapus pesan 

## **12.2 Kirim Pesan Kontak (Public)** 

**`POST`** `/api/contact` — Kirim pesan dari form kontak 

**Request Body:** 

```
{
  "name":    "Budi Santoso",
  "email":   "budi@gmail.com",
  "phone":   "081234567890",
  "subject": "Informasi PPDB 2025",
  "message": "Selamat siang, saya ingin bertanya..."
}
```

## **Response 201:** 

```
{ "message": "Pesan Anda telah terkirim. Kami akan segera menghubungi Anda." }
```

Halaman 17  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **13. Dashboard (Admin)** 

Statistik dan ringkasan data untuk halaman dashboard admin. 

## **13.1 Statistik Utama** 

**`GET`** `/api/admin/dashboard` — Ringkasan statistik (Auth: admin) **Response 200:** 

```
{
  "data": {
    "total_posts": 24,
    "total_teachers": 32,
    "total_alumni": 8,
    "total_testimonials": 10,
    "total_gallery": 45,
    "ppdb": {
      "total": 5,
      "pending": 1,
      "verified": 2,
      "accepted": 1,
      "rejected": 1
    },
    "unread_contacts": 3,
    "recent_posts": [ ... ],
    "recent_registrations": [ ... ]
  }
}
```

Halaman 18  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **14. Manajemen User & Role** 

Pengelolaan akun admin (superadmin only). 

## **14.1 Daftar Endpoint User** 

|**Endpoint**|**`Akses`**|**Keterangan**|
|---|---|---|
|GET /api/admin/users|`superadmin`|Daftar semua user|
|POST /api/admin/users|`superadmin`|Buat user baru|
|GET /api/admin/users/{id}|`superadmin`|Detail user|
|PUT /api/admin/users/{id}|`superadmin`|Update user|
|DELETE /api/admin/users/{id}|`superadmin`|Hapus user|
|GET /api/admin/roles|`superadmin`|Daftar role|



## **14.2 Buat User Baru** 

**`POST`** `/api/admin/users` — Buat akun admin baru (Auth: superadmin) 

**Request Body:** 

```
{
  "name":     "Staff Konten",
  "email":    "konten@alghazaly.sch.id",
  "password": "password123",
  "role_id":  2
}
```

**Response 201:** 

```
{
  "message": "User created successfully",
  "data": { "id": 5, "name": "Staff Konten", "email": "...", "role": "admin" }
}
```

## **14.3 Daftar Role** 

**`GET`** `/api/admin/roles` — Daftar role yang tersedia (Auth: superadmin) **Response 200:** 

```
{
  "data": [
    { "id": 1, "name": "superadmin", "display_name": "Super Admin" },
    { "id": 2, "name": "admin",      "display_name": "Admin" }
  ]
}
```

Halaman 19  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **15. Akun Test** 

Akun yang tersedia untuk pengujian di environment development: 

|**Nama**|**Email**|**Password**|**Role**|
|---|---|---|---|
|Admin Utama|`admin@alghazaly.sch.id`|`password`|superadmin|
|Admin Konten|`admin2@alghazaly.sch.id`|`password`|admin|



_PENTING: Ganti password semua akun test sebelum production launch!_ 

Halaman 20  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

## **16. Rin kasan Seluruh End oint g p** 

|**No**|**Endpoint**|**Method**|**Akses**|
|---|---|---|---|
|1|`/api/login`|`POST`|Public|
|2|`/api/logout`|`POST`|Auth|
|3|`/api/me`|`GET`|Auth|
|4|`/api/settings`|`GET`|Public|
|5|`/api/admin/settings`|`PUT`|superadmin|
|6|`/api/categories`|`GET`|Public|
|7|`/api/categories/{id}`|`GET`|Public|
|8|`/api/admin/categories`|`GET`|Admin|
|9|`/api/admin/categories`|`POST`|Admin|
|10|`/api/admin/categories/{id}`|`GET`|Admin|
|11|`/api/admin/categories/{id}`|`PUT`|Admin|
|12|`/api/admin/categories/{id}`|`DELETE`|Admin|
|13|`/api/posts`|`GET`|Public|
|14|`/api/posts/{slug}`|`GET`|Public|
|15|`/api/admin/posts`|`GET`|Admin|
|16|`/api/admin/posts`|`POST`|Admin|
|17|`/api/admin/posts/{id}`|`GET`|Admin|
|18|`/api/admin/posts/{id}`|`PUT`|Admin|
|19|`/api/admin/posts/{id}`|`DELETE`|Admin|
|20|`/api/pages`|`GET`|Public|
|21|`/api/pages/{slug}`|`GET`|Public|
|22|`/api/admin/pages`|`GET`|Admin|
|23|`/api/admin/pages`|`POST`|Admin|
|24|`/api/admin/pages/{id}`|`GET`|Admin|
|25|`/api/admin/pages/{id}`|`PUT`|Admin|
|26|`/api/admin/pages/{id}`|`DELETE`|Admin|
|27|`/api/teachers`|`GET`|Public|
|28|`/api/teachers/{id}`|`GET`|Public|
|29|`/api/admin/teachers`|`GET`|Admin|
|30|`/api/admin/teachers`|`POST`|Admin|
|31|`/api/admin/teachers/{id}`|`GET`|Admin|
|32|`/api/admin/teachers/{id}`|`PUT`|Admin|
|33|`/api/admin/teachers/{id}`|`DELETE`|Admin|
|34|`/api/testimonials`|`GET`|Public|
|35|`/api/admin/testimonials`|`GET`|Admin|
|36|`/api/admin/testimonials`|`POST`|Admin|
|37|`/api/admin/testimonials/{id}`|`GET`|Admin|
|38|`/api/admin/testimonials/{id}`|`PUT`|Admin|
|39|`/api/admin/testimonials/{id}`|`DELETE`|Admin|
|40|`/api/alumni`|`GET`|Public|
|41|`/api/alumni/{id}`|`GET`|Public|
|42|`/api/admin/alumni`|`GET`|Admin|
|43|`/api/admin/alumni`|`POST`|Admin|
|44|`/api/admin/alumni/{id}`|`GET`|Admin|
|45|`/api/admin/alumni/{id}`|`PUT`|Admin|
|46|`/api/admin/alumni/{id}`|`DELETE`|Admin|
|47|`/api/gallery`|`GET`|Public|
|48|`/api/gallery/{id}`|`GET`|Public|



Halaman 21  |  Konfidensial — Hanya untuk Tim Internal 

API Reference — SMA Al Ghazaly 

|49|`/api/admin/gallery`|`GET`|Admin|
|---|---|---|---|
|50|`/api/admin/gallery`|`POST`|Admin|
|51|`/api/admin/gallery/{id}`|`GET`|Admin|
|52|`/api/admin/gallery/{id}`|`PUT`|Admin|
|53|`/api/admin/gallery/{id}`|`DELETE`|Admin|
|54|`/api/registrations`|`POST`|Public|
|55|`/api/registrations/{number}`|`GET`|Public|
|56|`/api/admin/registrations`|`GET`|Admin|
|57|`/api/admin/registrations/{id}`|`GET`|Admin|
|58|`/api/admin/registrations/{id}/status`|`PATCH`|Admin|
|59|`/api/admin/registrations/{id}`|`DELETE`|Admin|
|60|`/api/contact`|`POST`|Public|
|61|`/api/admin/contact`|`GET`|Admin|
|62|`/api/admin/contact/{id}`|`GET`|Admin|
|63|`/api/admin/contact/{id}/read`|`PATCH`|Admin|
|64|`/api/admin/contact/{id}`|`DELETE`|Admin|
|65|`/api/admin/dashboard`|`GET`|Admin|
|66|`/api/admin/users`|`GET`|superadmin|
|67|`/api/admin/users`|`POST`|superadmin|
|68|`/api/admin/users/{id}`|`GET`|superadmin|
|69|`/api/admin/users/{id}`|`PUT`|superadmin|
|70|`/api/admin/users/{id}`|`DELETE`|superadmin|
|71|`/api/admin/roles`|`GET`|superadmin|
|72|`/api/webhook/midtrans`|`POST`|Internal|



Halaman 22  |  Konfidensial — Hanya untuk Tim Internal 

