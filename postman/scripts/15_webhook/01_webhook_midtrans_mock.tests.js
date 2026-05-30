// ============================================================================
// FOLDER: 15 - Webhook (Internal/Mock)
// REQUEST: POST /api/webhook/midtrans — Simulasi Payload Midtrans
// Auth: Tidak pakai Bearer Token (internal webhook)
// Content-Type: application/json
// ============================================================================
// PERATURAN: DILARANG stress test atau flood endpoint ini.
// Pengujian ini HANYA menggunakan mock payload untuk memeriksa validasi signature.
// ============================================================================

const CryptoJS = require('crypto-js');
const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true });
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

// ─────────────────────────────────────────────────────────────────────────────
// PRE-REQUEST SCRIPT (tempel di tab Pre-request):
// ─────────────────────────────────────────────────────────────────────────────
/*
// Simulasi kalkulasi SHA512 Signature Key Midtrans
// Format: orderId + statusCode + grossAmount + serverKey
const orderId     = 'ORDER-QA-' + Date.now();
const statusCode  = '200';
const grossAmount = '150000.00';
const serverKey   = 'SB-Mid-server-MOCK-KEY'; // Mock server key — BUKAN key nyata

const signatureRaw  = orderId + statusCode + grossAmount + serverKey;
const signatureHash = CryptoJS.SHA512(signatureRaw).toString();

pm.collectionVariables.set('mockOrderId', orderId);
pm.collectionVariables.set('mockSignatureKey', signatureHash);
pm.collectionVariables.set('mockGrossAmount', grossAmount);

console.log('[WEBHOOK] Mock Order ID:', orderId);
console.log('[WEBHOOK] Mock Signature (SHA512):', signatureHash);
*/

// ─────────────────────────────────────────────────────────────────────────────
// Body (JSON) — Payload simulasi Midtrans:
// {
//   "transaction_status": "settlement",
//   "order_id":           "{{mockOrderId}}",
//   "status_code":        "200",
//   "gross_amount":       "{{mockGrossAmount}}",
//   "signature_key":      "{{mockSignatureKey}}",
//   "payment_type":       "bank_transfer",
//   "fraud_status":       "accept"
// }
// ─────────────────────────────────────────────────────────────────────────────

const res = pm.response.json ? pm.response.json() : null;

// ── 1. Response Time ──────────────────────────────────────────────────────────
pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
    pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
});

// ── 2. Status Code (Server Key mock tidak cocok → validasi gagal) ────────────
// Dengan server key yang salah, server MUNGKIN mengembalikan 400/403
pm.test("[WEBHOOK] Status Code adalah 200, 400, atau 403 (bukan 500)", () => {
    pm.expect(pm.response.code).to.be.oneOf([200, 400, 403, 422]);
});

// ── 3. Server tidak crash (tidak ada 500 Internal Error) ─────────────────────
pm.test("[WEBHOOK] Server tidak mengalami error 500", () => {
    pm.expect(pm.response.code).to.not.equal(500);
});

// ── 4. Jika 400/403: Signature validation gagal dengan pesan yang jelas ──────
if (pm.response.code === 400 || pm.response.code === 403 || pm.response.code === 422) {
    pm.test("[WEBHOOK][Keamanan] Response mengandung pesan error saat signature tidak valid", () => {
        if (res) {
            pm.expect(res).to.have.property('message');
        }
    });
}

// ── 5. Jika 200: Response mengandung konfirmasi penerimaan ───────────────────
if (pm.response.code === 200) {
    pm.test("[WEBHOOK] Response 200 mengandung konfirmasi penerimaan", () => {
        if (res) {
            pm.expect(res).to.have.property('message');
        }
    });
}

console.log(`[WEBHOOK] Status Code: ${pm.response.code}`);
console.log(`[WEBHOOK] Response Body: ${pm.response.text().substring(0, 200)}`);
