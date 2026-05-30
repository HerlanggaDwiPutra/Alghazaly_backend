// ============================================================================
// FOLDER: 03 - Kategori
// REQUEST: PUT /api/admin/categories/{{created_category_id}} — Update (Positif)
// Auth: Authorization: Bearer {{token_admin}}
// Body:
// {
//   "category_name": "Kategori QA — Updated",
//   "slug": "kategori-qa-updated"
// }
// ============================================================================

const res = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

pm.test("[HTTP] Status 200 OK", () => { pm.response.to.have.status(200); });
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => { pm.expect(pm.response.responseTime).to.be.below(THRESHOLD); });
pm.test("[Kontrak] Response memiliki 'message'", () => { pm.expect(res).to.have.property('message'); });

// ============================================================================
// REQUEST: DELETE /api/admin/categories/{{created_category_id}} (Positif)
// Auth: Authorization: Bearer {{token_admin}}
// Ekspektasi: 204 No Content (body kosong)
// ============================================================================

// pm.test("[HTTP] Status 204 No Content", () => {
//     pm.response.to.have.status(204);
// });
// pm.test("[Bisnis] Body response kosong pada 204", () => {
//     pm.expect(pm.response.text()).to.be.empty;
// });

// ============================================================================
// REQUEST: GET /api/admin/categories/{{created_category_id}} — After Delete (404)
// Auth: Authorization: Bearer {{token_admin}}
// ============================================================================

// pm.test("[HTTP] Status 404 setelah kategori dihapus", () => {
//     pm.response.to.have.status(404);
// });
// pm.test("[Bisnis] Response memiliki 'message'", () => {
//     pm.expect(pm.response.json()).to.have.property('message');
// });
