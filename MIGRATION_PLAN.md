## Plan: Academico Backpack → Filament Migration

This is a large-scale rewrite of an academic management system (language school) from Laravel + Backpack CRUD to Laravel 12 + Filament v5. The old project has ~48 Eloquent models, ~28 CRUD controllers, ~22 standard controllers, 21 events, 18 listeners, 14 services, 116 Blade views, and 41 Vue components. The new project is a greenfield Laravel 12 + Filament v5 app with nothing beyond the scaffold.

I've categorized the work into what transfers easily, what has a native Filament equivalent, and what requires significant manual work.

---

### What Transfers Directly (copy/adapt)

These are framework-agnostic and can be brought over with minimal changes:

1. **All 48 Eloquent Models** — copy from app/Models/ to the new project. Remove the `CrudTrait` (Backpack-specific), keep everything else (relationships, scopes, accessors, casts, events). The `HasTranslations` trait should switch from Backpack's version to `spatie/laravel-translatable` (same API). Models using `HasRoles` continue with `spatie/laravel-permission`.

2. **All 112 Migrations** — copy from database/migrations/ directly. The database schema is independent of the admin framework.

3. **All 45 Factories** — copy from database/factories/ directly.

4. **All 3 Seeders** — copy from database/seeders/ directly.

5. **All 21 Events** — copy from app/Events/ directly.

6. **All 18 Listeners** — copy from app/Listeners/ directly.

7. **Both Jobs** (`BuildInvoicesZip`, `WatchAttendance`) — copy from app/Jobs/ directly.

8. **All 7 Mailables** — copy from app/Mail/ and their email Blade templates from resources/views/emails/.

9. **All 14 Services** — copy from app/Services/. These are pure business logic classes (invoicing, certificates, reports, LMS, mailing).

10. **All 5 Interfaces** — copy from app/Interfaces/ (or Interfaces).

11. **Custom Traits** (`PriceTrait`, `ValueTrait`, `PeriodSelection`, `UsernameTrait`, `HandlesAttendance`) — copy from app/Traits/. Remove `FiltersSearchableLevels` (Backpack-specific filter).

12. **All 9 Custom Config Files** — copy from config/ (`academico.php`, `registration.php`, `lms.php`, `invoicing.php`, `invoices.php`, `certificates-generation.php`, `enrollment-sheet.php`, `mailing-system.php`, `settings.php`).

13. **Lang Files** — copy lang/ directory (en.json, es.json, fr.json and subdirectories).

14. **`Imports/CourseSkillsImport`** — copy directly (Maatwebsite/Excel).

---

### What Has Native Filament Equivalents (rewrite required, but patterned)

Each Backpack CRUD controller maps to a **Filament Resource** with predictable translations:

| Backpack Concept | Filament v5 Equivalent |
|---|---|
| `CrudController` + `ListOperation` | `Resource` + `ListRecords` page with `table()` method |
| Columns (text, relationship, etc.) | `Tables\Columns\TextColumn`, `RelationshipColumn`, etc. |
| Fields (text, select, select2, relationship, etc.) | `Forms\Components\TextInput`, `Select`, `Repeater`, etc. |
| Filters (select, date_range, etc.) | `Tables\Filters\SelectFilter`, `DateFilter`, etc. |
| `CreateOperation` / `UpdateOperation` | `CreateRecord` / `EditRecord` pages with `form()` method |
| `ShowOperation` | `ViewRecord` page |
| `DeleteOperation` | Built-in delete action |
| `InlineCreateOperation` | `Select::make()->createOptionForm()` |
| Custom buttons | `Tables\Actions\Action` or `BulkAction` |
| Export buttons | Filament Export action or plugin |
| `FetchOperation` (AJAX selects) | Native Filament `Select::make()->searchable()->getSearchResultsUsing()` |
| Backpack sidebar | Filament navigation groups |
| `SoftDeletes` toggle button | `Tables\Filters\TrashedFilter` + restore/force-delete actions |

**28 CRUD Controllers → 28 Filament Resources** needed:

