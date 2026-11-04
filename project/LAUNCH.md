# Production launch checklist — mlmsoftware.omc.co.th

> Production is deployed **by the owner**, never by CI (`DEPLOY.md`). Nothing here has been done on production yet.
> ✅ = verified in Phase 6 (2026-11-04) · ⬜ = to do at cutover · 👤 = needs OMC / the server owner

## 0. Go / no-go gate (all must be ✅ before switching DNS / document root)
- ✅ SEO regression: `php tools/seo-audit/regression.php` → 0 blocking (95 baseline URLs + 153 sitemap URLs, `docs/seo/regression.md`)
- ✅ `php scripts/check.php` 0 must-fix · `php tests/run.php` all pass
- ⬜ 👤 Real data that only OMC can give (see "Waiting for OMC" in `QA-REPORT.md`) — none of it blocks the launch, but the launch copy is final only after it
- ⬜ 👤 Explicit approval from the owner to deploy production

## 1. Database backup + restore
- ✅ Restore tested (2026-11-04): `php scripts/backup.php --db-only` → restored into a scratch DB → 21 tables / 5,087 rows identical, Thai text intact
- ⬜ Before cutover: full backup of the **old WordPress** DB + files (rollback target) — keep until the rollback window ends (30 days)
- ⬜ After first deploy: take a backup of the new DB (CMS → Backups or `php scripts/backup.php`) and download it off the server
- Restore procedure: `DEPLOY.md` → "Restoring a database backup"

## 2. Environment variables (`.env` on production — never in git)
- ⬜ `APP_ENV=production` (default is production if missing — safe)
- ⬜ `APP_URL=https://mlmsoftware.omc.co.th` (robots.txt `Sitemap:` line comes from this) · `CANONICAL_URL` same value or unset
- ⬜ `APP_KEY` — **new** 64-hex value for production (`php -r "echo bin2hex(random_bytes(32));"`), not the staging one
- ⬜ `MIGRATE_TOKEN` — new long random value · `STAGING_PASS` empty
- ⬜ `DB_*` production credentials · `APP_LOCALES=th,en,zh`
- ⬜ 👤 `MAIL_ENABLED=true` + `MAIL_FROM` on a domain with SPF/DKIM — otherwise lead e-mails are not sent (leads are still saved in the CMS)
- ⬜ `APP_HTTPS` unset (defaults to true on production → Secure cookies + HSTS)

## 3. HTTPS / SSL
- ⬜ 👤 Valid certificate for `mlmsoftware.omc.co.th` **and** `www.mlmsoftware.omc.co.th` (the www redirect needs TLS too)
- ✅ http → https and www → non-www: `public/.htaccess`, one 301 hop, original encoded Thai path + query kept (tested on Apache 2026-11-04)
- ⬜ After cutover: `curl -I http://www.mlmsoftware.omc.co.th/2025/05/20/...` → one `301` straight to `https://mlmsoftware.omc.co.th/...`
- ⬜ If a CDN/proxy terminates TLS: it must send `X-Forwarded-Proto: https` (otherwise redirect loop)

## 4. DNS / document root
- ⬜ 👤 Document root → `public/` (**required** — the root `.htaccess` is a staging-only workaround)
- ⬜ 👤 `www` record exists and points at the same server
- ⬜ Lower DNS TTL a day before if the IP changes

## 5. Database migration
- ⬜ Upload files (same exclude list as `.github/workflows/deploy.yml`) + `public/wp-content/uploads/` (originals **and** the new `.webp` files)
- ⬜ Put `storage/maintenance.flag`, then `POST /_migrate` with header `X-Migrate-Token` → expect `ok` and migrations up to `048_legacy_webp_sizes.sql`; the flag is removed on success
- ⬜ A failed migration leaves the site in 503 on purpose → restore the backup (section 1), fix, redeploy

## 6. File storage / permissions
- ⬜ `storage/` (logs, backups, maintenance flag) and `public/uploads/` writable by PHP; everything else read-only
- ✅ Not reachable from the web (checked on staging): `.env`, `.git/`, `app/`, `core/`, `config/`, `database/`, `tools/`, `docs/`, `project/`, `storage/` → 403/404
- ⬜ Repeat that check on production after deploy (CI's last step does it for staging)

## 7. Cache / CDN
- ✅ Static assets: `Cache-Control: max-age=31536000, immutable` + `?v=<mtime>` cache-busting · gzip on (staging host: app.css 139 KB → 26 KB)
- ✅ HTML: `no-store` on every page (PHP session starts on each request) — always fresh; costs only repeat-visit speed. Do not let a CDN cache HTML while that is so
- ⬜ If a CDN is added later: do not cache `/admin`, `/_migrate`, `/_health`, POST responses; pass `X-Forwarded-Proto`

## 8. Security headers (production adds HSTS)
- ✅ CSP (`script-src 'self'`; nonce + GTM only when tracking is on), `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, HttpOnly + SameSite cookies
- ⬜ After cutover: `curl -I https://mlmsoftware.omc.co.th/` shows `Strict-Transport-Security` and **no** `X-Robots-Tag`
- ⬜ First admin login on production → forced password change works; create real accounts (admin / editor / sales); delete any test account

## 9. Error logging / monitoring
- ✅ Errors → `storage/logs/` (`Core\Logger`, size-capped by `LOG_MAX_BYTES`); production never shows stack traces
- ⬜ 👤 Uptime monitor on `https://mlmsoftware.omc.co.th/_health` (200 = app + DB up), alert to OMC
- ⬜ Look at `storage/logs/` and CMS → 404 log daily for the first week

## 10. SEO / Search Console / sitemap
- ✅ robots.txt production: `Allow: /`, `Disallow: /admin/`, `Sitemap:` line (tested) · non-production: `Disallow: /` + noindex header
- ✅ Canonical / og:url / sitemap use `https://mlmsoftware.omc.co.th` on every environment
- ⬜ 👤 Search Console (domain property): submit `/sitemap.xml`; URL-inspect `/`, `/mlm-software-omc/`, 3 old Thai post URLs, `/en/`, `/zh/`
- ⬜ Crawl production once: `php tools/seo-audit/crawl.php https://mlmsoftware.omc.co.th docs/seo/after-launch` and compare with the baseline
- ⬜ Watch Coverage / Page indexing and clicks for 4 weeks; 404 log in the CMS → add redirects for anything real
- ⬜ 👤 GA4 / GTM IDs in CMS → Settings (tracking loads only on production + when set)

## 11. Rollback plan
1. **Within minutes:** point the document root (or DNS) back to the old WordPress install — its files and DB are untouched (section 1).
2. **Code problem only:** redeploy the previous good commit (`DEPLOY.md` → Rollback).
3. **Migration broke data:** maintenance flag → restore the pre-deploy backup → redeploy.
4. Keep the WordPress copy for 30 days after launch before deleting anything.
