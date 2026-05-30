// ============================================================================
// FOLDER: 07 - Testimoni
// FOLDER: 08 - Alumni
// ============================================================================
// Skrip ini mencakup test untuk 2 fitur yang strukturnya identik.
// Pisahkan menjadi file terpisah di Postman sesuai endpoint masing-masing.
// ============================================================================

// ─────────────────────────────────────────────────────────────────────────────
// FILE 07_01: GET /api/testimonials — Public
// ─────────────────────────────────────────────────────────────────────────────

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const _ = require('lodash');
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// --- Aktifkan blok yang sesuai sebelum running ---

// ╔══════════════════════════════════════════════════════════════════╗
// ║  BLOK A: GET /api/testimonials — Public                         ║
// ╚══════════════════════════════════════════════════════════════════╝
(function testTestimonialsPublic() {
    const res = pm.response.json();

    pm.test("[TESTIMONI][HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
    pm.test(`[TESTIMONI][Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

    const schema = {
        type: 'object',
        required: ['data'],
        properties: {
            data: {
                type: 'array',
                items: {
                    type: 'object',
                    required: ['id', 'name', 'content', 'rating', 'is_published'],
                    properties: {
                        id:           { type: 'number' },
                        name:         { type: 'string' },
                        role:         { type: ['string', 'null'] },
                        content:      { type: 'string' },
                        rating:       { type: 'number', minimum: 1, maximum: 5 },
                        photo:        { type: ['string', 'null'] },
                        is_published: { type: 'boolean' }
                    }
                }
            }
        }
    };

    pm.test("[TESTIMONI][Schema] Struktur /api/testimonials sesuai kontrak", () => {
        const valid = ajv.validate(schema, res);
        if (!valid) console.error('[AJV]', JSON.stringify(ajv.errors));
        pm.expect(valid).to.be.true;
    });

    pm.test("[TESTIMONI][Bisnis] Rating semua testimoni antara 1–5", () => {
        if (res.data && res.data.length > 0) {
            res.data.forEach(item => {
                pm.expect(item.rating).to.be.within(1, 5);
            });
        }
    });

    pm.test("[TESTIMONI][Bisnis] Semua data publik is_published=true", () => {
        if (res.data && res.data.length > 0) {
            const unpublished = _.filter(res.data, { is_published: false });
            pm.expect(unpublished.length).to.equal(0);
        }
    });

    if (res.data && res.data.length > 0) {
        pm.environment.set('created_testimonial_id', res.data[0].id);
    }
})();

// ╔══════════════════════════════════════════════════════════════════╗
// ║  BLOK B: GET /api/alumni — Public (gunakan di request terpisah) ║
// ╚══════════════════════════════════════════════════════════════════╝
/*
(function testAlumniPublic() {
    const res = pm.response.json();
    pm.test("[ALUMNI][HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
    pm.test(`[ALUMNI][Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

    const schema = {
        type: 'object',
        required: ['data'],
        properties: {
            data: {
                type: 'array',
                items: {
                    type: 'object',
                    required: ['id', 'name', 'graduation_year', 'is_published'],
                    properties: {
                        id:                  { type: 'number' },
                        name:                { type: 'string' },
                        graduation_year:     { type: 'number' },
                        current_institution: { type: ['string', 'null'] },
                        major:               { type: ['string', 'null'] },
                        achievement:         { type: ['string', 'null'] },
                        photo:               { type: ['string', 'null'] },
                        is_published:        { type: 'boolean' }
                    }
                }
            }
        }
    };

    pm.test("[ALUMNI][Schema] Struktur /api/alumni sesuai kontrak", () => {
        const valid = ajv.validate(schema, res);
        if (!valid) console.error('[AJV]', JSON.stringify(ajv.errors));
        pm.expect(valid).to.be.true;
    });

    pm.test("[ALUMNI][Bisnis] graduation_year berupa angka tahun yang valid", () => {
        if (res.data && res.data.length > 0) {
            res.data.forEach(item => {
                pm.expect(item.graduation_year).to.be.within(2000, new Date().getFullYear() + 1);
            });
        }
    });

    if (res.data && res.data.length > 0) {
        pm.environment.set('created_alumni_id', res.data[0].id);
    }
})();
*/
