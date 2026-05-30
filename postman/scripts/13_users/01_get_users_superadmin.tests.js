// ============================================================================
// FOLDER: 13 - Manajemen User & Role (Superadmin Only)
// ============================================================================

// ─────────────────────────────────────────────────────────────────────────────
// REQUEST: GET /api/admin/users — Daftar User (Superadmin)
// Auth: Authorization: Bearer {{token_superadmin}}
// ─────────────────────────────────────────────────────────────────────────────

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const usersListSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'array',
            items: {
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
    }
};

pm.test("[Schema] Struktur /admin/users sesuai kontrak", () => {
    const valid = ajv.validate(usersListSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Daftar user tidak kosong (minimal ada 1 superadmin)", () => {
    pm.expect(res.data).to.be.an('array').and.not.empty;
});

pm.test("[Bisnis] Semua role adalah 'superadmin' atau 'admin'", () => {
    res.data.forEach(user => {
        pm.expect(['superadmin', 'admin']).to.include(user.role);
    });
});
