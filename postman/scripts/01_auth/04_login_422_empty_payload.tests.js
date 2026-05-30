// ============================================================================
// FOLDER: 01 - Autentikasi
// REQUEST: POST /api/login — Negatif: Payload kosong (422 Validation Error)
// Body: {} (empty JSON object)
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 422 ───────────────────────────────────────────────────────
pm.test("[HTTP] Status 422 Unprocessable Entity", () => {
    pm.response.to.have.status(422);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Schema Validasi 422 ────────────────────────────────────────────────────
const schema422 = {
    type: 'object',
    required: ['message', 'errors'],
    properties: {
        message: { type: 'string' },
        errors: {
            type: 'object',
            minProperties: 1
        }
    }
};

pm.test("[Schema] Struktur respons 422 sesuai kontrak (message + errors)", () => {
    const valid = ajv.validate(schema422, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Field errors mengandung 'email' dan 'password' ─────────────────────────
pm.test("[422] errors.email ada dan berisi array pesan", () => {
    pm.expect(res.errors).to.have.property('email');
    pm.expect(res.errors.email).to.be.an('array').and.not.empty;
});

pm.test("[422] errors.password ada dan berisi array pesan", () => {
    pm.expect(res.errors).to.have.property('password');
    pm.expect(res.errors.password).to.be.an('array').and.not.empty;
});

// ── 5. Response tidak mengandung token ───────────────────────────────────────
pm.test("[Keamanan] Response tidak mengandung token", () => {
    pm.expect(res).to.not.have.property('token');
});
