# Current API Reference

This document describes the API routes currently registered by the application.

## Base URL

```text
http://localhost:8000/api
```

The host and port depend on the environment. The `/api` prefix is applied by Laravel.

## Authentication

Routes marked **Protected** require a valid Laravel Sanctum bearer token:

```http
Authorization: Bearer <sanctum-token>
Accept: application/json
```

The current API does not expose a login or token-creation endpoint. A token must be issued by the application's existing authentication flow.

Unauthenticated protected requests return:

```json
{
  "message": "Unauthenticated. Token tidak valid."
}
```

HTTP status: `401 Unauthorized`

## Response conventions

- Successful resource responses use a `data` property.
- Validation failures use HTTP `422 Unprocessable Entity` with an `errors` object.
- Dates are returned as `Y-m-d H:i:s`.
- Complaint statuses are `DIPROSES` or `SELESAI`.
- Complaint priorities are `LOW`, `MEDIUM`, or `HIGH`. New complaints are created with `MEDIUM` priority.
- Attachment uploads accept JPG, JPEG, PNG, and WEBP images up to 5 MB per file.

## Endpoint summary

| Method | Endpoint | Auth | Purpose |
|---|---|---|---|
| GET | `/provinces` | Public | List provinces |
| GET | `/cities/{province_code}` | Public | List cities for a province |
| GET | `/districts/{city_code}` | Public | List districts for a city |
| GET | `/villages/{district_code}` | Public | List villages for a district |
| GET | `/complaint-categories` | Public | List complaint categories |
| GET | `/industrial-sectors` | Public | List industrial sectors |
| POST | `/complaints` | Public | Create a complaint |
| GET | `/dashboard/complaint-summary` | Protected | Get complaint counts by category |
| GET | `/complaints` | Protected | List and filter complaints |
| GET | `/complaints/{complaint}` | Protected | Get one complaint |
| PUT/PATCH | `/complaints/{complaint}` | Protected | Update a complaint |
| DELETE | `/complaints/{complaint}` | Protected | Delete a complaint |
| GET | `/complaints/{complaint}/attachments` | Protected | List complaint attachments |
| POST | `/complaints/{complaint}/attachments` | Protected | Upload complaint attachments |
| DELETE | `/complaints/{complaint}/attachments/{attachment}` | Protected | Delete an attachment |

---

## 1. Geography endpoints

### List provinces

```http
GET /api/provinces
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/provinces \
  -H "Accept: application/json"
```

Example response (`200 OK`):

```json
{
  "11": "Aceh",
  "12": "Sumatera Utara",
  "32": "Jawa Barat"
}
```

The response is an object keyed by province code. Values are province names.

### List cities

```http
GET /api/cities/{province_code}
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/cities/32 \
  -H "Accept: application/json"
```

Example response (`200 OK`):

```json
{
  "3201": "Kabupaten Bogor",
  "3202": "Kabupaten Sukabumi",
  "3275": "Kota Bekasi"
}
```

### List districts

```http
GET /api/districts/{city_code}
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/districts/3275 \
  -H "Accept: application/json"
```

Example response (`200 OK`):

```json
{
  "327501": "Bantargebang",
  "327502": "Bekasi Barat",
  "327503": "Bekasi Selatan"
}
```

### List villages

```http
GET /api/villages/{district_code}
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/villages/327502 \
  -H "Accept: application/json"
```

Example response (`200 OK`):

```json
{
  "3275021001": "Kota Baru",
  "3275021002": "Bintara"
}
```

---

## 2. Master-data endpoints

### List complaint categories

```http
GET /api/complaint-categories
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/complaint-categories \
  -H "Accept: application/json"
```

Example response (`200 OK`):

```json
[
  {
    "id": 1,
    "category_code": "UPAH_KERJA",
    "category_name": "Upah Kerja"
  },
  {
    "id": 2,
    "category_code": "K3",
    "category_name": "Keselamatan Kerja"
  }
]
```

### List industrial sectors

```http
GET /api/industrial-sectors
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/industrial-sectors \
  -H "Accept: application/json"
```

Example response (`200 OK`):

```json
[
  {
    "id": 1,
    "sector_code": "AGRICULTURE",
    "sector_name": "Pertanian, Kehutanan, dan Perikanan"
  },
  {
    "id": 2,
    "sector_code": "MINING",
    "sector_name": "Pertambangan dan Penggalian"
  }
]
```

---

## 3. Complaint endpoints

### Create a complaint

**Public endpoint.**

