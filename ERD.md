# Entity Relationship Diagram (ERD)
## Sistem PTSA KEMNAKER - Pelayanan Terpadu Satu Atap

> Dokumen ini dibangkitkan dari skema aktual pada `database/migrations` dan model
> pada `app/Models`. Nama tabel & kolom mengikuti implementasi nyata (bahasa Inggris),
> sehingga dapat berbeda dengan draf ERD konseptual awal.

---

## 1. ENTITIES & ATTRIBUTES

### **USERS** (`users`)
```
id (PK)
├── username (UNIQUE)
├── full_name (nullable)
├── email (UNIQUE, INDEX)
├── email_verified_at (nullable)
├── password (hashed)
├── role (ENUM: super_admin/admin/pelapor, default pelapor, INDEX)
├── status (ENUM: aktif/nonaktif, default aktif)
├── last_login (nullable)
├── remember_token
├── created_at
└── updated_at
```
> Catatan: RBAC memakai kolom `role` (enum) — tidak ada tabel `roles` terpisah.

### **ADMIN_PROFILES** (`admin_profiles`)
```
id (PK)
├── user_id (FK -> users.id, cascadeOnDelete)
├── nama_lengkap
├── nama_petugas
├── unit_kerja
├── golongan
├── created_at
└── updated_at
```

### **COMPLAINANTS / PELAPOR** (`complainants`)
```
id (PK)
├── nama_lengkap
├── nik (UNIQUE, 16 digit)
├── alamat
├── jenis_kelamin (ENUM: laki-laki/perempuan)
├── jabatan
├── no_telp
├── email
├── created_at
└── updated_at
```

### **COMPANIES / PERUSAHAAN** (`companies`)
```
id (PK)
├── nama_perusahaan (UNIQUE)
├── sector_id (FK -> industrial_sectors.id, nullable, restrictOnDelete, INDEX)
├── size_id (FK -> company_sizes.id, nullable, restrictOnDelete, INDEX)
├── alamat
├── provinsi
├── kota_kab
├── kecamatan
├── kelurahan
├── no_telp
├── email
├── jumlah_naker (integer, default 0)
├── npwp (UNIQUE, nullable)
├── status (ENUM: aktif/nonaktif, default aktif)
├── created_at
└── updated_at
```

### **INDUSTRIAL_SECTORS / SEKTOR_INDUSTRI** (`industrial_sectors`)
```
id (PK)
├── sector_code (UNIQUE)
├── sector_name
├── description (nullable)
├── created_at
└── updated_at
```

### **COMPANY_SIZES / SKALA_PARAMETER_PERUSAHAAN** (`company_sizes`)
```
id (PK)
├── size_code (UNIQUE)
├── size_name (Mikro, Kecil, Menengah, Besar)
├── description (nullable)
├── min_employees
├── max_employees (nullable)
├── range_start (decimal, nullable)
├── range_end (decimal, nullable)
├── created_at
└── updated_at
```

### **COMPLAINTS / PENGADUAN** (`complaints`)
```
id (PK)
├── complainant_id (FK -> complainants.id, cascadeOnDelete, INDEX)
├── company_id (FK -> companies.id, restrictOnDelete, INDEX)
├── category_id (FK -> complaint_categories.id, nullable, restrictOnDelete)
├── complaint_type_id (FK -> complaint_types.id, nullable, restrictOnDelete)
├── assigned_to_user_id (FK -> users.id, nullable, nullOnDelete, INDEX)
├── ticket_number (UNIQUE)          // format: NNN + ddmmyy (reset harian)
├── description (longText)
├── status (ENUM: draft/submitted/reviewed/approved/resolved/closed, default submitted, INDEX)
├── priority (ENUM: low/medium/high/critical, default medium)
├── is_deleted (boolean, default false)   // soft delete via global scope
├── complaint_date (datetime, INDEX)
├── deadline (datetime, nullable)         // SLA tindak lanjut
├── closed_at (datetime, nullable)
├── created_at
└── updated_at
```

