// ============================================================================
// FOLDER: 04 - Berita & Artikel (Posts)
// REQUEST: POST /api/admin/posts — Buat Berita Baru (Admin)
// Auth: Authorization: Bearer {{token_admin}}
// Content-Type: multipart/form-data (karena ada thumbnail)
//
// Form Fields:
//   title        : "Berita QA - {{$randomCatchPhrase}}"
//   content      : "<p>Ini adalah konten berita QA otomatis.</p>"
//   category_id  : {{created_category_id}}
//   is_published : true
//   published_at : "2026-05-01 08:00:00"
//   (thumbnail   : [opsional, file — kosongkan untuk test JSON-only)
//
// PRE-REQUEST SCRIPT:
// ─────────────────────────────────────────────────────────────────────────────
// pm.collectionVariables.set(
//     'dummyPostTitle',
//     'Berita QA - ' + pm.variables.replaceIn('{{$randomCatchPhrase}}')
// );
// pm.collectionVariables.set(
//     'dummyPostSlug',
//     'berita-qa-' + pm.variables.replaceIn('{{$randomAlphaNumeric}}').toLowerCase()
// );
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

// ── 3. Schema Validation ──────────────────────────────────────────────────────
const createPostSchema = {
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
                is_published: { type: 'boolean' }
            }
        }
    }
};

pm.test("[Schema] Struktur POST /admin/posts sesuai kontrak", () => {
    const valid = ajv.validate(createPostSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] ID post baru adalah number positif", () => {
    pm.expect(res.data.id).to.be.a('number').and.above(0);
});

pm.test("[Bisnis] Slug tidak kosong", () => {
    pm.expect(res.data.slug).to.be.a('string').and.not.empty;
});

// ── 5. Simpan ID dan slug ─────────────────────────────────────────────────────
pm.environment.set('created_post_id', res.data.id);
pm.environment.set('created_post_slug', res.data.slug);
console.log('[ENV] created_post_id =', res.data.id, '| created_post_slug =', res.data.slug);

// ============================================================================
// NEGATIF: POST /api/admin/posts — 422 Validasi: Tanpa title
// Body: { "content": "Tanpa title" }  (title wajib)
// ============================================================================
// pm.test("[HTTP] Status 422 Unprocessable", () => { pm.response.to.have.status(422); });
// pm.test("[422] errors.title ada", () => {
//     pm.expect(pm.response.json().errors).to.have.property('title');
// });