```http
POST /api/complaints
Content-Type: application/json
Accept: application/json
```

Request body without attachments:

```json
{
  "category_id": 1,
  "description": "Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.",
  "complainant": {
    "nama_lengkap": "Budi Santoso",
    "nik": "3201010101010001",
    "alamat": "Jl. Merdeka No. 1, Bekasi",
    "jenis_kelamin": "laki-laki",
    "jabatan": "Pekerja",
    "no_telp": "081234567890",
    "email": "budi@example.test"
  },
  "company": {
    "nama_perusahaan": "PT Maju Bersama",
    "sector_id": 1,
    "alamat": "Jl. Industri No. 10, Bekasi",
    "jumlah_naker": 120,
    "provinsi": "Jawa Barat",
    "kota_kab": "Bekasi",
    "kecamatan": "Cikarang",
    "kelurahan": "Waluya",
    "no_telp": "02112345678",
    "email": "contact@example.test"
  }
}
```

Example:

```bash
curl -X POST http://localhost:8000/api/complaints \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data @complaint.json
```

To create a complaint with image attachments, send the same fields as multipart form data:

```bash
curl -X POST http://localhost:8000/api/complaints \
  -H "Accept: application/json" \
  -F "category_id=1" \
  -F "description=Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali." \
  -F "complainant[nama_lengkap]=Budi Santoso" \
  -F "complainant[nik]=3201010101010001" \
  -F "complainant[alamat]=Jl. Merdeka No. 1, Bekasi" \
  -F "complainant[jenis_kelamin]=laki-laki" \
  -F "complainant[jabatan]=Pekerja" \
  -F "complainant[no_telp]=081234567890" \
  -F "company[nama_perusahaan]=PT Maju Bersama" \
  -F "company[sector_id]=1" \
  -F "company[alamat]=Jl. Industri No. 10, Bekasi" \
  -F "company[jumlah_naker]=120" \
  -F "company[provinsi]=Jawa Barat" \
  -F "company[kota_kab]=Bekasi" \
  -F "company[kecamatan]=Cikarang" \
  -F "company[kelurahan]=Waluya" \
  -F "attachments[]=@bukti-upah.jpg"
```

Request fields:

| Field | Type | Required | Rules |
|---|---|---:|---|
| `category_id` | integer | Yes | Must exist in `complaint_categories` |
| `description` | string | Yes | Complaint description |
| `attachments` | array of images | No | JPG, JPEG, PNG, or WEBP; max 5 MB each |
| `complainant.nama_lengkap` | string | Yes | Max 255 characters |
| `complainant.nik` | string | Yes | Exactly 16 digits |
| `complainant.alamat` | string | Yes | — |
| `complainant.jenis_kelamin` | string | Yes | `laki-laki` or `perempuan` |
| `complainant.jabatan` | string | Yes | Max 255 characters |
| `complainant.no_telp` | string | Yes | Max 20 characters |
| `complainant.email` | email | No | Max 255 characters |
| `company.nama_perusahaan` | string | Yes | Max 255 characters |
| `company.sector_id` | integer | Yes | Must exist in `industrial_sectors` |
| `company.alamat` | string | Yes | — |
| `company.jumlah_naker` | integer | Yes | At least 1 |
| `company.provinsi` | string | Yes | Max 255 characters |
| `company.kota_kab` | string | Yes | Max 255 characters |
| `company.kecamatan` | string | Yes | Max 255 characters |
| `company.kelurahan` | string | Yes | Max 255 characters |
| `company.no_telp` | string | No | Max 20 characters |
| `company.email` | email | No | Max 255 characters |

The existing complainant is reused when the submitted `nik` already exists. The existing company is reused when both `nama_perusahaan` and `kota_kab` match.

Example response (`201 Created`):