### **COMPLAINT_TYPES / JENIS_PENGADUAN** (`complaint_types`)
```
id (PK)
├── type_code (UNIQUE)
├── type_name (Norma WLKP, Norma K3, Perlindungan Upah, dst.)
├── description (nullable)
├── created_at
└── updated_at
```

### **COMPLAINT_CATEGORIES / KATEGORI_PELAYANAN** (`complaint_categories`)
```
id (PK)
├── category_code (UNIQUE)
├── category_name (Wajib Lapor, Upah Kerja, Jaminan Sosial, K3, SKP, dst.)
├── description (nullable)
├── icon (nullable)
├── color (nullable)
├── status (ENUM: aktif/nonaktif, default aktif)
├── created_at
└── updated_at
```

### **ATTACHMENTS / BUKTI_PENDUKUNG** (`attachments`)
```
id (PK)
├── complaint_id (FK -> complaints.id, cascadeOnDelete, INDEX)
├── attachment_category_id (FK -> attachment_categories.id, nullable, nullOnDelete)
├── file_name
├── file_path
├── file_type (JPG, PDF, PNG, MP4, ...)
├── file_size (unsignedBigInteger)
├── mime_type (nullable)
├── description (nullable)
├── uploaded_by_user_id (FK -> users.id, nullable, nullOnDelete)
├── is_verified (boolean, default false)
├── created_at
└── updated_at
```

### **ATTACHMENT_CATEGORIES** (`attachment_categories`)
```
id (PK)
├── category_name
├── description (nullable)
├── required_count (unsignedSmallInteger, default 0)
├── created_at
└── updated_at
```

### **UPDATES (Complaint Status History)** (`updates`)
```
id (PK)
├── complaint_id (FK -> complaints.id, cascadeOnDelete)
├── old_status (nullable)
├── new_status
├── notes (nullable)
├── created_at
└── updated_at
```

### **SURVEYS / SURVEI_PELAYANAN** (`surveys`)
```
id (PK)
├── complaint_id (FK -> complaints.id, nullable, nullOnDelete, INDEX)
├── created_by_admin_id (FK -> admin_profiles.id, nullable, nullOnDelete)
├── survey_date (date, nullable)
├── nama_petugas (nullable)
├── komunikasi_petugas (ENUM: baik/cukup/kurang, nullable)
├── penjelasan_materi (ENUM: baik/cukup/kurang, nullable)
├── sarana_prasarana (ENUM: baik/cukup/kurang, nullable)
├── catatan (nullable)
├── rating_overall (tinyInteger 1-5, nullable)
├── is_anonymous (boolean, default false)
├── respondent_email (nullable)
├── respondent_phone (nullable)
├── status (ENUM: draft/submitted, default draft, INDEX)
├── closed_at (nullable)
├── created_at
└── updated_at
```

### **SURVEY_QUESTIONS / DATA_SURVEI_QUESTION** (`survey_questions`)
```
id (PK)
├── question_text
├── answer_type (ENUM: radio/multiple_choice/text_area, default radio)
├── option_a (nullable)   // Baik
├── option_b (nullable)   // Cukup
├── option_c (nullable)   // Kurang
├── is_required (boolean, default true)
├── order_number (unsignedSmallInteger, default 0, INDEX)
├── created_at
└── updated_at
```

### **SURVEY_RESPONSES (Pivot Survei ↔ Pertanyaan)** (`survey_responses`)
```
id (PK)
├── survey_id (FK -> surveys.id, cascadeOnDelete)
├── survey_question_id (FK -> survey_questions.id, cascadeOnDelete)
├── answer (text)
├── created_at
└── updated_at
UNIQUE (survey_id, survey_question_id)
```

