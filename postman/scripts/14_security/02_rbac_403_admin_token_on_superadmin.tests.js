// ============================================================================
// FOLDER: 14 - Keamanan (Security Testing)
// SKENARIO: RBAC — Semua endpoint superadmin dengan token admin → 403
// ============================================================================
// Copy-paste skrip ini ke tab Tests setiap request RBAC 403 di Postman.
// Endpoint yang diuji (gunakan token_admin, ekspektasi 403):
//   PUT  /api/admin/settings
//   GET  /api/admin/users
//   POST /api/admin/users
//   GET  /api/admin/users/{id}
//   PUT  /api/admin/users/{id}
//   DELETE /api/admin/users/{id}
//   GET  /api/admin/roles
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 403 Forbidden ──────────────────────────────────────────────
pm.test("[RBAC] Status 403 Forbidden — token admin tidak punya akses superadmin", () => {
    pm.response.to.have.status(403);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Keamanan ───────────────────────────────────────────────────────────────
pm.test("[Keamanan] Response tidak mengembalikan data sensitif", () => {
    pm.expect(res).to.not.have.property('data');
});

pm.test("[Keamanan] Response memiliki field 'message'", () => {
    pm.expect(res).to.have.property('message');
});

// ── 4. Pastikan token admin sendiri masih valid ───────────────────────────────
pm.test("[Keamanan] Token admin masih tersimpan di environment", () => {
    pm.expect(pm.environment.get('token_admin')).to.be.a('string').and.not.empty;
});

// ============================================================================
// SKENARIO: Token tidak valid (string random) → 401
// Header: Authorization: Bearer invalid_token_random_string
// ============================================================================

// pm.test("[Keamanan] Status 401 dengan token palsu", () => {
//     pm.response.to.have.status(401);
// });
// pm.test("[Keamanan] Response tidak mengandung 'data'", () => {
//     pm.expect(pm.response.json()).to.not.have.property('data');
// });