```json
{
  "data": {
    "id": 1,
    "ticket_number": "TCK-66D5F1A2B3C4D",
    "description": "Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.",
    "status": "DIPROSES",
    "priority": "MEDIUM",
    "complaint_date": "2026-09-02 09:30:00",
    "follow_up_date": "2026-09-05 09:00:00",
    "attachments": [
      {
        "id": 1,
        "file_name": "bukti-upah.jpg",
        "file_type": "photo",
        "file_size": 51200,
        "mime_type": "image/jpeg",
        "url": "/storage/complaints/1/bukti-upah.jpg",
        "created_at": "2026-09-02 09:30:01"
      }
    ],
    "complainant": {
      "id": 1,
      "nama_lengkap": "Budi Santoso",
      "nik": "3201010101010001",
      "alamat": "Jl. Merdeka No. 1, Bekasi",
      "jabatan": "Pekerja",
      "jenis_kelamin": "laki-laki",
      "no_telp": "081234567890",
      "email": "budi@example.test"
    },
    "company": {
      "id": 1,
      "nama_perusahaan": "PT Maju Bersama",
      "sector_id": 1,
      "sector": {
        "id": 1,
        "sector_code": "AGRICULTURE",
        "sector_name": "Pertanian, Kehutanan, dan Perikanan"
      },
      "sektor_industri": "Pertanian, Kehutanan, dan Perikanan",
      "alamat": "Jl. Industri No. 10, Bekasi",
      "jumlah_naker": 120,
      "provinsi": "Jawa Barat",
      "kota_kab": "Bekasi",
      "kecamatan": "Cikarang",
      "kelurahan": "Waluya",
      "no_telp": "02112345678",
      "email": "contact@example.test"
    },
    "category": {
      "id": 1,
      "category_code": "UPAH_KERJA",
      "category_name": "Upah Kerja",
      "description": null
    },
    "created_at": "2026-09-02 09:30:01"
  },
  "success": true,
  "message": "Aduan berhasil dibuat"
}
```

`internal_notes`, `assigned_to`, and `updates` are not included in the create response unless their relationships/data are available. `internal_notes` is only exposed to an authenticated admin user.

### List complaints

**Protected endpoint.**

```http
GET /api/complaints
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Supported query parameters:

| Parameter | Type | Required | Rules |
|---|---|---:|---|
| `search` | string | No | Searches ticket number, description, complainant name/NIK, company name, and category name; max 255 characters |
| `status` | string | No | `DIPROSES` or `SELESAI` |
| `category_id` | integer | No | Must exist in `complaint_categories` |
| `month` | integer | No | 1–12; filters `complaint_date` month |
| `year` | integer | No | 1900–2200; filters `complaint_date` year |
| `page` | integer | No | Minimum 1 |
| `per_page` | integer | No | 1–100; defaults to 15 |

Example:

```bash
curl "http://localhost:8000/api/complaints?search=upah&status=DIPROSES&month=9&year=2026&per_page=10&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>"
```

Example response (`200 OK`):

```json
{
  "data": [
    {
      "id": 1,
      "ticket_number": "TCK-66D5F1A2B3C4D",
      "description": "Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.",
      "status": "DIPROSES",
      "priority": "MEDIUM",
      "complaint_date": "2026-09-02 09:30:00",
      "follow_up_date": null,
      "attachments": [],
      "complainant": {
        "id": 1,
        "nama_lengkap": "Budi Santoso",
        "nik": "3201010101010001",
        "alamat": "Jl. Merdeka No. 1, Bekasi",
        "jabatan": "Pekerja",
        "jenis_kelamin": "laki-laki",
        "no_telp": "081234567890",
        "email": "budi@example.test"
      },
      "company": {
        "id": 1,
        "nama_perusahaan": "PT Maju Bersama",
        "sector_id": 1,
        "sector": {
          "id": 1,
          "sector_code": "AGRICULTURE",
          "sector_name": "Pertanian, Kehutanan, dan Perikanan"
        },
        "sektor_industri": "Pertanian, Kehutanan, dan Perikanan",
        "alamat": "Jl. Industri No. 10, Bekasi",
        "jumlah_naker": 120,
        "provinsi": "Jawa Barat",
        "kota_kab": "Bekasi",
        "kecamatan": "Cikarang",
        "kelurahan": "Waluya",
        "no_telp": "02112345678",
        "email": "contact@example.test"
      },
      "category": {
        "id": 1,
        "category_code": "UPAH_KERJA",
        "category_name": "Upah Kerja",
        "description": null
      },
      "created_at": "2026-09-02 09:30:01"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/complaints?page=1",
    "last": "http://localhost:8000/api/complaints?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost:8000/api/complaints",
    "per_page": 10,
    "to": 1,
    "total": 1
  }
}
```

### Get a complaint

**Protected endpoint.**

```http
GET /api/complaints/{complaint}
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/complaints/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>"
```

Example response (`200 OK`):

```json
{
  "data": {
    "id": 1,
    "ticket_number": "TCK-66D5F1A2B3C4D",
    "description": "Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.",
    "status": "DIPROSES",
    "priority": "MEDIUM",
    "complaint_date": "2026-09-02 09:30:00",
    "follow_up_date": null,
    "attachments": [],
    "complainant": {
      "id": 1,
      "nama_lengkap": "Budi Santoso",
      "nik": "3201010101010001",
      "alamat": "Jl. Merdeka No. 1, Bekasi",
      "jabatan": "Pekerja",
      "jenis_kelamin": "laki-laki",
      "no_telp": "081234567890",
      "email": "budi@example.test"
    },
    "company": {
      "id": 1,
      "nama_perusahaan": "PT Maju Bersama",
      "sector_id": 1,
      "sector": {
        "id": 1,
        "sector_code": "AGRICULTURE",
        "sector_name": "Pertanian, Kehutanan, dan Perikanan"
      },
      "sektor_industri": "Pertanian, Kehutanan, dan Perikanan",
      "alamat": "Jl. Industri No. 10, Bekasi",
      "jumlah_naker": 120,
      "provinsi": "Jawa Barat",
      "kota_kab": "Bekasi",
      "kecamatan": "Cikarang",
      "kelurahan": "Waluya",
      "no_telp": "02112345678",
      "email": "contact@example.test"
    },
    "category": {
      "id": 1,
      "category_code": "UPAH_KERJA",
      "category_name": "Upah Kerja",
      "description": null
    },
    "updates": [],
    "created_at": "2026-09-02 09:30:01"
  }
}
```

If the authenticated user is an admin (`admin` or `super_admin` role), the response may also include `internal_notes`.

### Update a complaint

**Protected endpoint.** Both `PUT` and `PATCH` are supported.

```http
PATCH /api/complaints/{complaint}
Authorization: Bearer <sanctum-token>
Content-Type: application/json
Accept: application/json
```

Request body:

```json
{
  "status": "SELESAI",
  "notes": "Upah sudah dibayar lunas.",
  "follow_up_date": null,
  "assigned_to": 3,
  "internal_notes": "Dokumen pembayaran sudah diverifikasi.",
  "description": "Perusahaan sudah membayar seluruh upah pekerja."
}
```

All fields are optional:

| Field | Type | Rules |
|---|---|---|
| `description` | string | Minimum 20 characters |
| `status` | string | `DIPROSES` or `SELESAI` |
| `notes` | string | Nullable; saved to the status update when `status` changes |
| `follow_up_date` | date/time | Nullable |
| `assigned_to` | integer | Nullable; must exist in `admin_profiles` |
| `internal_notes` | string | Nullable |

Example:

```bash
curl -X PATCH http://localhost:8000/api/complaints/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>" \
  -H "Content-Type: application/json" \
  --data '{
    "status": "SELESAI",
    "notes": "Upah sudah dibayar lunas."
  }'