### **NOTIFICATIONS / NOTIFIKASI** (`notifications`)
```
id (PK)
├── user_id (FK -> users.id, nullable, cascadeOnDelete)
├── complaint_id (FK -> complaints.id, nullable, cascadeOnDelete)
├── type (nullable)
├── title
├── content (text)
├── is_read (boolean, default false)
├── action_url (nullable)
├── read_at (nullable)
├── created_at
└── updated_at
INDEX (user_id, is_read)
```

### **FAQS / FAQ** (`faqs`)
```
id (PK)
├── question
├── answer (text)
├── category (nullable)
├── publication_status (ENUM: published/draft, default draft, INDEX)
├── order_number (unsignedSmallInteger, default 0, INDEX)
├── approved_by_user_id (FK -> users.id, nullable, nullOnDelete)
├── view_count (unsignedBigInteger, default 0)
├── published_at (nullable)
├── created_at
└── updated_at
```

### **DATA_VISITORS / DATA_VISITOR** (`data_visitors`)
```
id (PK)
├── ip_address (nullable)
├── page_visited (nullable)
├── visit_date (date, nullable, INDEX)
├── visit_time (time, nullable)
├── user_agent (nullable)
├── referrer (nullable)
├── session_duration (unsignedInteger, nullable)
├── device_type (nullable)
├── browser_type (nullable)
├── created_at
└── updated_at
```

### **ACTIVITY_LOGS / ACTIVITY_LOG** (`activity_logs`)
```
id (PK)
├── user_id (FK -> users.id, nullable, nullOnDelete, INDEX)
├── action_type (ENUM: create/update/delete/view/download)
├── entity_type
├── entity_id (nullable)
├── old_value (JSON, nullable)
├── new_value (JSON, nullable)
├── ip_address (nullable)
├── description (nullable)
├── created_at (INDEX)
└── updated_at
INDEX (entity_type, entity_id)
```

### **APP_SETTINGS** (`app_settings`)
```
id (PK)
├── setting_key (UNIQUE)
├── setting_value (nullable)
├── setting_type (default 'string')
├── description (nullable)
├── created_by (FK -> admin_profiles.id, nullable, nullOnDelete)
├── created_at
└── updated_at
```

> Tabel infrastruktur Laravel/wilayah (`sessions`, `password_reset_tokens`,
> `cache`, `jobs`, `personal_access_tokens`, `provinces`, `cities`, `districts`,
> `villages`) tidak digambarkan sebagai entitas domain.

---

## 2. RELATIONSHIPS (Cardinality)

```
USERS (1) ──── (1) ADMIN_PROFILES
        └─ hasOne / Memiliki profil petugas

USERS (1) ──── (M) COMPLAINTS
        └─ assigned to / Ditugaskan menangani (assigned_to_user_id)

USERS (1) ──── (M) ATTACHMENTS
        └─ uploads / Mengunggah (uploaded_by_user_id)

USERS (1) ──── (M) NOTIFICATIONS
        └─ receives / Menerima

USERS (1) ──── (M) ACTIVITY_LOGS
        └─ performs / Melakukan

USERS (1) ──── (M) FAQS
        └─ approves / Menyetujui (approved_by_user_id)

COMPLAINANTS (1) ──── (M) COMPLAINTS
        └─ files / Mengajukan

COMPANIES (1) ──── (M) COMPLAINTS
        └─ named in / Disebut dalam

INDUSTRIAL_SECTORS (1) ──── (M) COMPANIES
        └─ classifies / Mengklasifikasi

COMPANY_SIZES (1) ──── (M) COMPANIES
        └─ scales / Menentukan skala

COMPLAINT_TYPES (1) ──── (M) COMPLAINTS
        └─ typed as / Berjenis

COMPLAINT_CATEGORIES (1) ──── (M) COMPLAINTS
        └─ categorized / Dikategorikan

COMPLAINTS (1) ──── (M) ATTACHMENTS
        └─ has / Memiliki bukti

COMPLAINTS (1) ──── (M) UPDATES
        └─ status history / Riwayat status

COMPLAINTS (1) ──── (M) SURVEYS
        └─ optionally surveyed / Disurvei (opsional)

COMPLAINTS (1) ──── (M) NOTIFICATIONS
        └─ triggers / Memicu

ATTACHMENT_CATEGORIES (1) ──── (M) ATTACHMENTS
        └─ groups / Mengelompokkan

ADMIN_PROFILES (1) ──── (M) SURVEYS
        └─ initiates / Menginisiasi (created_by_admin_id)

ADMIN_PROFILES (1) ──── (M) APP_SETTINGS
        └─ creates / Membuat (created_by)

SURVEYS (M) ──── (M) SURVEY_QUESTIONS  [via SURVEY_RESPONSES]
        └─ answered in / Dijawab dalam
```

