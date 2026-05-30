// ============================================================================
// FOLDER: 13 - Manajemen User & Role
// REQUEST: GET /api/admin/users — RBAC SECURITY: Akses dengan token ADMIN
// Auth: Authorization: Bearer {{token_admin}}
// Ekspektasi: 403 Forbidden
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ── 1. HTTP Status 403 Forbidden ──────────────────────────────────────────────
pm.test("[RBAC] Status 403 Forbidden — role admin tidak dapat akses /admin/users", () => {
    pm.response.to.have.status(403);
});

// ── 2. Performa ───────────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 3. Keamanan ───────────────────────────────────────────────────────────────
pm.test("[Keamanan] Response tidak mengembalikan data user lain", () => {
    pm.expect(res).to.not.have.property('data');
});

pm.test("[Keamanan] Response mengandung pesan 'message'", () => {
    pm.expect(res).to.have.property('message');
});

// ============================================================================
// REQUEST: GET /api/admin/users — SECURITY: Tanpa Auth Header
// Auth: Tidak ada
// Ekspektasi: 401 Unauthorized
// ============================================================================
// pm.test("[Keamanan] Status 401 tanpa Authorization header", () => {
//     pm.response.to.have.status(401);
// });

// ============================================================================
// REQUEST: POST /api/admin/users — Buat User Baru (Superadmin)
// Auth: Authorization: Bearer {{token_superadmin}}
// Body:
// {
//   "name":     "Staff QA Test",
//   "email":    "qa-staff-{{$randomAlphaNumeric}}@alghazaly.sch.id",
//   "password": "password123",
//   "role_id":  2
// }
// ============================================================================

// pm.test("[HTTP] Status 201 Created", () => { pm.response.to.have.status(201); });
// const Ajv = require('ajv'); const ajv = new Ajv({allErrors:true});
// const createUserSchema = {
//     type: 'object', required: ['message', 'data'],
//     properties: {
//         message: { type: 'string' },
//         data: { type: 'object', required: ['id','name','email','role'],
//             properties: { id:{type:'number'}, name:{type:'string'}, email:{type:'string'}, role:{type:'string'} }
//         }
//     }
// };
// pm.test("[Schema] Struktur POST /admin/users sesuai kontrak", () => {
//     const valid = ajv.validate(createUserSchema, pm.response.json());
//     pm.expect(valid).to.be.true;
// });
// pm.environment.set('created_user_id', pm.response.json().data.id);

// ============================================================================
// REQUEST: DELETE /api/admin/users/{{created_user_id}} (Superadmin)
// pm.test("[HTTP] Status 204 No Content", () => { pm.response.to.have.status(204); });
// ============================================================================
