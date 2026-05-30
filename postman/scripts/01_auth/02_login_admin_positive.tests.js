// ============================================================================
// FOLDER: 01 - Autentikasi
// REQUEST: POST /api/login — Admin Konten (Positif)
// Body: { "email": "{{admin_email}}", "password": "{{admin_password}}" }
// ============================================================================

/* ─── Helpers ─── */
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
const loginSchema = {
    type: 'object',
    required: ['token', 'user'],
    properties: {
        token: { type: 'string', minLength: 1 },
        user: {
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

pm.test("[Schema] Struktur respons login sesuai kontrak", () => {
    const valid = ajv.validate(loginSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] Role user adalah admin", () => {
    pm.expect(res.user.role).to.equal('admin');
});

pm.test("[Bisnis] Email user sesuai dengan kredensial admin", () => {
    pm.expect(res.user.email).to.equal(pm.environment.get('admin_email'));
});

// ── 5. Simpan Token ───────────────────────────────────────────────────────────
pm.environment.set('token_admin', res.token);
pm.environment.set('token_admin_fetched_at', Date.now().toString());
console.log('[AUTH] token_admin berhasil disimpan.');
