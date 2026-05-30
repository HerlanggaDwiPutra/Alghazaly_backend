// ============================================================================
// FOLDER: 13 - Manajemen User & Role
// REQUEST: GET /api/admin/roles — Daftar Role (Superadmin)
// Auth: Authorization: Bearer {{token_superadmin}}
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const rolesSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'array',
            minItems: 2,
            items: {
                type: 'object',
                required: ['id', 'name'],
                properties: {
                    id:           { type: 'number' },
                    name:         { type: 'string' },
                    display_name: { type: ['string', 'null'] }
                }
            }
        }
    }
};

pm.test("[Schema] Struktur /admin/roles sesuai kontrak", () => {
    const valid = ajv.validate(rolesSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Ada minimal 2 role: superadmin dan admin", () => {
    const _ = require('lodash');
    const roleNames = _.map(res.data, 'name');
    pm.expect(roleNames).to.include('superadmin');
    pm.expect(roleNames).to.include('admin');
});

pm.test("[Bisnis] Setiap role memiliki ID unik", () => {
    const _ = require('lodash');
    const ids = _.map(res.data, 'id');
    pm.expect(_.uniq(ids).length).to.equal(ids.length);
});

// ── RBAC: Akses dengan token admin → 403 ─────────────────────────────────────
// pm.test("[RBAC] Status 403 — admin tidak dapat akses /admin/roles", () => {
//     pm.response.to.have.status(403);
// });
