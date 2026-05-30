// ============================================================================
// FOLDER: 01 - Autentikasi
// REQUEST: GET /api/me — Profil User Login (Positif)
// Auth: Authorization: Bearer {{token_superadmin}}
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status ────────────────────────────────────────────────────────────
pm.test("[HTTP] Status 200 OK", () => {
    pm.response.to.have.status(200);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Schema Validation ──────────────────────────────────────────────────────
const meSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'object',
            required: ['id', 'name', 'email', 'role'],
            properties: {
                id:    { type: 'number' },
                name:  { type: 'string' },
                email: { type: 'string' },
                role:  { type: 'string' }
            }
        }
    }
};

pm.test("[Schema] Struktur /api/me sesuai kontrak", () => {
    const valid = ajv.validate(meSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] Email user sesuai token yang digunakan", () => {
    pm.expect(res.data.email).to.equal(pm.environment.get('superadmin_email'));
});

pm.test("[Bisnis] Role user adalah superadmin", () => {
    pm.expect(res.data.role).to.equal('superadmin');
});

// ============================================================================
// SKENARIO NEGATIF — Salin ke request terpisah tanpa Authorization header
// ============================================================================
// pm.test("[HTTP] Status 401 Unauthorized tanpa token", () => {
//     pm.response.to.have.status(401);
// });
// pm.test("[Keamanan] Response mengandung pesan Unauthenticated", () => {
//     pm.expect(pm.response.json().message).to.include('Unauthenticated');
// });