| Priority | Resource | Old Controller | Complexity |
|---|---|---|---|
| High | `StudentResource` | `StudentCrudController` (511 lines) | **Complex** — custom show, filters, photo, export |
| High | `CourseResource` | `CourseCrudController` (757 lines) | **Complex** — custom operations, repeatable children, schedule, multiple price fields |
| High | `EnrollmentResource` | `EnrollmentCrudController` (482 lines) | **Complex** — two modes (global/course), many computed columns, custom buttons |
| High | `InvoiceResource` | `InvoiceCrudController` (282 lines) | **Complex** — repeatable relationships, PDF generation, payments |
| Medium | `EventResource` | `EventCrudController` (214 lines) | Medium — date filters, teacher validation |
| Medium | `TeacherResource` | `TeacherCrudController` (220 lines) | Medium — creates User+Teacher, soft deletes |
| Medium | `ResultResource` | `ResultCrudController` | Medium — gates, custom show |
| Medium | `LeaveResource` | `LeaveCrudController` | Medium — bulk date range creation |
| Medium | `PeriodResource` | `PeriodCrudController` | Low-Medium |
| Medium | `PaymentResource` | `PaymentCrudController` | Low-Medium |
| Medium | `ScheduledPaymentResource` | `ScheduledPaymentCrudController` | Low-Medium |
| Medium | `ExternalCourseResource` | `ExternalCourseCrudController` (279 lines) | Medium — extends Course |
| Medium | `SkillResource` | `SkillCrudController` (232 lines) | Medium — CSV import |
| Low | `PartnerResource` | `PartnerCrudController` | Simple |
| Low | `FeeResource` | `FeeCrudController` | Simple |
| Low | `BookResource` | `BookCrudController` | Simple |
| Low | `DiscountResource` | `DiscountCrudController` | Simple |
| Low | `CouponResource` | `CouponCrudController` | Simple |
| Low | `ScholarshipResource` | `ScholarshipCrudController` | Simple |
| Low | `TaxResource` | `TaxCrudController` | Simple |
| Low | `RoomResource` | `RoomCrudController` | Simple |
| Low | `LevelResource` | `LevelCrudController` | Simple |
| Low | `RhythmResource` | `RhythmCrudController` | Simple |
| Low | `YearResource` | `YearCrudController` | Simple |
| Low | `InstitutionResource` | `InstitutionCrudController` | Simple |
| Low | `PaymentmethodResource` | `PaymentmethodCrudController` | Simple |
| Low | `ProfessionResource` | `ProfessionCrudController` | Simple |
| Low | Simple resources | `MemberCrudController`, `EvaluationTypeCrudController`, `GradeTypeCrudController`, `GradeTypeCategoryCrudController`, `ResultTypeCrudController`, `SkillTypeCrudController`, `SkillScaleCrudController` | Simple |

**Middleware → Filament equivalents:**
- `role:admin` / `permission:X` → Filament Policies + `canAccess()` on Resources
- `ForceUpdate` middleware → Filament custom page or middleware
- `SetLocale` → Filament's built-in locale support or custom middleware
- `isLoggedIn` → Filament has built-in authentication

---

### What Requires Significant Manual Work

These are custom features without direct Filament equivalents:

1. **Custom Dashboard Pages** (4 role-based dashboards)
   - Admin dashboard with FullCalendar widget, enrollment insights → Filament custom dashboard with **Widgets** (stats, charts). FullCalendar → need a Filament calendar plugin (e.g., `saade/filament-fullcalendar`).
   - Teacher dashboard → Filament custom page or separate panel
   - Student dashboard → Filament custom page or separate panel (may need a separate Filament Panel since students have a very different UI)
   - HR dashboard → Filament custom page

2. **Attendance System** (complex interactive UI)
   - Course/event attendance grids with clickable toggle buttons → currently Vue components. This needs **Filament Livewire custom pages** with interactive attendance grids. Significant UI work.
   - attendance/course.blade.php — students × events matrix
   - attendance/event.blade.php — single event attendance

3. **Calendar Views** (3 Vue components)
   - Teacher calendar, room calendar, overview calendar → `saade/filament-fullcalendar` plugin or custom Livewire pages. The OverviewCalendar.vue is particularly complex with drag-and-drop.

4. **Grading/Evaluation System** (custom interactive UI)
   - grades/edit.blade.php — editable grade table per course
   - skills/student.blade.php — skill evaluation per student
   - These are currently Vue components → need Filament Livewire custom pages

5. **Cart/Checkout Flow** (9 Vue components)
   - Multi-step enrollment billing with products, discounts, taxes, payment methods, invoice types → this is a complex wizard. Could be a **Filament custom page with a multi-step Wizard form**, but significant work to replicate the interactive cart behavior from CartComponent.vue.

6. **Student Registration Flow** (6 Vue components)
   - Multi-step wizard with user data, contacts, photo upload → Filament custom page with `Wizard` form component. Needs to work as a public (unauthenticated) page.

7. **Reporting System** (14 report views + 3 controllers)
   - Reports with charts (Chart.js), data tables, period selectors → Filament custom pages with chart widgets. The old project uses `<canvas>` charts; Filament has chart widgets built-in.
   - Attendance reports (4 views) → custom Filament pages

