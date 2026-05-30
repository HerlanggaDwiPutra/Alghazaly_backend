// ============================================================================
// FOLDER: 10 - PPDB (Penerimaan Peserta Didik Baru)
// REQUEST: POST /api/registrations — Submit Pendaftaran (Public)
// Auth: Tidak perlu
// ============================================================================

// PRE-REQUEST SCRIPT:
// pm.collectionVariables.set('dummyStudentName', 'Calon Siswa QA ' + pm.variables.replaceIn('{{$randomLastName}}'));
// pm.collectionVariables.set('dummyPhone', '08' + Math.floor(Math.random() * 9000000000 + 1000000000).toString());
// pm.collectionVariables.set('dummyParentPhone', '08' + Math.floor(Math.random() * 9000000000 + 1000000000).toString());

// Body (JSON):
// {
//   "full_name":       "{{dummyStudentName}}",
//   "birth_date":      "2009-08-17",
//   "birth_place":     "Bandung",
//   "gender":          "L",
//   "address":         "Jl. QA Test No. 1, Bandung",
//   "phone":           "{{dummyPhone}}",
//   "parent_name":     "Orang Tua QA",
//   "parent_phone":    "{{dummyParentPhone}}",
//   "previous_school": "SMP QA Test",
//   "academic_year":   "2026/2027"
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
const registrationSchema = {
    type: 'object',
    required: ['message', 'data'],
    properties: {
        message: { type: 'string' },
        data: {
            type: 'object',
            required: ['registration_number', 'status', 'full_name'],
            properties: {
                registration_number: { type: 'string', pattern: '^PPDB-\\d{4}-\\d{4}$' },
                status:              { type: 'string', enum: ['pending'] },
                full_name:           { type: 'string' }
            }
        }
    }
};

pm.test("[Schema] Struktur POST /registrations sesuai kontrak", () => {
    const valid = ajv.validate(registrationSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] Status pendaftaran awal adalah 'pending'", () => {
    pm.expect(res.data.status).to.equal('pending');
});

pm.test("[Bisnis] Nomor pendaftaran mengikuti format PPDB-YYYY-NNNN", () => {
    pm.expect(res.data.registration_number).to.match(/^PPDB-\d{4}-\d{4}$/);
});

pm.test("[Bisnis] Pesan berisi 'berhasil' atau 'success'", () => {
    pm.expect(res.message.toLowerCase()).to.match(/berhasil|success/);
});

// ── 5. Simpan Nomor Pendaftaran ───────────────────────────────────────────────
pm.environment.set('registration_number', res.data.registration_number);
console.log('[ENV] registration_number =', res.data.registration_number);