---

## 3. ER DIAGRAM (ASCII Representation)

```
┌───────────────────────────────────────────────────────────────────────────┐
│                          SISTEM PTSA KEMNAKER ERD                          │
└───────────────────────────────────────────────────────────────────────────┘

        ┌────────────────┐ 1:1  ┌──────────────────┐
        │  ADMIN_PROFILES│◄─────┤      USERS       │
        ├────────────────┤      ├──────────────────┤
        │ id (PK)        │      │ id (PK)          │
        │ user_id (FK)   │      │ username (UQ)    │
        │ nama_petugas   │      │ email (UQ)       │
        │ unit_kerja     │      │ role (enum)      │
        └───────┬────────┘      │ status (enum)    │
                │ 1:M           └──┬─────┬─────┬────┘
                │                  │ 1:M │ 1:M │ 1:M
        ┌───────▼──────┐   ┌───────▼─┐ ┌─▼──────────┐ ┌▼───────────┐
        │ APP_SETTINGS │   │ATTACH-  │ │NOTIFICATION│ │ACTIVITY_LOG│
        ├──────────────┤   │MENTS    │ ├────────────┤ ├────────────┤
        │ id (PK)      │   ├─────────┤ │ id (PK)    │ │ id (PK)    │
        │ setting_key  │   │ id (PK) │ │ user_id FK │ │ user_id FK │
        │ created_by FK│   │compl.FK │ │ compl. FK  │ │ action_type│
        └──────────────┘   │upload FK│ │ is_read    │ │ entity_*   │
                           │is_verif │ └─────┬──────┘ │ old/new JSON│
                           └────┬────┘       │        └────────────┘
                                │ M:1        │ M:1
                                │            │
   ┌──────────────┐ 1:M    ┌───▼────────────▼────────────┐   M:1 ┌───────────────┐
   │ COMPLAINANTS │───────►│         COMPLAINTS          │◄──────│ COMPLAINT_TYPES│
   ├──────────────┤        ├─────────────────────────────┤       ├───────────────┤
   │ id (PK)      │        │ id (PK)                     │       │ id (PK)       │
   │ nik (UQ)     │        │ ticket_number (UQ)          │       │ type_code (UQ)│
   │ nama_lengkap │        │ complainant_id (FK)         │       │ type_name     │
   │ jenis_kelamin│        │ company_id (FK)             │       └───────────────┘
   │ no_telp      │        │ category_id (FK)            │
   └──────────────┘        │ complaint_type_id (FK)      │   M:1 ┌────────────────────┐
                           │ assigned_to_user_id (FK)    │◄──────│COMPLAINT_CATEGORIES│
   ┌──────────────┐ 1:M    │ status (enum)               │       ├────────────────────┤
   │  COMPANIES   │───────►│ priority (enum)             │       │ id (PK)            │
   ├──────────────┤        │ complaint_date / deadline   │       │ category_code (UQ) │
   │ id (PK)      │        └───┬──────────┬──────────────┘       │ category_name      │
   │ nama_perus.UQ│            │ 1:M      │ 1:M                   │ icon / color       │
   │ sector_id FK │            │          │                      └────────────────────┘
   │ size_id FK   │       ┌────▼────┐ ┌───▼─────┐
   │ npwp (UQ)    │       │ UPDATES │ │ SURVEYS │
   └──┬────────┬──┘       ├─────────┤ ├─────────┤
      │ M:1    │ M:1      │ id (PK) │ │ id (PK) │
      │        │          │compl.FK │ │compl.FK │
┌─────▼─────┐ ┌▼──────────┐ old_st  │ │rating   │
│INDUSTRIAL │ │COMPANY_    │new_st  │ │is_anon  │
│_SECTORS   │ │SIZES      │└────────┘ └────┬────┘
├───────────┤ ├───────────┤                │ M:M (via SURVEY_RESPONSES)
│ id (PK)   │ │ id (PK)   │           ┌────▼──────────────┐
│sector_code│ │ size_code │           │ SURVEY_QUESTIONS  │
│sector_name│ │ size_name │           ├───────────────────┤
└───────────┘ │ range_*   │           │ id (PK)           │
              └───────────┘           │ question_text     │
                                      │ option_a/b/c      │
   ┌──────────────────────┐          │ order_number      │
   │ATTACHMENT_CATEGORIES  │ 1:M      └───────────────────┘
   ├──────────────────────┤────► ATTACHMENTS

   ┌──────────────┐  ┌──────────────────┐  ┌──────────────────┐
   │     FAQS     │  │  DATA_VISITORS   │  │ SURVEY_RESPONSES │
   ├──────────────┤  ├──────────────────┤  ├──────────────────┤
   │ id (PK)      │  │ id (PK)          │  │ id (PK)          │
   │ question     │  │ ip_address       │  │ survey_id (FK)   │
   │ answer       │  │ page_visited     │  │ question_id (FK) │
   │ publication_ │  │ visit_date       │  │ answer           │
   │   status     │  │ device_type      │  │ UNIQUE(s_id,q_id)│
   │ approved_by  │  │ browser_type     │  └──────────────────┘
   └──────────────┘  └──────────────────┘
```