8. **PDF Generation** (3 distinct outputs)
   - Invoice PDFs (via LaravelDaily/Invoices) → service is transferable, but trigger points need Filament actions
   - Certificate PDFs (via mPDF) → service is transferable
   - Enrollment sheet Word docs (via PhpWord) → service is transferable
   - All need **Filament Actions** to trigger downloads

9. **41 Vue Components → Livewire**
   - The old project heavily uses Vue.js for interactive features. Filament is Livewire-based. Every Vue component needs to be reimplemented as either a Filament form/table component or a custom Livewire component.

10. **My Account / Profile Pages** (7 multi-step profile forms)
    - `MyAccountController` with 7 sub-pages → Filament has a built-in profile page, but the student-specific forms (contacts, photo, profession, student info) need custom profile page extensions.

11. **Custom Course View** with student list, photo roster, enrollment modal
    - courses/show.blade.php, courses/showRoster.blade.php → Filament custom pages on the Course resource (view record + relation managers)

12. **Authorization Gates** (11 gates in `AuthServiceProvider`)
    - Need to be converted to **Filament Policies** and gate checks within resource `canAccess()`, `canCreate()`, `canEdit()`, etc.

---

### Required Composer Packages

These need to be installed in the new project:

| Package | Purpose |
|---|---|
| `spatie/laravel-permission` | Roles & permissions (already used) |
| `spatie/laravel-translatable` | Model translations |
| `spatie/laravel-medialibrary` | Student photos |
| `spatie/laravel-activitylog` | Activity logging (LogsActivity) |
| `saade/filament-fullcalendar` | Calendar views |
| `maatwebsite/excel` | Skills CSV import |
| `phpoffice/phpword` | Enrollment sheet Word export |
| `mpdf/mpdf` | Certificate PDF generation |
| `laraveldaily/laravel-invoices` | Invoice PDF generation |

---

### IMPORTANT NOTE

We want to run both the old project (academico) and the new project (academico-filament) on the same database for some time. So the database structure cannot change. Users will be invited to use the new app as soon as possible but access to the old app will remain open for a few months until they grow accustomed to the new UI. So things must not break if some users make changes in the old app while other users make changes in the new app. Scheduled tasks will be disabled in the old app to avoid duplicating alerts and emails.

### Recommended Implementation Order

Completed:

**Phase 1 — Foundation (copy & adapt)**
1. Copy all models (remove `CrudTrait`), migrations, factories, seeders
2. Install required Composer packages
3. Copy events, listeners, jobs, mailables, services, interfaces, traits, config files, lang files
4. Set up the `User` model with `HasRoles`, relationships to Student/Teacher
5. Run migrations, verify database structure
6. Set up `AppServiceProvider` with interface bindings (strategy pattern)
7. Set up `AuthServiceProvider` with gates → later convert to Policies

**Phase 2 — Simple CRUD Resources (~15 resources)**
8. Create all simple Filament resources (Level, Rhythm, Room, Year, Campus, Fee, Discount, Coupon, Tax, Book, Scholarship, Paymentmethod, Institution, Profession, Member, SkillType, SkillScale, ResultType, GradeTypeCategory, EvaluationType, GradeType)
9. Set up Filament navigation groups to match the old sidebar

**Phase 3 — Core CRUD Resources (~7 complex resources)**
10. `PeriodResource` with Year relationship
11. `TeacherResource` with User creation, soft deletes
12. `CourseResource` with all relationships, schedule, prices, children
13. `StudentResource` with photo, contacts relation manager, phone numbers
14. `EnrollmentResource` with status management, relation managers for grades/scholarships
15. `LeaveResource` with bulk date-range creation
16. `EventResource`

**Phase 4 — Custom Pages**
17. Admin dashboard with stats widgets + calendar widget
18. Attendance pages (course & event attendance grids)
19. Grade editing pages
20. Skill evaluation pages
21. Calendar pages (teacher, room, overview)
22. Report pages (internal, external, attendance, courses, rhythms, levels, gender, HR)


Still todo:

**Phase 5 — Invoicing & Checkout** ✅
23. `InvoiceResource` with invoice detail management
24. `PaymentResource` and `ScheduledPaymentResource`
25. Cart/checkout flow as Filament wizard page
26. PDF invoice download actions
27. `ResultResource` with certificate/result PDF export actions

**Phase 6 — Public & Student-Facing** ✅
28. Student registration page (public Filament page or separate panel)
29. Student dashboard (separate Filament panel or custom page)
30. Teacher dashboard
31. My Account / profile pages
32. Course list public view

