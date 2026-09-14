# API Reference

Base URL: `/api`

All currently registered API endpoints are public. No bearer token is required.

## Endpoints

### Regions and master data

| Method | Endpoint | Description |
|---|---|---|
| GET | `/provinces` | Return provinces as a code-to-name object, ordered by name. |
| GET | `/cities/{province_code}` | Return cities in a province as a code-to-name object. |
| GET | `/districts/{city_code}` | Return districts in a city as a code-to-name object. |
| GET | `/villages/{district_code}` | Return villages in a district as a code-to-name object. |
| GET | `/complaint-categories` | Return complaint categories ordered by name. |
| GET | `/industrial-sectors` | Return industrial sectors ordered by ID. |

Region responses use this shape:

```json
{
  "<code>": "<name>"
}
```

An unknown parent region code returns an empty object.

Complaint categories and industrial sectors are returned as bare arrays:

```json
[
  {
    "id": 1,
    "category_code": "CODE",
    "category_name": "Category name"
  }
]
```

```json
[
  {
    "id": 1,
    "sector_code": "CODE",
    "sector_name": "Sector name"
  }
]
```

### Dashboard

| Method | Endpoint | Description |
|---|---|---|
| GET | `/dashboard/complaint-summary` | Return complaint counts for every configured category. |
| GET | `/dashboard/statistics` | Return totals, satisfaction statistics, and counts by company size and category. |

Both endpoints accept the optional query parameter `period=today`. Any other non-empty value returns HTTP `422`. Without it, all active complaints are included.

#### Complaint summary response

```json
{
  "data": [
    {
      "id": 1,
      "category_code": "CODE",
      "category_name": "Category name",
      "count": 10
    }
  ],
  "meta": {
    "period": "all",
    "total": 10,
    "uncategorized_count": 0
  }
}
```

#### Statistics response

```json
{
  "data": {
    "total_complaints": 10,
    "satisfaction_index": 4.25,
    "satisfaction_scale": 5,
    "satisfaction_responses": 8,
    "by_company_size": [
      {
        "size_code": "SMALL",
        "size_name": "Small",
        "count": 3
      }
    ],
    "by_category": [
      {
        "id": 1,
        "category_code": "CODE",
        "category_name": "Category name",
        "count": 10
      }
    ]
  },
  "meta": {
    "period": "all",
    "uncategorized_count": 0
  }
}
```

Configured categories and company-size groups are included even when their count is zero. `satisfaction_index` is `null` when no valid numeric survey answers from 1 through 5 exist.

### Complaints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/complaints` | Create a complaint. |
| GET | `/complaints` | Return a paginated complaint list. |
| GET | `/complaints/{complaint}` | Return one complaint. |
| GET | `/complaints/{complaint}/pdf` | Download `complaint-{id}.pdf`. |
| PUT/PATCH | `/complaints/{complaint}` | Update the complaint description. |
| DELETE | `/complaints/{complaint}` | Hide a complaint from active records. |

#### Create a complaint

Use `application/json`, or `multipart/form-data` when uploading attachments.

| Field | Required | Rules |
|---|---|---|
| `category_id` | Yes | Existing complaint category ID. |
| `description` | Yes | String. |
| `attachments` | No | Array of images. |
| `attachments.*` | No | JPG, JPEG, PNG, or WEBP; maximum 5 MB per file. |
| `complainant.nama_lengkap` | Yes | String, maximum 255 characters. |
| `complainant.nik` | Yes | Exactly 16 digits. |
| `complainant.alamat` | Yes | String. |
| `complainant.jenis_kelamin` | Yes | `laki-laki` or `perempuan`. |
| `complainant.jabatan` | Yes | String, maximum 255 characters. |
| `complainant.no_telp` | Yes | String, maximum 20 characters. |
| `complainant.email` | Yes | Valid email, maximum 255 characters. |
| `company.nama_perusahaan` | Yes | String, maximum 255 characters. |
| `company.sector_id` | Yes | Existing industrial sector ID. |
| `company.alamat` | Yes | String. |
| `company.jumlah_naker` | Yes | Integer, minimum 1. |
| `company.provinsi` | Yes | String, maximum 255 characters. |
| `company.kota_kab` | Yes | String, maximum 255 characters. |
| `company.kecamatan` | Yes | String, maximum 255 characters. |
| `company.kelurahan` | Yes | String, maximum 255 characters. |
| `company.no_telp` | No | String, maximum 20 characters. |
| `company.email` | No | Valid email, maximum 255 characters. |

