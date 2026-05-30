// ============================================================================
// FOLDER: 09 - Galeri (Gallery)
// REQUEST: GET /api/gallery?page=1 — Public
// Auth: Tidak perlu
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const gallerySchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'array',
            items: {
                type: 'object',
                required: ['id', 'title', 'image', 'is_published'],
                properties: {
                    id:           { type: 'number' },
                    title:        { type: 'string' },
                    image:        { type: ['string', 'null'] },
                    description:  { type: ['string', 'null'] },
                    is_published: { type: 'boolean' },
                    category: {
                        type: ['object', 'null'],
                        properties: {
                            id:   { type: 'number' },
                            name: { type: 'string' }
                        }
                    }
                }
            }
        }
    }
};

pm.test("[Schema] Struktur /api/gallery sesuai kontrak", () => {
    const valid = ajv.validate(gallerySchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Semua item galeri publik is_published=true", () => {
    if (res.data && res.data.length > 0) {
        const _ = require('lodash');
        const unpublished = _.filter(res.data, { is_published: false });
        pm.expect(unpublished.length).to.equal(0);
    }
});

if (res.data && res.data.length > 0) {
    pm.environment.set('created_gallery_id', res.data[0].id);
}

// ── GET /api/gallery/{{created_gallery_id}} — Detail (pisah request) ─────────
// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] id sesuai", () => {
//     pm.expect(pm.response.json().data.id).to.equal(parseInt(pm.environment.get('created_gallery_id')));
// });

// ── POST /api/admin/gallery — Upload Foto (Admin, multipart/form-data) ───────
// Fields: title, image (file), category_id, description, is_published (1/0), order
// pm.test("[HTTP] Status 201 Created", () => { pm.response.to.have.status(201); });
// pm.environment.set('created_gallery_id', pm.response.json().data.id);

// ── DELETE /api/admin/gallery/{{created_gallery_id}} ─────────────────────────
// pm.test("[HTTP] Status 204 No Content", () => { pm.response.to.have.status(204); });
