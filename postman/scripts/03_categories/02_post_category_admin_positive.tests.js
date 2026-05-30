// ============================================================================
// FOLDER: 03 - Kategori
// REQUEST: POST /api/admin/categories — Buat Kategori Baru (Positif)
// Auth: Authorization: Bearer {{token_admin}}
// Pre-request Script: Set dynamic dummy data
// ============================================================================

// ─── PRE-REQUEST SCRIPT (tempel di tab Pre-request): ─────────────────────────
/*
pm.collectionVariables.set(
    'dummyCategoryName',
    'Kategori QA - ' + pm.variables.replaceIn('{{$randomCatchPhrase}}')
);
pm.collectionVariables.set(
    'dummyCategorySlug',
    'kategori-qa-' + pm.variables.replaceIn('{{$randomAlphaNumeric}}').toLowerCase()
);
*/
// Body yang digunakan:
// {
//   "category_name": "{{dummyCategoryName}}",
//   "slug": "{{dummyCategorySlug}}",
//   "parent_id": null
// }
// ─────────────────────────────────────────────────────────────────────────────

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 201 Created ────────────────────────────────────────────────
pm.test("[HTTP] Status 201 Created", () => {
    pm.response.to.have.status(201);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Kontrak Response ───────────────────────────────────────────────────────
pm.test("[Kontrak] Response memiliki 'message' dan 'data'", () => {
    pm.expect(res).to.have.property('message');
    pm.expect(res).to.have.property('data');
});

// ── 4. Schema Validation ──────────────────────────────────────────────────────
const categorySchema = {
    type: 'object',
    required: ['message', 'data'],
    properties: {
        message: { type: 'string' },
        data: {
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
};

pm.test("[Schema] Struktur POST /admin/categories sesuai kontrak", () => {
    const valid = ajv.validate(categorySchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 5. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] ID kategori baru adalah number positif", () => {
    pm.expect(res.data.id).to.be.a('number').and.above(0);
});

// ── 6. Simpan ID ke Environment untuk pengujian berikutnya ───────────────────
pm.environment.set('created_category_id', res.data.id);
console.log('[ENV] created_category_id =', res.data.id);
