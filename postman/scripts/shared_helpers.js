/**
 * =============================================================================
 * SHARED TEST HELPERS / UTILITY LIBRARY
 * SMA Al Ghazaly — QA Automation Suite
 * =============================================================================
 * Tujuan : Kumpulan fungsi helper yang digunakan di seluruh skrip Tests.
 *          Salin konten ini ke bagian atas setiap tab "Tests" item Postman,
 *          ATAU tempelkan di Collection-level Tests agar diwarisi (jika Postman
 *          mendukung pewarisan).
 *
 * Cara Pakai di tab Tests item:
 *   // --- PASTE helpers di sini, lalu tulis test Anda ---
 *   assertStatus(200);
 *   assertResponseTime();
 *   assertSuccessBody(res);
 * =============================================================================
 */

/* ─── 1. AJV — JSON Schema Validator ──────────────────────────────────────── */
const Ajv = require('ajv');
const ajv = new Ajv({ allErrors: true, coerceTypes: false });

/* ─── 2. Lodash ─────────────────────────────────────────────────────────────── */
const _ = require('lodash');

/* ─── 3. CryptoJS (untuk keperluan keamanan / hash) ─────────────────────────── */
// const CryptoJS = require('crypto-js');

/* ─── Shorthand ──────────────────────────────────────────────────────────────── */
const res      = pm.response.json();
const THRESHOLD = parseInt(pm.environment.get('response_time_threshold_ms') || '500', 10);

/* ─── Helper Functions ────────────────────────────────────────────────────────── */

/**
 * Validasi HTTP Status Code.
 * @param {number} expected - Kode status yang diharapkan.
 */
function assertStatus(expected) {
    pm.test(`[HTTP] Status ${expected}`, () => {
        pm.response.to.have.status(expected);
    });
}

/**
 * Validasi response time di bawah threshold (default 500ms).
 */
function assertResponseTime() {
    pm.test(`[Performa] Response Time < ${THRESHOLD}ms`, () => {
        pm.expect(pm.response.responseTime).to.be.below(THRESHOLD);
    });
}

/**
 * Validasi format sukses standar: { message, data }.
 * @param {object} body - pm.response.json()
 */
function assertSuccessBody(body) {
    pm.test('[Kontrak] Response memiliki key "message"', () => {
        pm.expect(body).to.have.property('message');
        pm.expect(body.message).to.be.a('string').and.not.empty;
    });
    pm.test('[Kontrak] Response memiliki key "data"', () => {
        pm.expect(body).to.have.property('data');
    });
}

/**
 * Validasi format sukses minimal hanya "message" (tanpa "data").
 * Contoh: Logout, Contact submit.
 * @param {object} body - pm.response.json()
 */
function assertMessageOnly(body) {
    pm.test('[Kontrak] Response memiliki key "message"', () => {
        pm.expect(body).to.have.property('message');
        pm.expect(body.message).to.be.a('string').and.not.empty;
    });
}

/**
 * Validasi format error 422 Unprocessable Entity.
 * @param {object} body - pm.response.json()
 */
function assert422Body(body) {
    pm.test('[422] Response memiliki key "message"', () => {
        pm.expect(body).to.have.property('message');
    });
    pm.test('[422] Response memiliki key "errors" berupa object', () => {
        pm.expect(body).to.have.property('errors');
        pm.expect(body.errors).to.be.an('object');
    });
    pm.test('[422] Setiap field di "errors" berisi array pesan', () => {
        const fields = Object.keys(body.errors);
        pm.expect(fields.length).to.be.above(0, 'errors harus memiliki minimal 1 field');
        fields.forEach(field => {
            pm.expect(body.errors[field]).to.be.an('array').and.not.empty;
        });
    });
}

/**
 * Validasi format pagination meta.
 * @param {object} body - pm.response.json()
 */
function assertPagination(body) {
    pm.test('[Pagination] Response memiliki key "meta"', () => {
        pm.expect(body).to.have.property('meta');
    });
    pm.test('[Pagination] Meta memiliki field current_page, last_page, per_page, total', () => {
        const meta = body.meta;
        pm.expect(meta).to.have.property('current_page').that.is.a('number');
        pm.expect(meta).to.have.property('last_page').that.is.a('number');
        pm.expect(meta).to.have.property('per_page').that.is.a('number');
        pm.expect(meta).to.have.property('total').that.is.a('number');
    });
    pm.test('[Pagination] current_page >= 1', () => {
        pm.expect(body.meta.current_page).to.be.at.least(1);
    });
    pm.test('[Pagination] last_page >= current_page', () => {
        pm.expect(body.meta.last_page).to.be.at.least(body.meta.current_page);
    });
    pm.test('[Pagination] per_page > 0', () => {
        pm.expect(body.meta.per_page).to.be.above(0);
    });
}

/**
 * Validasi JSON Schema menggunakan AJV.
 * @param {object} schema - JSON Schema object.
 * @param {object} data   - Data yang akan divalidasi.
 * @param {string} label  - Nama test untuk ditampilkan.
 */
function assertSchema(schema, data, label) {
    pm.test(`[Schema] ${label || 'Struktur JSON sesuai kontrak'}`, () => {
        const valid = ajv.validate(schema, data);
        if (!valid) {
            const errorMessages = ajv.errors.map(e => `${e.instancePath} ${e.message}`).join('; ');
            pm.expect.fail(`Schema validation failed: ${errorMessages}`);
        }
        pm.expect(valid).to.be.true;
    });
}

