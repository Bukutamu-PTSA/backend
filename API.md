# API Endpoint List

Base URL: `/api`

Protected endpoints require a Sanctum bearer token:

```text
Authorization: Bearer <sanctum-token>
```

## Public endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/provinces` | Return all provinces. |
| GET | `/cities/{province_code}` | Return cities for a province. |
| GET | `/districts/{city_code}` | Return districts for a city. |
| GET | `/villages/{district_code}` | Return villages for a district. |
| GET | `/complaint-categories` | Return complaint categories. |
| GET | `/industrial-sectors` | Return industrial sectors. |
| POST | `/complaints` | Create a complaint without authentication. Image attachments are optional. |

## Dashboard endpoints

All endpoints in this section require Sanctum authentication.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/dashboard/complaint-summary` | Return complaint counts for every category. Supports `period=today`. |
| GET | `/dashboard/statistics` | Return total complaints, completed complaints, satisfaction statistics, complaints by company size, and complaints by category. Supports `period=today`. |

## Complaint endpoints

All endpoints in this section require Sanctum authentication except `POST /complaints`.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/complaints` | Return a paginated complaint list. Supports `search`, `status`, `category_id`, `month`, `year`, `page`, and `per_page`. |
| GET | `/complaints/{complaint}` | Return one complaint with complainant, company, category, attachments, assigned admin, and status updates. |
| GET | `/complaints/{complaint}/pdf` | Download one complaint as a PDF. |
| PUT/PATCH | `/complaints/{complaint}` | Update complaint description, status, follow-up date, assigned admin, or internal notes. A status change creates a status update record. |
| DELETE | `/complaints/{complaint}` | Delete a complaint. |

## Attachment endpoints

All endpoints in this section require Sanctum authentication.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/complaints/{complaint}/attachments` | Return all attachments for a complaint. |
| POST | `/complaints/{complaint}/attachments` | Upload JPG, JPEG, PNG, or WEBP attachments, up to 5 MB per file. |
| DELETE | `/complaints/{complaint}/attachments/{attachment}` | Delete an attachment belonging to the specified complaint. |

## Request behavior

- New complaints default to status `DIPROSES` and priority `MEDIUM`.
- Valid complaint statuses are `DIPROSES` and `SELESAI`.
- Complaint list pagination defaults to 15 records per page and accepts up to 100 records per page.
- `period=today` limits dashboard counts to complaints whose `complaint_date` is today.
- The dashboard statistics endpoint returns configured company-size groups and categories, including groups with zero complaints.
- Validation failures return HTTP `422`.
- Unauthenticated protected requests return HTTP `401`.
- The PDF endpoint returns a file download instead of JSON.

## Active controller mapping

| Controller | Registered methods |
|---|---|
| `WilayahController` | `getProvinces`, `getCities`, `getDistricts`, `getVillages` |
| `ComplaintController` | `summary`, `statistics`, `index`, `store`, `show`, `pdf`, `update`, `destroy` |
| `AttachmentController` | `index`, `store`, `destroy` |