---

## 4. KEY CONSTRAINTS

### PRIMARY KEYS (PK)
Semua entitas memakai `id` auto-increment sebagai PK: `users`, `admin_profiles`,
`complainants`, `companies`, `industrial_sectors`, `company_sizes`, `complaints`,
`complaint_types`, `complaint_categories`, `attachments`, `attachment_categories`,
`updates`, `surveys`, `survey_questions`, `survey_responses`, `notifications`,
`faqs`, `data_visitors`, `activity_logs`, `app_settings`.

### FOREIGN KEYS (FK)
- admin_profiles.user_id → users.id (cascade)
- companies.sector_id → industrial_sectors.id (restrict)
- companies.size_id → company_sizes.id (restrict)
- complaints.complainant_id → complainants.id (cascade)
- complaints.company_id → companies.id (restrict)
- complaints.category_id → complaint_categories.id (restrict, nullable)
- complaints.complaint_type_id → complaint_types.id (restrict, nullable)
- complaints.assigned_to_user_id → users.id (nullOnDelete, nullable)
- attachments.complaint_id → complaints.id (cascade)
- attachments.attachment_category_id → attachment_categories.id (nullOnDelete, nullable)
- attachments.uploaded_by_user_id → users.id (nullOnDelete, nullable)
- updates.complaint_id → complaints.id (cascade)
- surveys.complaint_id → complaints.id (nullOnDelete, nullable)
- surveys.created_by_admin_id → admin_profiles.id (nullOnDelete, nullable)
- survey_responses.survey_id → surveys.id (cascade)
- survey_responses.survey_question_id → survey_questions.id (cascade)
- notifications.user_id → users.id (cascade, nullable)
- notifications.complaint_id → complaints.id (cascade, nullable)
- faqs.approved_by_user_id → users.id (nullOnDelete, nullable)
- activity_logs.user_id → users.id (nullOnDelete, nullable)
- app_settings.created_by → admin_profiles.id (nullOnDelete, nullable)

