# API Reference

Base URL: `/api`

## Authentication

Protected endpoints require a Sanctum bearer token:

```http
Authorization: Bearer <token>
```

| Access | Requirement |
|---|---|
| Public | No token required. |
| Authenticated | Valid Sanctum bearer token. |
| Admin | Authenticated user with the `admin` or `super_admin` role. |

## Documentation

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/docs` | Public | Scalar API documentation UI. |
| GET | `/docs/openapi` | Public | OpenAPI JSON specification. |

## Authentication

| Method | Endpoint | Access | Description |
|---|---|---|---|
| POST | `/v1/auth/register` | Public | Register a `pelapor` account. |
| POST | `/v1/auth/login` | Public | Authenticate and issue a Sanctum token. |
| GET | `/v1/auth/me` | Authenticated | Return the current user and admin profile. |
| POST | `/v1/auth/logout` | Authenticated | Revoke the current access token. |

### Register

```json
{
  "username": "jane",
  "email": "jane@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

`username` must be unique and no longer than 50 characters. `email` must be unique and no longer than 100 characters. `password` must contain at least 8 characters and match `password_confirmation`.

### Login

```json
{
  "email": "jane@example.com",
  "password": "password"
}
```

A successful login returns `success`, `message`, and `data.user` with `id`, `username`, `email`, and `role`, plus `data.token`.

## Regions and master data

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/provinces` | Public | Return provinces as a code-to-name object. |
| GET | `/cities/{province_code}` | Public | Return cities in a province as a code-to-name object. |
| GET | `/districts/{city_code}` | Public | Return districts in a city as a code-to-name object. |
| GET | `/villages/{district_code}` | Public | Return villages in a district as a code-to-name object. |
| GET | `/industrial-sectors` | Public | Return industrial sectors ordered by ID. |
| GET | `/companies` | Public | Return a paginated company list; accepts optional `search`. |
| GET | `/admin/settings` | Public | Return application settings. |
| GET | `/v1/categories` | Authenticated | Return complaint categories. |
| GET | `/v1/company-sizes` | Authenticated | Return company-size groups. |
| POST | `/v1/companies` | Authenticated | Create a company. |
| POST | `/v1/admin/categories` | Admin | Create a complaint category. |
| POST | `/v1/admin/company-sizes` | Admin | Create a company-size group. |
| PUT | `/v1/admin/settings/{setting}` | Admin | Update an application setting. |

Region responses use this shape. An unknown parent region code returns an empty object.

```json
{
  "<code>": "<name>"
}
```

### Create a company

```json
{
  "nama_perusahaan": "PT Contoh",
  "sektor_industri": "Manufaktur",
  "alamat": "Jl. Contoh No. 1",
  "jumlah_naker": 20,
  "size_id": 2,
  "provinsi": "Jawa Barat",
  "kota_kab": "Bandung",
  "kecamatan": "Coblong",
  "kelurahan": "Dago",
  "no_telp": "0221234567",
  "email": "info@example.com"
}
```

All fields except `no_telp` and `email` are required. `size_id` must reference an existing company-size group.

### Create master data

`POST /v1/admin/categories` accepts:

```json
{
  "category_code": "UPAH",
  "category_name": "Upah",
  "description": "Optional description"
}
```

`POST /v1/admin/company-sizes` accepts:

```json
{
  "size_code": "SMALL",
  "size_name": "Kecil",
  "min_employees": 5,
  "max_employees": 19
}
```

`PUT /v1/admin/settings/{setting}` requires `setting_value` and accepts an optional `description`.

## Complaint categories

These REST endpoints are public.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/complaint-categories` | List complaint categories ordered by name. |
| POST | `/complaint-categories` | Create a complaint category. |
| GET | `/complaint-categories/{complaint_category}` | Return one complaint category. |
| PUT/PATCH | `/complaint-categories/{complaint_category}` | Update a complaint category. |
| DELETE | `/complaint-categories/{complaint_category}` | Delete an unused complaint category. |

Create requests require unique `category_code` and `category_name`. Update requests accept either field. Deleting a category still used by complaints returns HTTP `409`.

## Dashboard and reports

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/dashboard/complaint-summary` | Public | Return complaint counts by category. |
| GET | `/dashboard/province-summary` | Public | Return complaint counts grouped by province. |
| GET | `/dashboard/statistics` | Public | Return complaint and satisfaction statistics. |
| GET | `/v1/dashboard/overview` | Admin | Return dashboard totals, status/category/size counts, and recent complaints. |
| GET | `/v1/reports/by-category` | Admin | Return complaint counts by category. |
| GET | `/v1/reports/by-company-size` | Admin | Return complaint counts by company-size range. |
| GET | `/v1/reports/export/csv` | Admin | Download a CSV complaint report. |

`/dashboard/complaint-summary` and `/dashboard/statistics` accept optional `period=today`. Any other non-empty period value returns HTTP `422`.

`/v1/reports/by-category`, `/v1/reports/by-company-size`, and `/v1/reports/export/csv` accept optional `start_date` and `end_date`. The CSV export also accepts optional `status`.

## Complaints

| Method | Endpoint | Access | Description |
|---|---|---|---|
| POST | `/complaints` | Public | Create a complaint. |
| GET | `/complaints` | Public | Return a paginated complaint list. |
| GET | `/complaints/{complaint}` | Public | Return one complaint. |
| GET | `/complaints/{complaint}/pdf` | Public | Download `complaint-{id}.pdf`. |
| PUT/PATCH | `/complaints/{complaint}` | Public | Update the complaint description. |
| DELETE | `/complaints/{complaint}` | Public | Hide a complaint from active records. |

### Create a complaint

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

### List complaints

| Parameter | Rules |
|---|---|
| `search` | String, maximum 255 characters. Searches ticket number, description, complainant name/NIK, company name, and category name. |
| `category_id` | Existing complaint category ID. |
| `month` | Integer from 1 through 12. |
| `year` | Integer from 1900 through 2200. |
| `page` | Integer, minimum 1. |
| `per_page` | Integer from 1 through 100; defaults to 15. |

Results are ordered by `complaint_date` descending and use Laravel's pagination envelope. Updating a complaint accepts only an optional `description` with at least 20 characters. Deletion sets `is_deleted=true` and excludes the complaint from active records.

## Attachments

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/complaints/{complaint}/attachments` | Public | Return attachments newest first. |
| POST | `/complaints/{complaint}/attachments` | Public | Upload one or more attachments. |
| DELETE | `/complaints/{complaint}/attachments/{attachment}` | Public | Delete an attachment belonging to the complaint. |

Upload requests use `multipart/form-data` with a required `attachments` array containing at least one JPG, JPEG, PNG, or WEBP image. Each file is limited to 5 MB.

## Surveys

| Method | Endpoint | Access | Description |
|---|---|---|---|
| GET | `/surveys/{survey}` | Public | Return a survey with its complaint, administrator, and questions. |
| POST | `/surveys/{survey}/responses` | Public | Submit survey responses. |
| POST | `/surveys` | Admin | Create a survey and its default questions. |
| PATCH | `/surveys/{survey}/close` | Admin | Close an active survey. |

### Create a survey

```json
{
  "complaint_id": 1
}
```

### Submit survey responses

```json
{
  "responses": [
    {
      "survey_question_id": 1,
      "answer": "Nama Petugas"
    }
  ]
}
```

Each response requires an existing `survey_question_id` and a string `answer`. The question must belong to the survey. Closed surveys cannot receive responses.

## Errors

| Status | Meaning |
|---|---|
| `401` | Missing, invalid, or expired authentication token. |
| `403` | Authenticated user does not have the required role. |
| `404` | Requested model or attachment was not found. |
| `409` | Complaint category cannot be deleted because it is still used. |
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
