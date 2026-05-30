// ============================================================================
// FOLDER: 03 - Kategori
// ============================================================================

// ─────────────────────────────────────────────────────────────────────────────
// REQUEST: GET /api/categories — Public (Positif)
// Auth: Tidak perlu
// ─────────────────────────────────────────────────────────────────────────────

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const _ = require('lodash');
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const categoriesListSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'array',
            items: {
                type: 'object',
                required: ['id', 'name', 'slug'],
                properties: {
                    id:        { type: 'number' },
                    name:      { type: 'string' },
                    slug:      { type: 'string' },
                    parent_id: {}
                }
            }
        }
    }
};

pm.test("[Schema] Struktur /api/categories sesuai kontrak", () => {
    const valid = ajv.validate(categoriesListSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Array data tidak kosong", () => {
    pm.expect(res.data).to.be.an('array').and.not.empty;
});

pm.test("[Bisnis] Setiap item memiliki slug unik", () => {
    const slugs = _.map(res.data, 'slug');
    const uniqueSlugs = _.uniq(slugs);
    pm.expect(slugs.length).to.equal(uniqueSlugs.length);
});

// Simpan ID pertama untuk pengujian GET /categories/{id}
if (res.data && res.data.length > 0) {
    pm.environment.set('created_category_id', res.data[0].id);
    console.log('[ENV] created_category_id =', res.data[0].id);
}
