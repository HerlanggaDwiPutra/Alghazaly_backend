// ============================================================================
// FOLDER: 01 - Autentikasi
// REQUEST: POST /api/login — Negatif: Password salah
// Body: { "email": "admin@alghazaly.sch.id", "password": "WRONG_PASSWORD" }
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status harus 401 atau 422 ─────────────────────────────────────────
pm.test("[HTTP] Status bukan 200 (kredensial salah harus ditolak)", () => {
    pm.expect(pm.response.code).to.be.oneOf([401, 422, 400]);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Response tidak mengandung token ───────────────────────────────────────
pm.test("[Keamanan] Response tidak mengandung token", () => {
    pm.expect(res).to.not.have.property('token');
});

// ── 4. Response mengandung pesan error ───────────────────────────────────────
pm.test("[Bisnis] Response mengandung pesan error", () => {
    pm.expect(res).to.have.property('message');
    pm.expect(res.message).to.be.a('string').and.not.empty;
});

// ============================================================================
// REQUEST: POST /api/login — Negatif: Email tidak terdaftar
// Body: { "email": "tidakada@example.com", "password": "password" }
// ============================================================================
// (Gunakan request terpisah dengan body berbeda)

// ============================================================================
// REQUEST: POST /api/login — Negatif: Email kosong (422 Validation)
// Body: { "email": "", "password": "password" }
// ============================================================================
// pm.test("[HTTP] Status 422 Unprocessable", () => {
//     pm.response.to.have.status(422);
// });
// pm.test("[422] Field 'errors' mengandung 'email'", () => {
//     pm.expect(res.errors).to.have.property('email');
// });
