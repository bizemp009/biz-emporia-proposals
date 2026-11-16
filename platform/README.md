# Biz Emporia — Interactive Proposal Generator & Admin Dashboard

Laravel 13 · Livewire 4 (with bundled Alpine.js) · Tailwind CSS 4 · Chart.js · Trix · DomPDF

## Quick start (local)

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # skip if .env exists
php artisan migrate --seed                          # demo data + admin user
php artisan storage:link
php artisan serve                                   # http://localhost:8000
```

Admin login (from the seeder): `admin@bizemporia.com` / `change-me-now`.
Set `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME` in `.env` **before** seeding production.

Demo links after seeding:
- Admin: `/admin/proposals`
- Client view: `/proposal/biz-emporia-acme-living` (open it in a private window — when you are logged in you get a non-tracking staff preview where signing is disabled)

Run tests: `php artisan test`

## Production (MySQL)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bizemporia.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=proposals
DB_USERNAME=...
DB_PASSWORD=...
```

Then `php artisan migrate --force`, `php artisan storage:link`, `php artisan optimize`, and add the scheduler cron
(`* * * * * php artisan schedule:run`) so proposals are marked **Expired** hourly.
Needs a PHP host (Forge, Ploi, a VPS, cPanel, etc.) — Vercel's static hosting used by the old React prototype can't run Laravel.

## Map of the spec → code

| Spec | Where |
|---|---|
| §3A Admin dashboard table (client/brand, URL slug, strategist, status badge, Edit / Duplicate / Copy Link / View PDF / Delete) | `app/Livewire/Admin/Proposals/Index.php` |
| §5 Proposal generator form (8 input groups, catalog pull-in, templates, screenshot uploads) | `app/Livewire/Admin/Proposals/Form.php` |
| Clients, Services catalog, Case Studies, Templates, Branding | `app/Livewire/Admin/*.php` |
| Template editor (all sections except client) and section-by-section **Combine templates** | `app/Livewire/Admin/Templates/Edit.php`, `app/Livewire/Admin/Templates/Combine.php`; shared fields in `app/Livewire/Concerns/EditsProposalContent.php`, shared section views in `resources/views/livewire/admin/partials/content/` |
| §4 Client proposal viewer (7 sections) | `app/Livewire/Client/ProposalViewer.php` + `resources/views/livewire/client/proposal-viewer.blade.php` |
| §4.3 ROI simulator (budget-first / traffic-first, live chart) | `resources/js/components/roi-calculator.js` |
| §4.7 Signature pad | `resources/js/components/signature-pad.js` |
| PDF export | `app/Http/Controllers/ProposalPdfController.php`, `resources/views/pdf/proposal.blade.php` |
| §6 Schema (+ services catalog, case studies, templates, settings, activity log) | `database/migrations/2026_11_04_*` |

## Behaviour notes / deviations from the spec draft

- **Contract value** = one-time + monthly × tenure + quarterly × ⌈tenure / 3⌉ (the draft summed all prices regardless of billing type).
- **Mandatory items can't be toggled off** by the client; only `is_optional` items have switches.
- **Signed proposals are locked** — the client page becomes read-only and the admin form refuses to save. Duplicate to revise.
- **Staff preview**: when logged in, viewing a proposal doesn't mark it *Viewed*, doesn't log activity and can't sign. Drafts are 404 for the public.
- **Expiry**: past `expires_at` → *Expired* (on view, on dashboard load, and hourly via `proposals:expire`). Extending the date re-opens it.
- **Activity log** (`proposal_activities`): views (once per session), add-on toggles, signature (with IP), decline, PDF downloads.
- Rich text is sanitised (`App\Support\RichText`) before it's shown to clients or rendered to PDF.
- ROI model (per month at full ramp): organic = baseline × lift; paid = spend ÷ CPC; revenue = traffic × CR × AOV;
  hours = base hours × (0.5 + 0.5 × spend ÷ default spend); ROI = (revenue − current revenue − (spend + agency fee)) ÷ (spend + agency fee).
  The agency fee is live — toggling add-ons in Pricing updates ROI.
- Slugs are human-readable (`biz-emporia-acme-living`), so they're guessable. If that matters, add a random suffix
  (e.g. `biz-emporia-acme-living-7f3k`) in the slug field — prefix is configurable under Branding.
