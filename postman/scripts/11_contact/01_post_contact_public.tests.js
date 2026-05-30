// ============================================================================
// FOLDER: 11 - Formulir Kontak
// REQUEST: POST /api/contact — Kirim Pesan (Public)
// Auth: Tidak perlu
// ============================================================================

// PRE-REQUEST SCRIPT:
// pm.collectionVariables.set('dummySenderName', pm.variables.replaceIn('{{$randomFullName}}'));
// pm.collectionVariables.set('dummySenderEmail', pm.variables.replaceIn('{{$randomEmail}}'));

// Body (JSON):
// {
//   "name":    "{{dummySenderName}}",
//   "email":   "{{dummySenderEmail}}",
//   "phone":   "081234567890",
//   "subject": "Pertanyaan QA Test — {{$randomCatchPhrase}}",
//   "message": "Ini adalah pesan otomatis dari QA Testing Suite. Harap abaikan."
// }

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
const contactSchema = {
    type: 'object',
    required: ['message'],
    properties: {
        message: { type: 'string', minLength: 1 }
    }
};

pm.test("[Schema] Struktur POST /contact sesuai kontrak (hanya 'message')", () => {
    const valid = ajv.validate(contactSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Pesan konfirmasi tidak kosong", () => {
    pm.expect(res.message).to.be.a('string').and.not.empty;
});

// ── Negatif: POST tanpa email → 422 ──────────────────────────────────────────
// pm.test("[HTTP] Status 422 Unprocessable", () => { pm.response.to.have.status(422); });
// pm.test("[422] errors.email ada", () => { pm.expect(pm.response.json().errors).to.have.property('email'); });

// ── Negatif: POST dengan email format salah → 422 ────────────────────────────
// pm.test("[422] Pesan validasi email", () => {
//     const errs = pm.response.json().errors;
//     pm.expect(errs.email).to.be.an('array').and.not.empty;
// });

// ============================================================================
// REQUEST: GET /api/admin/contact — Daftar Pesan (Admin)
// Auth: Authorization: Bearer {{token_admin}}
// ============================================================================

// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] data adalah array", () => { pm.expect(pm.response.json().data).to.be.an('array'); });
// if (pm.response.json().data && pm.response.json().data.length > 0) {
//     pm.environment.set('created_contact_id', pm.response.json().data[0].id);
// }

// ============================================================================
// REQUEST: PATCH /api/admin/contact/{{created_contact_id}}/read — Mark as Read
// Auth: Authorization: Bearer {{token_admin}}
// Body: {} (empty atau tidak ada)
// ============================================================================

// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] is_read = true setelah ditandai", () => {
//     const r = pm.response.json();
//     pm.expect(r.data.is_read).to.be.true;
// });