```

Changing `status` creates an entry in the complaint's `updates` collection. Updating other fields does not create a status-history entry.

Example response (`200 OK`):

```json
{
  "data": {
    "id": 1,
    "ticket_number": "TCK-66D5F1A2B3C4D",
    "description": "Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.",
    "status": "SELESAI",
    "priority": "MEDIUM",
    "complaint_date": "2026-09-02 09:30:00",
    "follow_up_date": null,
    "attachments": [],
    "complainant": {
      "id": 1,
      "nama_lengkap": "Budi Santoso",
      "nik": "3201010101010001",
      "alamat": "Jl. Merdeka No. 1, Bekasi",
      "jabatan": "Pekerja",
      "jenis_kelamin": "laki-laki",
      "no_telp": "081234567890",
      "email": "budi@example.test"
    },
    "company": {
      "id": 1,
      "nama_perusahaan": "PT Maju Bersama",
      "sector_id": 1,
      "sector": {
        "id": 1,
        "sector_code": "AGRICULTURE",
        "sector_name": "Pertanian, Kehutanan, dan Perikanan"
      },
      "sektor_industri": "Pertanian, Kehutanan, dan Perikanan",
      "alamat": "Jl. Industri No. 10, Bekasi",
      "kota_kab": "Bekasi",
      "kecamatan": "Cikarang",
      "kelurahan": "Waluya"
    },
    "category": {
      "id": 1,
      "category_code": "UPAH_KERJA",
      "category_name": "Upah Kerja",
      "description": null
    },
    "updates": [
      {
        "id": 1,
        "old_status": "DIPROSES",
        "new_status": "SELESAI",
        "notes": "Upah sudah dibayar lunas.",
        "created_at": "2026-09-02 10:00:00"
      }
    ],
    "created_at": "2026-09-02 09:30:01"
  }
}
```

### Delete a complaint

**Protected endpoint.**

```http
DELETE /api/complaints/{complaint}
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Example:

