// ============================================================================
// FOLDER: 14 - Keamanan (Security Testing)
// SKENARIO: Pengujian 401 Unauthorized untuk semua endpoint Auth/Admin
// ============================================================================
// Buat request terpisah di Postman untuk masing-masing endpoint di bawah,
// TANPA menyertakan header Authorization.
// ============================================================================

/**
 * Skrip ini SAMA untuk semua request "401 — No Auth" di folder Security.
 * Copy-paste ke tab Tests setiap request tersebut.
 */

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 401 ────────────────────────────────────────────────────────
pm.test("[Keamanan] Status 401 Unauthorized — tidak ada token", () => {
    pm.response.to.have.status(401);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Response mengandung pesan error ───────────────────────────────────────
pm.test("[Keamanan] Response memiliki field 'message'", () => {
    pm.expect(res).to.have.property('message');
    pm.expect(res.message).to.be.a('string').and.not.empty;
});

// ── 4. Response TIDAK mengandung data sensitif ───────────────────────────────
pm.test("[Keamanan] Response tidak mengandung key 'data'", () => {
    pm.expect(res).to.not.have.property('data');
});

pm.test("[Keamanan] Response tidak mengandung token", () => {
    pm.expect(res).to.not.have.property('token');
});

// ── Daftar endpoint yang harus diuji dengan skenario ini: ────────────────────
// [Auth Endpoints]
//   POST /api/logout              → 401
//   GET  /api/me                  → 401
//
// [Company Profile]
//   PUT  /api/admin/settings      → 401
//
// [Berita]
//   GET  /api/admin/posts         → 401
//   POST /api/admin/posts         → 401
//
// [Kategori]
//   GET  /api/admin/categories    → 401
//   POST /api/admin/categories    → 401
//
// [Halaman Statis]
//   GET  /api/admin/pages         → 401
//
// [Guru]
//   GET  /api/admin/teachers      → 401
//
// [Testimoni]
//   GET  /api/admin/testimonials  → 401
//
// [Alumni]
//   GET  /api/admin/alumni        → 401
//
// [Galeri]
//   GET  /api/admin/gallery       → 401
//
// [PPDB]
//   GET  /api/admin/registrations → 401
//
// [Kontak]
//   GET  /api/admin/contact       → 401
//
// [Dashboard]
//   GET  /api/admin/dashboard     → 401
//
// [Users (Superadmin)]
//   GET  /api/admin/users         → 401
//   GET  /api/admin/roles         → 401
