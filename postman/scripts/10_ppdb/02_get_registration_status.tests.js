// ============================================================================
// FOLDER: 10 - PPDB
// REQUEST: GET /api/registrations/{{registration_number}} — Cek Status (Public)
// Auth: Tidak perlu
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });

const statusSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'object',
            required: ['registration_number', 'full_name', 'status'],
            properties: {
                registration_number: { type: 'string' },
                full_name:           { type: 'string' },
                status:              { type: 'string', enum: ['pending', 'verified', 'accepted', 'rejected'] },
                notes:               { type: ['string', 'null'] }
            }
        }
    }
};

pm.test("[Schema] Struktur cek status pendaftaran sesuai kontrak", () => {
    const valid = ajv.validate(statusSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

pm.test("[Bisnis] Nomor pendaftaran pada response sesuai dengan yang diminta", () => {
    pm.expect(res.data.registration_number).to.equal(pm.environment.get('registration_number'));
});

pm.test("[Bisnis] Status adalah salah satu dari nilai yang valid", () => {
    pm.expect(['pending', 'verified', 'accepted', 'rejected']).to.include(res.data.status);
});

// ── Negatif: GET /api/registrations/PPDB-XXXX-XXXX (nomor tidak ada) → 404 ──
// pm.test("[HTTP] Status 404 untuk nomor pendaftaran tidak valid", () => {
//     pm.response.to.have.status(404);
// });

// ============================================================================
// REQUEST: PATCH /api/admin/registrations/{{created_registration_id}}/status
// Auth: Authorization: Bearer {{token_admin}}
// Body: { "status": "accepted", "notes": "Diterima jalur QA" }
// ============================================================================

// PRE-REQUEST: Ambil ID dari list admin setelah POST
// pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
// pm.test("[Bisnis] Status berhasil diubah menjadi accepted", () => {
//     const r = pm.response.json();
//     pm.expect(r.data.status).to.equal('accepted');
// });

// ── Negatif: PATCH dengan status invalid → 422 ───────────────────────────────
// Body: { "status": "invalid_status" }
// pm.test("[HTTP] Status 422", () => { pm.response.to.have.status(422); });
// pm.test("[422] errors.status ada", () => { pm.expect(pm.response.json().errors).to.have.property('status'); });