```bash
curl -X DELETE http://localhost:8000/api/complaints/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>"
```

Example response (`200 OK`):

```json
{
  "success": true,
  "message": "Aduan dihapus"
}
```

---

## 4. Dashboard endpoint

### Complaint summary by category

**Protected endpoint.**

```http
GET /api/dashboard/complaint-summary
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/dashboard/complaint-summary \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>"
```

Example response (`200 OK`):

```json
{
  "data": [
    {
      "id": 1,
      "category_code": "UPAH_KERJA",
      "category_name": "Upah Kerja",
      "count": 12
    },
    {
      "id": 2,
      "category_code": "K3",
      "category_name": "Keselamatan Kerja",
      "count": 5
    }
  ],
  "meta": {
    "total": 20,
    "uncategorized_count": 3
  }
}
```

The category list includes categories with zero complaints. `total` is the total number of complaints, while `uncategorized_count` counts complaints whose `category_id` is null.

---

## 5. Attachment endpoints

### List complaint attachments

**Protected endpoint.**

```http
GET /api/complaints/{complaint}/attachments
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Example:

```bash
curl http://localhost:8000/api/complaints/1/attachments \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>"
```

Example response (`200 OK`):

```json
{
  "data": [
    {
      "id": 1,
      "file_name": "bukti-upah.jpg",
      "file_type": "photo",
      "file_size": 51200,
      "mime_type": "image/jpeg",
      "url": "/storage/complaints/1/bukti-upah.jpg",
      "created_at": "2026-09-02 09:30:01"
    }
  ]
}
```

### Upload complaint attachments

**Protected endpoint.**

```http
POST /api/complaints/{complaint}/attachments
Authorization: Bearer <sanctum-token>
Content-Type: multipart/form-data
Accept: application/json
```

Example:

```bash
curl -X POST http://localhost:8000/api/complaints/1/attachments \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>" \
  -F "attachments[]=@bukti-upah.jpg" \
  -F "attachments[]=@surat-pernyataan.png"
```

Request fields:

| Field | Type | Required | Rules |
|---|---|---:|---|
| `attachments` | array | Yes | At least one file |
| `attachments.*` | image file | Yes | JPG, JPEG, PNG, or WEBP; max 5 MB each |

Example response (`201 Created`):

```json
{
  "data": [
    {
      "id": 1,
      "file_name": "bukti-upah.jpg",
      "file_type": "photo",
      "file_size": 51200,
      "mime_type": "image/jpeg",
      "url": "/storage/complaints/1/bukti-upah.jpg",
      "created_at": "2026-09-02 09:30:01"
    },
    {
      "id": 2,
      "file_name": "surat-pernyataan.png",
      "file_type": "photo",
      "file_size": 73400,
      "mime_type": "image/png",
      "url": "/storage/complaints/1/surat-pernyataan.png",
      "created_at": "2026-09-02 09:30:01"
    }
  ],
  "success": true,
  "message": "2 lampiran berhasil diunggah"
}
```

### Delete an attachment

**Protected endpoint.** The attachment must belong to the complaint in the URL.

```http
DELETE /api/complaints/{complaint}/attachments/{attachment}
Authorization: Bearer <sanctum-token>
Accept: application/json
```

Example:

```bash
curl -X DELETE http://localhost:8000/api/complaints/1/attachments/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <sanctum-token>"
```

Example response (`200 OK`):

```json
{
  "success": true,
  "message": "Lampiran dihapus"
}
```

If the attachment does not belong to the specified complaint, the endpoint returns `404 Not Found`.

---

## Common validation error

Invalid request data returns `422 Unprocessable Entity`.

Example request:

```bash
curl -X POST http://localhost:8000/api/complaints \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  --data '{"description":"Data tidak lengkap"}'
```

Example response:

```json
{
  "message": "The category id field is required. (and 3 more errors)",
  "errors": {
    "category_id": [
      "The category id field is required."
    ],
    "complainant.nama_lengkap": [
      "The complainant.nama lengkap field is required."
    ],
    "complainant.nik": [
      "The complainant.nik field is required."
    ],
    "company.nama_perusahaan": [
      "The company.nama perusahaan field is required."
    ]
  }
}
```

The exact validation message text depends on Laravel's locale and the fields that fail validation.

## Not currently exposed

The current route list does not include endpoints for user login, token issuance, logout, user profiles, surveys, notifications, or attachment categories.