### UNIQUE CONSTRAINTS
- users.username, users.email
- complainants.nik
- companies.nama_perusahaan, companies.npwp
- complaints.ticket_number
- industrial_sectors.sector_code
- company_sizes.size_code
- complaint_types.type_code
- complaint_categories.category_code
- app_settings.setting_key
- survey_responses (survey_id, survey_question_id) — komposit

### CHECK / ENUM CONSTRAINTS
- users.role ∈ (super_admin, admin, pelapor)
- users.status ∈ (aktif, nonaktif)
- companies.status ∈ (aktif, nonaktif)
- complaint_categories.status ∈ (aktif, nonaktif)
- complaints.status ∈ (draft, submitted, reviewed, approved, resolved, closed)
- complaints.priority ∈ (low, medium, high, critical)
- surveys.(komunikasi_petugas, penjelasan_materi, sarana_prasarana) ∈ (baik, cukup, kurang)
- surveys.status ∈ (draft, submitted)
- survey_questions.answer_type ∈ (radio, multiple_choice, text_area)
- faqs.publication_status ∈ (published, draft)
- activity_logs.action_type ∈ (create, update, delete, view, download)

---

## 5. INDEXES (Optimasi Query)

```sql
-- users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role  ON users(role);

-- companies
CREATE INDEX idx_companies_sector ON companies(sector_id);
CREATE INDEX idx_companies_size   ON companies(size_id);

-- complaints
CREATE INDEX idx_complaints_complainant ON complaints(complainant_id);
CREATE INDEX idx_complaints_company     ON complaints(company_id);
CREATE INDEX idx_complaints_date        ON complaints(complaint_date);
CREATE INDEX idx_complaints_status      ON complaints(status);
CREATE INDEX idx_complaints_assigned    ON complaints(assigned_to_user_id);

-- attachments
CREATE INDEX idx_attachments_complaint ON attachments(complaint_id);

-- surveys
CREATE INDEX idx_surveys_complaint ON surveys(complaint_id);
CREATE INDEX idx_surveys_status    ON surveys(status);

-- survey_questions
CREATE INDEX idx_survey_questions_order ON survey_questions(order_number);

-- notifications
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- faqs
CREATE INDEX idx_faqs_publication ON faqs(publication_status);
CREATE INDEX idx_faqs_order       ON faqs(order_number);

-- data_visitors
CREATE INDEX idx_visitors_date ON data_visitors(visit_date);

-- activity_logs
CREATE INDEX idx_activity_user   ON activity_logs(user_id);
CREATE INDEX idx_activity_created ON activity_logs(created_at);
CREATE INDEX idx_activity_entity ON activity_logs(entity_type, entity_id);
```

---

## 6. CATATAN PENYELARASAN (Implementasi vs ERD Konseptual)

- **ROLES** tidak menjadi tabel tersendiri; peran disimpan sebagai kolom `role`
  (enum) pada `users`, dilengkapi entitas `admin_profiles` untuk data kepetugasan.
- **UPDATES** adalah tabel riwayat status aduan (audit ringan per-tiket), terpisah
  dari **activity_logs** (audit trail global lintas entitas via Observer).
- **SURVEY_RESPONSES** menjadi tabel penghubung many-to-many antara `surveys`
  dan `survey_questions` (bank pertanyaan berdiri sendiri).
- **ATTACHMENT_CATEGORIES** menambahkan klasifikasi opsional untuk bukti pendukung.
- **APP_SETTINGS** menyimpan konfigurasi sistem, dibuat oleh `admin_profiles`.
- `ticket_number` memakai format nomor urut harian + tanggal (`NNN` + `ddmmyy`).
- Pelapor bersifat tamu (guest) — entitas `complainants` bukan akun `users`.

---

**Dokumen ini mencerminkan struktur data aktual Sistem PTSA KEMNAKER berdasarkan migrasi dan model terkini.**
