// ============================================================================
// FOLDER: 06 - Guru & Staff (Teachers)
// REQUEST: GET /api/teachers?page=1&per_page=15 — Public (Paginated)
// Auth: Tidak perlu
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const teachersSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'array',
            items: {
                type: 'object',
                required: ['id', 'name', 'position', 'is_published'],
                properties: {
                    id:           { type: 'number' },
                    name:         { type: 'string' },
                    position:     { type: 'string' },
                    subject:      { type: ['string', 'null'] },
                    photo:        { type: ['string', 'null'] },
                    email:        { type: ['string', 'null'] },
                    is_published: { type: 'boolean' }
                }
            }
        }
    }
};

pm.test("[Schema] Struktur /api/teachers sesuai kontrak", () => {
    const valid = ajv.validate(teachersSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Semua guru yang tampil di publik memiliki is_published=true", () => {
    if (res.data && res.data.length > 0) {
        const _ = require('lodash');
        const unpublished = _.filter(res.data, { is_published: false });
        pm.expect(unpublished.length).to.equal(0, 'Data publik tidak boleh mengandung guru yang tidak dipublikasi');
    }
});

if (res.data && res.data.length > 0) {
    pm.environment.set('created_teacher_id', res.data[0].id);
}

// ============================================================================
// REQUEST: POST /api/admin/teachers — Tambah Guru (Admin)
// Auth: Authorization: Bearer {{token_admin}}
// Content-Type: multipart/form-data
// Fields:
//   name         : "Bpk. QA Test M.Pd"
//   position     : "Guru Tetap"
//   subject      : "QA Automation"
//   email        : "qa-teacher-{{$randomAlphaNumeric}}@alghazaly.sch.id"
//   phone        : "081234500000"
//   bio          : "Guru QA untuk keperluan testing otomatis."
//   is_published : 1
//   order        : 99
// ============================================================================

// pm.test("[HTTP] Status 201 Created", () => { pm.response.to.have.status(201); });
// pm.test("[Bisnis] ID guru baru tersimpan", () => {
//     const r = pm.response.json();
//     pm.expect(r.data.id).to.be.a('number').and.above(0);
//     pm.environment.set('created_teacher_id', r.data.id);
// });

// ── DELETE /api/admin/teachers/{{created_teacher_id}} ────────────────────────
// pm.test("[HTTP] Status 204 No Content", () => { pm.response.to.have.status(204); });
