// ============================================================================
// FOLDER: 12 - Dashboard (Admin)
// REQUEST: GET /api/admin/dashboard — Statistik Utama
// Auth: Authorization: Bearer {{token_admin}}
// ============================================================================

const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status ────────────────────────────────────────────────────────────
pm.test("[HTTP] Status 200 OK", () => {
    pm.response.to.have.status(200);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Schema Validation ──────────────────────────────────────────────────────
const dashboardSchema = {
    type: 'object',
    required: ['data'],
    properties: {
        data: {
            type: 'object',
            required: [
                'total_posts', 'total_teachers', 'total_alumni',
                'total_testimonials', 'total_gallery', 'ppdb', 'unread_contacts'
            ],
            properties: {
                total_posts:        { type: 'number', minimum: 0 },
                total_teachers:     { type: 'number', minimum: 0 },
                total_alumni:       { type: 'number', minimum: 0 },
                total_testimonials: { type: 'number', minimum: 0 },
                total_gallery:      { type: 'number', minimum: 0 },
                ppdb: {
                    type: 'object',
                    required: ['total', 'pending', 'verified', 'accepted', 'rejected'],
                    properties: {
                        total:    { type: 'number', minimum: 0 },
                        pending:  { type: 'number', minimum: 0 },
                        verified: { type: 'number', minimum: 0 },
                        accepted: { type: 'number', minimum: 0 },
                        rejected: { type: 'number', minimum: 0 }
                    }
                },
                unread_contacts:      { type: 'number', minimum: 0 },
                recent_posts:         { type: 'array' },
                recent_registrations: { type: 'array' }
            }
        }
    }
};

pm.test("[Schema] Struktur /admin/dashboard sesuai kontrak", () => {
    const valid = ajv.validate(dashboardSchema, res);
    if (!valid) console.error('[AJV Errors]', JSON.stringify(ajv.errors));
    pm.expect(valid).to.be.true;
});

// ── 4. Business Logic ─────────────────────────────────────────────────────────
pm.test("[Bisnis] Semua counter statistik adalah angka non-negatif", () => {
    const d = res.data;
    pm.expect(d.total_posts).to.be.at.least(0);
    pm.expect(d.total_teachers).to.be.at.least(0);
    pm.expect(d.total_alumni).to.be.at.least(0);
    pm.expect(d.total_testimonials).to.be.at.least(0);
    pm.expect(d.total_gallery).to.be.at.least(0);
    pm.expect(d.unread_contacts).to.be.at.least(0);
});

pm.test("[Bisnis] PPDB: total = pending + verified + accepted + rejected", () => {
    const ppdb = res.data.ppdb;
    const calculatedTotal = ppdb.pending + ppdb.verified + ppdb.accepted + ppdb.rejected;
    pm.expect(ppdb.total).to.equal(calculatedTotal);
});

// ── 5. Keamanan: Akses tanpa token → 401 ─────────────────────────────────────
// (Buat request terpisah tanpa Authorization header)
// pm.test("[Keamanan] Status 401 tanpa token", () => { pm.response.to.have.status(401); });