**Phase 7 — Polish & Edge Cases** ✅
33. Locale/language switching
34. ForceUpdate profile completion flow
35. Email notifications integration (already migrated: all 7 Mailables, 16 listeners, 18 events, email templates)
37. External mailing system integration (already migrated: MailingSystemInterface, config, services)
38. Scheduled commands (3 Artisan commands + 8 scheduled tasks in routes/console.php)
39. `filament/spatie-laravel-media-library-plugin` for `StudentResource` photo upload
40. Comprehensive test coverage: 135 tests (36 new feature tests). Fixed bugs: StudentDashboard scope query chain, StudentAccount birthdate parsing



===

Here's the **complete final audit** comparing the old project against the new one. Items are categorized by severity.

---

## RED — Functionality Gaps (likely to cause runtime issues)

### ~~1. Authorization Gates NOT migrated~~ ✅ DONE
All 11 Gate definitions have been migrated to `AppServiceProvider::registerGates()`. The `CourseEnrollments` page uses `Gate::allows('view-course')`. `AFLojaCertificatesService` gate checks (`view-enrollment`, `view-course`) now resolve correctly.

### ~~2. SetLocale middleware not registered~~ ✅ DONE
`SetLocale` was already registered in `AdminPanelProvider` for Filament panel routes. Now also appended to the `web` middleware stack in `bootstrap/app.php` so student-facing routes (`/dashboard`, `/account`) and public routes (`/register`) also get locale switching.

### 3. MailerLite SDK package missing
MailerliteService.php imports `MailerLiteApi\MailerLite` but `mailerlite/mailerlite-api-v2-php-sdk` is **not in composer.json**. Any code path touching this service will fatal error.

### ~~4. Three model policies not migrated~~ ✅ No action needed
`CommentPolicy`, `ContactPolicy`, and `StudentPolicy` were dead code in the old project — placed in a non-standard namespace (`App\Models\Policies`), never registered in `AuthServiceProvider`, and never referenced by any controller or gate. The permissions they checked (`comments.edit`, `student.edit`) exist as seeded Spatie permissions, but the policies themselves were never wired up. Relevant authorization is handled by the migrated gates and Filament resource-level access controls.

### 5. Two events/listeners removed without replacement
- `CourseCreated` event + listener — was triggered on course creation
- `SyncUserWithMailingSystem` listener — synced users to MailerLite on creation

---

## YELLOW — Features possibly incomplete

| # | Item | Details |
|---|------|---------|
| 6 | ~~**PDF export templates**~~ ✅ | Copied 3 missing blade views from old project: `results/certificate.blade.php`, `results/export.blade.php`, `results/course-export.blade.php`. These are standalone HTML templates rendered by `AFLojaCertificatesService` via mPDF. The `ResultResource` actions (`export_result`, `export_certificate`) already call the service correctly. Note: `exportCourseResults()` exists in the service/interface but has no Filament trigger yet (it wasn't linked from any view in the old project either). No syllabus templates exist in either project. |
| 7 | ~~**`ExternalCourseFactory`**~~ ✅ No action needed | There is no `ExternalCourse` model — external courses are `Course` records with a non-null `partner_id`. The old factory was a legacy-syntax duplicate of `CourseFactory`. Tests already use `Course::factory()->create(['partner_id' => 1])`. |
| 8 | **`POST /api/checkemail`** | Email uniqueness API endpoint for registration not in new project. |
| 9 | ~~**Photo roster**~~ ✅ | Implemented as a "Photo roster" toggle mode on the `CourseEnrollments` page. Cards show student photo + name in a responsive grid. |
| 10 | **Standalone invoice creation route** | `GET /createinvoice` existed for admins — verify in Filament. |
| 11 | **Public assets** | `watermark.png`, `logo.png`, `user-icon-placeholder.png` — verify these exist if PDFs reference them. |
| 12 | **`ext-gd`, `ext-zip`** | Not declared in new composer.json. Needed for image processing & ZIP exports. |
| 13 | **.env.example** | ~40+ custom env vars from the old project — verify the new .env.example includes all needed ones. |

---

## GREEN — Intentionally removed (no action needed)

- **Telescope** → replaced by `laravel/pail`
- **Sentry** → error tracking removed
- **Backpack** → replaced by Filament  
- **Vue.js + webpack** → replaced by Vite + Livewire
- **ApolearnService / LMS integration** → removed
- **deploy.php (Deployer)** → replaced by Docker
- **elfinder, enlightn, gravatar** configs → dev/optional tools
- **`FiltersSearchableLevels` trait** → was for spatie/query-builder
- **`AddViewerRole` command** → one-time setup script
- **Custom error pages** → using framework defaults

---

**Shall I fix the RED items now?** The most impactful ones are:
1. Migrate the 11 Gate definitions to `AppServiceProvider` (or a new `AuthServiceProvider`)
2. Register `SetLocale` middleware in app.php
3. Add `mailerlite/mailerlite-api-v2-php-sdk` to composer.json
4. Create the 3 missing policies