The server generates `ticket_number` and `complaint_date`. Company size is derived from `company.jumlah_naker`; clients do not submit `size_id`. An existing complainant is reused by NIK. A company with the same name and city is updated with the submitted company data.

A successful creation returns HTTP `201`:

```json
{
  "data": {},
  "success": true,
  "message": "Aduan berhasil dibuat"
}
```

`data` follows the complaint resource shape below.

#### List complaints

Supported query parameters:

| Parameter | Rules |
|---|---|
| `search` | String, maximum 255 characters. Searches ticket number, description, complainant name/NIK, company name, and category name. |
| `category_id` | Existing complaint category ID. |
| `month` | Integer from 1 through 12. |
| `year` | Integer from 1900 through 2200. |
| `page` | Integer, minimum 1. |
| `per_page` | Integer from 1 through 100; defaults to 15. |

Results are ordered by `complaint_date` descending and use Laravel's pagination envelope:

```json
{
  "data": [],
  "links": {
    "first": null,
    "last": null,
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [],
    "path": "...",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

#### Complaint resource

Single-complaint endpoints wrap this object in `data`. The list endpoint returns these objects in `data`.

```json
{
  "id": 1,
  "ticket_number": "TCK-...",
  "description": "Complaint description",
  "attachments": [],
  "complainant": {
    "id": 1,
    "nama_lengkap": "Full name",
    "nik": "1234567890123456",
    "alamat": "Address",
    "jabatan": "Position",
    "jenis_kelamin": "laki-laki",
    "no_telp": "Phone number",
    "email": "name@example.com"
  },
  "company": {
    "id": 1,
    "nama_perusahaan": "Company name",
    "sector_id": 1,
    "size_id": 1,
    "size": {
      "id": 1,
      "size_code": "SMALL",
      "size_name": "Small"
    },
    "sector": {
      "id": 1,
      "sector_code": "CODE",
      "sector_name": "Sector name"
    },
    "sektor_industri": "Sector name",
    "alamat": "Address",
    "jumlah_naker": 10,
    "provinsi": "Province",
    "kota_kab": "City or regency",
    "kecamatan": "District",
    "kelurahan": "Village",
    "no_telp": "Phone number",
    "email": "company@example.com"
  },
  "category": {
    "id": 1,
    "category_code": "CODE",
    "category_name": "Category name",
    "description": null
  },
  "created_at": "2026-09-04 08:07:22"
}
```

#### Update a complaint

Only `description` is accepted. It is optional, but when present it must be a string with at least 20 characters.

```json
{
  "description": "Updated complaint description"
}
```

A successful update returns HTTP `200` with the complaint resource in `data`.

#### Delete a complaint

Deletion sets `is_deleted=true`. The complaint is then excluded from lists, dashboards, relations, and route-model binding. Its attachment records and files are not deleted.

A successful deletion returns HTTP `200`:

```json
{
  "success": true,
  "message": "Aduan dihapus"
}
```

#### Download a complaint PDF

A successful response has `Content-Type: application/pdf` and `Content-Disposition: attachment; filename="complaint-{id}.pdf"`.

### Attachments

| Method | Endpoint | Description |
|---|---|---|
| GET | `/complaints/{complaint}/attachments` | Return attachments newest first. |
| POST | `/complaints/{complaint}/attachments` | Upload one or more attachments. |
| DELETE | `/complaints/{complaint}/attachments/{attachment}` | Delete an attachment belonging to the complaint. |

The upload request uses `multipart/form-data` with a required `attachments` array containing at least one JPG, JPEG, PNG, or WEBP image. Each file is limited to 5 MB.

Attachment responses use this resource shape:

```json
{
  "id": 1,
  "file_name": "evidence.jpg",
  "file_type": "photo",
  "file_size": 12345,
  "mime_type": "image/jpeg",
  "url": "/storage/complaints/1/evidence.jpg",
  "created_at": "2026-09-04 08:07:22"
}
```

The list response wraps an array in `data`. A successful upload returns HTTP `201` with `data`, `success: true`, and a message containing the uploaded file count.

Deleting an attachment removes both its database record and stored file. It returns HTTP `200`:

```json
{
  "success": true,
  "message": "Lampiran dihapus"
}
```

An attachment that does not belong to the complaint returns HTTP `404`.

## Errors

| Status | Meaning |
|---|---|
| `404` | Complaint or attachment not found, including hidden complaints. |
| `422` | Request validation failed. |

Validation errors use Laravel's standard JSON shape:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": [
      "Validation message"
    ]
  }
}
```
