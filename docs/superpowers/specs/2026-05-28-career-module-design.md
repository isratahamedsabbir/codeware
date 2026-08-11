# Career Module Design

**Date:** 2026-05-28
**Approach:** Option A — Three separate Livewire admin components
**Stack:** Laravel + Livewire + Flux UI (admin), REST API (Next.js frontend)

---

## Overview

A career module with job listings and an online application system. Departments are admin-managed. Job descriptions use a simple translatable textarea. Applications are submitted via public API, stored with a private resume file, and trigger a confirmation email to the applicant. Admins manage application status via a dedicated Livewire component. No admin REST API — all management is through Livewire.

---

## Database Schema

### `departments`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | JSON | Translatable EN/BN via Spatie Translatable |
| `slug` | string unique | Auto-generated from `name.en` on save |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `jobs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `department_id` | bigint FK nullable | References `departments.id`, nullOnDelete |
| `title` | JSON | Translatable EN/BN |
| `slug` | string unique | Auto-generated from `title.en` on save |
| `description` | JSON nullable | Translatable EN/BN — long text, textarea in admin |
| `position` | string | e.g. "Senior Engineer" |
| `vacancy` | unsignedSmallInteger default 1 | Number of openings |
| `deadline` | date nullable | Application closing date |
| `location` | string nullable | e.g. "Dhaka, Bangladesh" |
| `status` | enum `draft,open,closed` default `draft` | |
| `sort_order` | unsignedSmallInteger default 0 | |
| `deleted_at` | timestamp nullable | Soft deletes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `job_applications`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `job_id` | bigint FK | References `jobs.id`, cascadeOnDelete |
| `name` | string | Applicant full name |
| `email` | string | |
| `phone` | string | |
| `resume_path` | string | Private storage path (`resumes/{job_id}/{filename}`) |
| `resume_original_name` | string | Original filename for display and download |
| `status` | enum `pending,reviewed,shortlisted,rejected` default `pending` | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Models

### `App\Models\Department`
- `HasTranslations` — translatable: `name`
- `HasFactory`
- Auto-slug from `name.en` in `booted()`
- `jobs(): HasMany`

### `App\Models\Job`
- `HasTranslations` — translatable: `title`, `description`
- `SoftDeletes`, `HasFactory`
- Auto-slug from `title.en` in `booted()`
- `department(): BelongsTo`
- `applications(): HasMany`
- `scopeOpen(Builder $query)` — where status = open (deadline is informational only; admin closes jobs manually)

### `App\Models\JobApplication`
- `HasFactory`
- `job(): BelongsTo`

---

## Livewire Admin Components

### `Admin\Departments\Index`
Follows `Admin\Categories\Index` pattern. Table: Name (EN), Name (BN), Slug, Jobs count. Modal: name_en (required), name_bn, slug.

### `Admin\Jobs\Index`
Table: Title (EN), Department badge, Status badge, Vacancy, Deadline, actions.

Modal form fields (single form, locale tab switcher for title + description):
- `title_en` (required), `title_bn`
- `slug`
- `department_id` — select from departments
- `position` — text input
- `vacancy` — number input (min 1)
- `deadline` — date input
- `location` — text input
- `status` — select: draft / open / closed
- `sort_order` — number input
- `description_en`, `description_bn` — textarea, locale-switched

### `Admin\Applications\Index`
Read-only listing — no create form. Table shows: Applicant Name, Email, Job Title, Status, Applied Date, Resume (download link), Status Action.

Admin can update status inline per row via a Livewire select. Clicking the download link hits the resume download route.

Filter by job and status.

---

## Admin Routes

Added to `routes/admin.php`:

```php
Route::get('/departments', \App\Livewire\Admin\Departments\Index::class)->name('departments');
Route::get('/jobs', \App\Livewire\Admin\Jobs\Index::class)->name('jobs');
Route::get('/applications', \App\Livewire\Admin\Applications\Index::class)->name('applications');
Route::get('/applications/{id}/resume', \App\Http\Controllers\Admin\ResumeDownloadController::class)
    ->name('applications.resume');
```

The `ResumeDownloadController` is a single-action controller (invokable) that finds the application by ID and streams the file from private storage using `response()->download()`.

---

## Admin Sidebar Addition

New **"Careers"** navlist group in `resources/views/layouts/admin.blade.php`:

```
Careers
  ├── Jobs              /admin/jobs
  ├── Applications      /admin/applications
  └── Departments       /admin/departments
```

---

## Public REST API

### Controllers
- `App\Http\Controllers\Api\V1\DepartmentController`
- `App\Http\Controllers\Api\V1\JobController`
- `App\Http\Controllers\Api\V1\ApplicationController`

### Endpoints

#### `GET /api/v1/departments`
Returns all departments ordered alphabetically. Each item: `id`, `name` (locale-resolved), `slug`, `jobs_count` (count of open jobs).

#### `GET /api/v1/jobs`
Returns paginated open jobs (status=open AND deadline not passed).

**Query params:**
- `department` — filter by department slug
- `search` — searches `title->{locale}`
- `locale` — `en` or `bn`, default `en`
- `per_page` — default 10, max 50

**Response per job:** `id`, `slug`, `title`, `position`, `vacancy`, `deadline`, `location`, `department` (id, name, slug), `created_at`

#### `GET /api/v1/jobs/{slug}`
Returns full job detail. Same fields plus `description`.

Returns 404 if job is draft, closed, or soft-deleted.

#### `POST /api/v1/applications`
Accepts `multipart/form-data`.

**Validation:**
- `job_id` — required, exists in `jobs`, job must have `status = open`
- `name` — required, string, max 255
- `email` — required, valid email, max 255
- `phone` — required, string, max 50
- `resume` — required, file, mimes: `pdf,docx`, max 5120 KB (5 MB)

**On success:**
1. Store resume to `local` disk at `resumes/{job_id}/{timestamp}_{sanitized_original_name}`
2. Create `JobApplication` record with status `pending`
3. Dispatch `App\Mail\ApplicationSubmitted` to applicant's email
4. Return `201` with `{ "message": "Application submitted successfully." }`

**On validation failure:** return `422` with validation errors.

---

## Mail

### `App\Mail\ApplicationSubmitted`

Simple Mailable sent to the applicant's email address. Contents:
- Subject: "Application Received — {job title}"
- Body: applicant name, job title, company name, message that the application has been received and will be reviewed

Uses Laravel's `markdown` mailable with a simple template at `resources/views/mail/application-submitted.blade.php`.

---

## API Routes

Added to `routes/api.php` under `prefix('v1')`:

```php
Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{slug}', [JobController::class, 'show'])->name('jobs.show');
Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
```

---

## Out of Scope

- Admin REST API for jobs/applications
- CV parsing or automated screening
- Applicant login / application tracking portal
- Rich text editor (Puck) for job descriptions
- Application deadline auto-close (jobs stay open past deadline unless manually closed)
