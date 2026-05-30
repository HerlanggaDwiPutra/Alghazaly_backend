// ============================================================================
// FOLDER: 01 - Autentikasi
// REQUEST: GET /api/me — Negatif: Tanpa Authorization Header (401)
// Auth: (tidak ada header Authorization)
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 401 Unauthorized", () => {
    pm.response.to.have.status(401);
});

pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

pm.test("[Keamanan] Response mengandung field 'message'", () => {
    pm.expect(res).to.have.property('message');
});

pm.test("[Keamanan] Response tidak mengandung data user", () => {
    pm.expect(res).to.not.have.property('data');
});

// ============================================================================
// FOLDER: 01 - Autentikasi
// REQUEST: POST /api/logout — Positif (gunakan file terpisah)
// Auth: Authorization: Bearer {{token_superadmin}}
// ============================================================================
// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] Response mengandung pesan logout", () => {
//     pm.expect(pm.response.json().message).to.include('Logged out');
// });
// pm.environment.unset('token_superadmin');
// pm.environment.unset('token_superadmin_fetched_at');
