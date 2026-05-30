// ============================================================================
// FOLDER: 02 - Company Profile (Settings)
// REQUEST: PUT /api/admin/settings — Update Pengaturan (Superadmin)
// Auth: Authorization: Bearer {{token_superadmin}}
// Body:
// {
//   "settings": [
//     { "key": "site_name", "value": "SMA Al Ghazaly — QA Test" },
//     { "key": "address",   "value": "Jl. QA Test No. 99, Bandung" }
//   ]
// }
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 200 ────────────────────────────────────────────────────────
pm.test("[HTTP] Status 200 OK", () => {
    pm.response.to.have.status(200);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Kontrak Response ───────────────────────────────────────────────────────
pm.test("[Kontrak] Response mengandung 'message'", () => {
    pm.expect(res).to.have.property('message');
    pm.expect(res.message).to.be.a('string').and.not.empty;
});

pm.test("[Bisnis] Pesan menyebutkan 'updated' atau 'success'", () => {
    pm.expect(res.message.toLowerCase()).to.match(/updated|success|berhasil/);
});

// ============================================================================
// FOLDER: 02 - Company Profile (Settings)
// REQUEST: PUT /api/admin/settings — RBAC: Akses dengan token ADMIN (403)
// Auth: Authorization: Bearer {{token_admin}}
// Body: (sama seperti di atas)
// ============================================================================

// pm.test("[RBAC] Status 403 Forbidden — admin tidak bisa update settings", () => {
//     pm.response.to.have.status(403);
// });
// pm.test("[Keamanan] Response tidak mengembalikan data sensitif", () => {
//     pm.expect(pm.response.json()).to.not.have.property('data');
// });

// ============================================================================
// REQUEST: PUT /api/admin/settings — RBAC: Tanpa Auth Header (401)
// Auth: Tidak ada
// ============================================================================

// pm.test("[Keamanan] Status 401 tanpa Authorization header", () => {
//     pm.response.to.have.status(401);
// });
