// ============================================================================
// FOLDER: 02 - Company Profile (Settings)
// REQUEST: GET /api/settings — Public (Positif)
// Auth: Tidak perlu
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
const settingsSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'array',
            items: {
                type: 'object',
                required: ['key', 'value'],
                properties: {
                    key:   { type: 'string' },
                    value: {}
                }
            }
        }
    }
};

pm.test("[Schema] Struktur /api/settings sesuai kontrak", () => {
    const valid = ajv.validate(settingsSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] Array data tidak kosong", () => {
    pm.expect(res.data).to.be.an('array').and.not.empty;
});

pm.test("[Bisnis] Mengandung key 'site_name'", () => {
    const _ = require('lodash');
    const siteNameItem = _.find(res.data, { key: 'site_name' });
    pm.expect(siteNameItem).to.not.be.undefined;
    pm.expect(siteNameItem.value).to.be.a('string').and.not.empty;
});

pm.test("[Bisnis] Mengandung key 'email'", () => {
    const _ = require('lodash');
    const emailItem = _.find(res.data, { key: 'email' });
    pm.expect(emailItem).to.not.be.undefined;
});

// ── 5. Header Content-Type ────────────────────────────────────────────────────
pm.test("[Header] Content-Type adalah application/json", () => {
    pm.expect(pm.response.headers.get('Content-Type')).to.include('application/json');
});
