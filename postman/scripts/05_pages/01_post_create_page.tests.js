// ============================================================================
// FOLDER: 05 - Halaman Statis (Pages)
// REQUEST: POST /api/admin/pages — Buat Halaman (Admin)
// Auth: Authorization: Bearer {{token_admin}}
// Body:
// {
//   "title": "Halaman QA Test",
//   "slug": "halaman-qa-{{$randomAlphaNumeric}}",
//   "content": "<p>Konten halaman QA test.</p>",
//   "is_published": true,
//   "order": 99
// }
// ============================================================================

// PRE-REQUEST SCRIPT:
// pm.collectionVariables.set('dummyPageSlug', 'halaman-qa-' + pm.variables.replaceIn('{{$randomAlphaNumeric}}').toLowerCase());

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 201 Created", () => { pm.response.to.have.status(201); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const createPageSchema = {
    type: 'object',
    required: ['message', 'data'],
    properties: {
        message: { type: 'string' },
        data: {
            type: 'object',
            required: ['id', 'title', 'slug'],
            properties: {
                id:           { type: 'number' },
                title:        { type: 'string' },
                slug:         { type: 'string' },
                is_published: { type: 'boolean' },
                order:        { type: ['number', 'null'] }
            }
        }
    }
};

pm.test("[Schema] Struktur POST /admin/pages sesuai kontrak", () => {
    const valid = ajv.validate(createPageSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.environment.set('created_page_id', res.data.id);
pm.environment.set('created_page_slug', res.data.slug);
console.log('[ENV] created_page_id =', res.data.id);

// ── GET /api/pages — Public (pisah request) ───────────────────────────────────
// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] data adalah array", () => { pm.expect(pm.response.json().data).to.be.an('array'); });

// ── GET /api/pages/{{created_page_slug}} — Detail Public ─────────────────────
// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] slug sesuai", () => { pm.expect(pm.response.json().data.slug).to.equal(pm.environment.get('created_page_slug')); });

// ── DELETE /api/admin/pages/{{created_page_id}} ───────────────────────────────
// pm.test("[HTTP] Status 204 No Content", () => { pm.response.to.have.status(204); });
// pm.test("[Bisnis] Body kosong", () => { pm.expect(pm.response.text()).to.be.empty; });
