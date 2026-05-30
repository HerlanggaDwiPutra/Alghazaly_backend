// ============================================================================
// FOLDER: 02 - Company Profile (Settings)
// REQUEST: PUT /api/admin/settings — RBAC SECURITY: Akses dengan token ADMIN
// Auth: Authorization: Bearer {{token_admin}}
// Ekspektasi: 403 Forbidden (superadmin only endpoint)
// Body:
// {
//   "settings": [{ "key": "site_name", "value": "Hacked!" }]
// }
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 403 ────────────────────────────────────────────────────────
pm.test("[RBAC] Status 403 Forbidden — role admin tidak boleh update settings", () => {
    pm.response.to.have.status(403);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Keamanan: Response tidak mengembalikan data sensitif ──────────────────
pm.test("[Keamanan] Response tidak mengembalikan data settings", () => {
    pm.expect(res).to.not.have.property('data');
});

pm.test("[Keamanan] Response mengandung pesan 'message'", () => {
    pm.expect(res).to.have.property('message');
});

// ── 4. Tidak ada perubahan data yang terjadi ─────────────────────────────────
pm.test("[RBAC] Role 'admin' tidak memiliki akses ke endpoint superadmin", () => {
    // Jika status 403, maka kita yakin data tidak berubah
    pm.expect(pm.response.code).to.equal(403);
});
