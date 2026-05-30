/**
 * =============================================================================
 * COLLECTION-LEVEL PRE-REQUEST SCRIPT
 * SMA Al Ghazaly — QA Automation Suite
 * =============================================================================
 * Tujuan  : Otomatisasi login & penyegaran token sebelum setiap request.
 * Lokasi  : Tab "Pre-request Script" di ROOT Collection (bukan folder/item).
 * =============================================================================
 */

// ─── Konfigurasi ─────────────────────────────────────────────────────────────
const BASE_URL        = pm.environment.get('base_url');
const SUPERADMIN_CRED = { email: pm.environment.get('superadmin_email'), password: pm.environment.get('superadmin_password') };
const ADMIN_CRED      = { email: pm.environment.get('admin_email'),      password: pm.environment.get('admin_password') };

// Token dianggap "masih valid" jika diambil dalam 55 menit terakhir (Sanctum default 60 menit)
const TOKEN_TTL_MS = 55 * 60 * 1000;

/**
 * Login ke API dan simpan token ke environment.
 * @param {string} role        - 'superadmin' atau 'admin'
 * @param {object} credentials - { email, password }
 * @param {function} callback  - dipanggil setelah login selesai
 */
function loginAndStoreToken(role, credentials, callback) {
    const tokenKey     = `token_${role}`;
    const tokenTimeKey = `token_${role}_fetched_at`;

    const storedToken   = pm.environment.get(tokenKey);
    const fetchedAt     = parseInt(pm.environment.get(tokenTimeKey) || '0', 10);
    const isTokenFresh  = storedToken && (Date.now() - fetchedAt < TOKEN_TTL_MS);

    if (isTokenFresh) {
        console.log(`[Auth] Token ${role} masih valid — lewati login.`);
        if (callback) callback();
        return;
    }

    console.log(`[Auth] Token ${role} expired/tidak ada — memulai login...`);

    pm.sendRequest(
        {
            url    : `${BASE_URL}/login`,
            method : 'POST',
            header : { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body   : { mode: 'raw', raw: JSON.stringify(credentials) }
        },
        function (err, res) {
            if (err) {
                console.error(`[Auth] Login ${role} GAGAL (network error):`, err);
                if (callback) callback();
                return;
            }

            if (res.code !== 200) {
                console.error(`[Auth] Login ${role} GAGAL — HTTP ${res.code}:`, res.text());
                if (callback) callback();
                return;
            }

            const body = res.json();
            if (!body.token) {
                console.error(`[Auth] Login ${role} GAGAL — respons tidak mengandung token:`, JSON.stringify(body));
                if (callback) callback();
                return;
            }

            pm.environment.set(tokenKey, body.token);
            pm.environment.set(tokenTimeKey, Date.now().toString());
            console.log(`[Auth] Token ${role} berhasil diperbarui.`);

            if (callback) callback();
        }
    );
}

// ─── Eksekusi Login Berurutan (superadmin → admin) ───────────────────────────
loginAndStoreToken('superadmin', SUPERADMIN_CRED, function () {
    loginAndStoreToken('admin', ADMIN_CRED, function () {
        console.log('[Auth] Semua token siap. Request dapat dilanjutkan.');
    });
});
