// ============================================================================
// FOLDER: 04 - Berita & Artikel (Posts)
// ============================================================================

// ─────────────────────────────────────────────────────────────────────────────
// REQUEST: GET /api/posts?page=1&per_page=15 — Public dengan Pagination
// Auth: Tidak perlu
// URL: {{base_url}}/posts?page=1&per_page=15
// ─────────────────────────────────────────────────────────────────────────────

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const _ = require('lodash');
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

// ── Schema Pagination ─────────────────────────────────────────────────────────
const paginatedPostsSchema = {
    type: 'object',
    required: ['data', 'meta'],
    properties: {
        data: { type: 'array' },
        meta: {
            type: 'object',
            required: ['current_page', 'last_page', 'per_page', 'total'],
            properties: {
                current_page: { type: 'number' },
                last_page:    { type: 'number' },
                per_page:     { type: 'number' },
                total:        { type: 'number' }
            }
        }
    }
};

pm.test("[Schema] Struktur pagination /api/posts sesuai kontrak", () => {
    const valid = ajv.validate(paginatedPostsSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Pagination] current_page = 1 sesuai query param", () => {
    pm.expect(res.meta.current_page).to.equal(1);
});

pm.test("[Pagination] per_page = 15 sesuai query param", () => {
    pm.expect(res.meta.per_page).to.equal(15);
});

pm.test("[Pagination] Jumlah item dalam data <= per_page", () => {
    pm.expect(res.data.length).to.be.at.most(res.meta.per_page);
});

// ── Simpan slug post pertama ─────────────────────────────────────────────────
if (res.data && res.data.length > 0) {
    pm.environment.set('created_post_slug', res.data[0].slug);
    console.log('[ENV] created_post_slug =', res.data[0].slug);
}