/**
 * Simpan nilai ke environment variable.
 * @param {string} key   - Nama variabel environment.
 * @param {*}      value - Nilai yang akan disimpan.
 */
function saveToEnv(key, value) {
    if (value !== undefined && value !== null) {
        pm.environment.set(key, String(value));
        console.log(`[ENV] ${key} = ${value}`);
    } else {
        console.warn(`[ENV] Nilai untuk "${key}" adalah null/undefined — tidak disimpan.`);
    }
}

/* ─── JSON Schemas ────────────────────────────────────────────────────────────── */

const SCHEMAS = {

    /** Login */
    login: {
        type: 'object',
        required: ['token', 'user'],
        properties: {
            token: { type: 'string' },
            user: {
                type: 'object',
                required: ['id', 'name', 'email', 'role'],
                properties: {
                    id:    { type: 'number' },
                    name:  { type: 'string' },
                    email: { type: 'string', format: 'email' },
                    role:  { type: 'string' }
                }
            }
        }
    },

    /** /api/me */
    me: {
        type: 'object',
        required: ['data'],
        properties: {
            data: {
                type: 'object',
                required: ['id', 'name', 'email', 'role'],
                properties: {
                    id:    { type: 'number' },
                    name:  { type: 'string' },
                    email: { type: 'string' },
                    role:  { type: 'string' }
                }
            }
        }
    },

    /** Settings item */
    settingsItem: {
        type: 'object',
        required: ['key', 'value'],
        properties: {
            key:   { type: 'string' },
            value: {}
        }
    },

    /** Category item */
    categoryItem: {
        type: 'object',
        required: ['id', 'name', 'slug'],
        properties: {
            id:        { type: 'number' },
            name:      { type: 'string' },
            slug:      { type: 'string' },
            parent_id: {}
        }
    },

    /** Post/berita item */
    postItem: {
        type: 'object',
        required: ['id', 'title', 'slug', 'is_published'],
        properties: {
            id:           { type: 'number' },
            title:        { type: 'string' },
            slug:         { type: 'string' },
            content:      { type: ['string', 'null'] },
            thumbnail:    { type: ['string', 'null'] },
            is_published: { type: 'boolean' },
            published_at: { type: ['string', 'null'] }
        }
    },

    /** Page/halaman statis item */
    pageItem: {
        type: 'object',
        required: ['id', 'title', 'slug', 'is_published'],
        properties: {
            id:           { type: 'number' },
            title:        { type: 'string' },
            slug:         { type: 'string' },
            content:      { type: ['string', 'null'] },
            is_published: { type: 'boolean' },
            order:        { type: ['number', 'null'] }
        }
    },

    /** Teacher item */
    teacherItem: {
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
    },

    /** Testimonial item */
    testimonialItem: {
        type: 'object',
        required: ['id', 'name', 'content', 'rating', 'is_published'],
        properties: {
            id:           { type: 'number' },
            name:         { type: 'string' },
            role:         { type: ['string', 'null'] },
            content:      { type: 'string' },
            rating:       { type: 'number' },
            photo:        { type: ['string', 'null'] },
            is_published: { type: 'boolean' },
            order:        { type: ['number', 'null'] }
        }
    },

    /** Alumni item */
    alumniItem: {
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
    },

    /** Gallery item */
    galleryItem: {
        type: 'object',
        required: ['id', 'title', 'image', 'is_published'],
        properties: {
            id:           { type: 'number' },
            title:        { type: 'string' },
            image:        { type: ['string', 'null'] },
            description:  { type: ['string', 'null'] },
            is_published: { type: 'boolean' }
        }
    },

    /** Registration item */
    registrationItem: {
        type: 'object',
        required: ['registration_number', 'status', 'full_name'],
        properties: {
            registration_number: { type: 'string' },
            full_name:           { type: 'string' },
            status:              { type: 'string', enum: ['pending', 'verified', 'accepted', 'rejected'] },
            notes:               { type: ['string', 'null'] }
        }
    },

    /** Dashboard */
    dashboard: {
        type: 'object',
        required: ['data'],
        properties: {
            data: {
                type: 'object',
                required: ['total_posts', 'total_teachers', 'total_alumni', 'total_testimonials', 'total_gallery', 'ppdb', 'unread_contacts'],
                properties: {
                    total_posts:        { type: 'number' },
                    total_teachers:     { type: 'number' },
                    total_alumni:       { type: 'number' },
                    total_testimonials: { type: 'number' },
                    total_gallery:      { type: 'number' },
                    ppdb: {
                        type: 'object',
                        required: ['total', 'pending', 'verified', 'accepted', 'rejected'],
                        properties: {
                            total:    { type: 'number' },
                            pending:  { type: 'number' },
                            verified: { type: 'number' },
                            accepted: { type: 'number' },
                            rejected: { type: 'number' }
                        }
                    },
                    unread_contacts: { type: 'number' }
                }
            }
        }
    },

    /** User item */
    userItem: {
        type: 'object',
        required: ['id', 'name', 'email', 'role'],
        properties: {
            id:    { type: 'number' },
            name:  { type: 'string' },
            email: { type: 'string' },
            role:  { type: 'string' }
        }
    },

    /** Role item */
    roleItem: {
        type: 'object',
        required: ['id', 'name'],
        properties: {
            id:           { type: 'number' },
            name:         { type: 'string' },
            display_name: { type: ['string', 'null'] }
        }
    }
};
