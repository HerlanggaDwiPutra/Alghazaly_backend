// ============================================================================
// FOLDER: 04 - Berita & Artikel (Posts)
// REQUEST: GET /api/posts/{{created_post_slug}} — Detail Berita (Public)
// Auth: Tidak perlu
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const postDetailSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'object',
            required: ['id', 'title', 'slug', 'content', 'is_published'],
            properties: {
                id:           { type: 'number' },
                title:        { type: 'string' },
                slug:         { type: 'string' },
                content:      { type: 'string' },
                thumbnail:    { type: ['string', 'null'] },
                is_published: { type: 'boolean' },
                published_at: { type: ['string', 'null'] },
                category: {
                    type: ['object', 'null'],
                    properties: {
                        id:   { type: 'number' },
                        name: { type: 'string' }
                    }
                },
                author: {
                    type: ['object', 'null'],
                    properties: {
                        id:   { type: 'number' },
                        name: { type: 'string' }
                    }
                }
            }
        }
    }
};

pm.test("[Schema] Struktur detail post sesuai kontrak", () => {
    const valid = ajv.validate(postDetailSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] is_published adalah true (post publik hanya tampil jika published)", () => {
    pm.expect(res.data.is_published).to.be.true;
});

pm.test("[Bisnis] Slug pada response sesuai dengan yang diminta", () => {
    pm.expect(res.data.slug).to.equal(pm.environment.get('created_post_slug'));
});

// ── Negatif: GET /api/posts/slug-tidak-ada → 404 ─────────────────────────────
// pm.test("[HTTP] Status 404 untuk slug yang tidak ada", () => {
//     pm.response.to.have.status(404);
// });
