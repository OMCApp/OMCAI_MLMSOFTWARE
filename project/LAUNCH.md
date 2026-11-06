# Production runbook — mlmsoftware.omc.co.th

> **Status 2026-11-06: prepared, not deployed.** Production files, the WordPress database and DNS are untouched.
> Go-live needs the written approvals in `OMC-CHECKLIST.md` → section A. Every step below is written for the server owner (OMC / host admin) and can be followed literally.
> ✅ = verified · ⬜ = to do · 👤 = needs OMC / the host

## 0. What is where (verified from outside, 2026-11-06)

| | Value | How verified |
|---|---|---|
| Production server | `203.146.170.60` (`mlmsoftware`, `www.mlmsoftware`, `omc.co.th`, `demomlm` all on it) — **not** the staging server (203.146.127.221) | DNS A records |
| Web server | Apache, HTTP/2 over TLS (`ALPN h2`), TLS 1.0/1.1 refused | openssl probe |
| Certificate | Let's Encrypt, SAN covers `mlmsoftware.omc.co.th` **and** `www.mlmsoftware.omc.co.th`, valid until 30 Jan 2027 | openssl |
| WordPress today | `/home/omc/domains/omc.co.th/public_html/mlmsoftware` (DirectAdmin layout) | DEPLOY.md |
| PHP / MySQL version | **unknown from outside** → `/_preflight` checks it (needs PHP ≥ 8.1, MySQL ≥ 5.7.8 / MariaDB ≥ 10.2.7) | — |
| Mail | MX `mail.1sc.co.th` · SPF `v=spf1 a mx ip4:203.146.170.60 ~all` · **no DMARC** · **no DKIM found** on common selectors · SMTP 587 STARTTLS (cert OK) + AUTH LOGIN | DNS + SMTP greeting (no login) |

## 1. Safety rules for the whole launch
1. **WordPress is never overwritten or deleted.** The new app goes into a **separate folder**; going live = changing which folder the subdomain serves. Rollback = changing it back (minutes).
2. **The new app uses a new, empty database.** `/_preflight` refuses a database that contains WordPress tables.
3. **No URL or keyword changes** beyond the reviewed mapping (`docs/seo/regression.md`: 57 × 200 · 36 × 301 · 2 × 410).
4. Secrets (`.env`, DB and SMTP passwords, `MIGRATE_TOKEN`) are typed on the server only — never into git, chat, e-mail or reports.

## 2. Backup of the WordPress site (T−1 day) — 👤 host admin
1. DirectAdmin → **Create/Restore Backups** → full backup of user `omc` (files + databases + e-mail settings) **or** manually:
   - files: download `public_html/mlmsoftware/` completely (incl. `wp-config.php`, `.htaccess`, `wp-content/uploads`)
   - database: phpMyAdmin → WordPress DB → Export → SQL (custom: add `DROP TABLE`, UTF-8) → `wordpress-YYYYMMDD.sql.gz`
2. Store the backup **in two places off the server**; keep it ≥ 30 days.
3. **Prove it restores** (on a test PC with MySQL, outside the project folder):
   ```
   php tools/launch/verify-wp-backup.php --sql=wordpress-YYYYMMDD.sql.gz --files=<extracted mlmsoftware folder>
   ```
   Must end with `BACKUP IS RESTORABLE` (WordPress core tables, siteurl, **46/46 old post/page URLs**, every image the old site used). The tool restores into a throw-away database and always drops it. ✅ tool tested 2026-11-06 with a synthetic backup (passes when complete, fails when a post or image is missing).

## 3. Prepare the new app on the server (T−1 day, site still WordPress) — 👤
1. Build the package from the approved commit (on the dev PC): `php tools/launch/build-release.php <commit>` → `mlmsoftware-<sha>.zip` + `.sha256` (no `.env` inside; built from git only). ✅ rehearsed with `f7c8207`: 1,004 files.
2. Upload and extract into a **new folder outside `public_html`**, e.g. `/home/omc/domains/omc.co.th/mlmsoftware-app/` → contains `app/ core/ public/ …`.
3. Create a **new MySQL database + user** (e.g. `omc_mlmcms` / `omc_mlmcms`), privileges on that database only.
4. Create `mlmsoftware-app/.env` (chmod 600) — keys, no values here:
   `APP_ENV=production` · `APP_URL=https://mlmsoftware.omc.co.th` · `APP_TIMEZONE=Asia/Bangkok` · `APP_LOCALES=th,en,zh` ·
   `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS` (new DB) · `MIGRATE_TOKEN` (new, 64 hex) · `APP_KEY` (new, 64 hex: `php -r "echo bin2hex(random_bytes(32));"`) ·
   `MAIL_ENABLED=true` `MAIL_FROM=<noreply@omc.co.th>` `MAIL_DRIVER=smtp` `MAIL_HOST=mail.1sc.co.th` `MAIL_PORT=587` `MAIL_ENCRYPTION=tls` `MAIL_USER` `MAIL_PASS`.
   Leave out `STAGING_PASS` and `APP_HTTPS` (production defaults are right).
5. Permissions: `storage/`, `storage/logs`, `storage/backups`, `public/uploads` writable by PHP; the rest read-only.
6. Create `mlmsoftware-app/storage/maintenance.flag` (empty file) — the new site will answer **503 + Retry-After** until migrations succeed.
   ‼️ Without the flag an empty database shows HTTP 500 — rehearsal 2026-11-06 confirmed this, so the flag is mandatory before the switch.

## 4. Switch (T−0, ~10 minutes, pick the option the host supports) — 👤
| Option | How | Rollback |
|---|---|---|
| **A (preferred)** host admin sets the subdomain's document root to `…/mlmsoftware-app/public` | DirectAdmin admin → custom HTTPD config for `mlmsoftware.omc.co.th` (+ `www`) | set it back to `…/public_html/mlmsoftware` |
| **B** SSH | `mv public_html/mlmsoftware public_html/mlmsoftware-wordpress-YYYYMMDD` then `ln -s ../mlmsoftware-app/public public_html/mlmsoftware` (needs `FollowSymLinks`) | `rm public_html/mlmsoftware && mv public_html/mlmsoftware-wordpress-YYYYMMDD public_html/mlmsoftware` |
| **C** File Manager only (fallback) | rename `public_html/mlmsoftware` → `mlmsoftware-wordpress-YYYYMMDD`, create `public_html/mlmsoftware`, extract the release **with its root `.htaccess`** there (same layout as staging, which forwards to `public/` and blocks `.env`/source — verified on staging) | rename the two folders back |
WordPress files stay complete in every option.

## 5. Go live (right after the switch)
```
curl -H "X-Migrate-Token: <token>" https://mlmsoftware.omc.co.th/_preflight      # 1. every line OK/WARN — any FAIL = stop, roll back (section 8)
curl -X POST -H "X-Migrate-Token: <token>" https://mlmsoftware.omc.co.th/_migrate   # 2. "migrated 28" + "maintenance off" → site is live
```
3. **Within one minute:** log in at `/admin/login` as `admin` with the initial password → forced password change → new strong password. (The bcrypt hash of the initial password is in the public GitHub repository — change it immediately.)
4. Create real accounts (admin / editor / sales); `/_preflight` again → every line OK (`admin accounts` no longer warns).
5. CMS → Settings → **Send test e-mail** → arrives in the sales inbox (check spam).

## 6. Post-launch verification (same hour)
```
php tools/launch/postcheck.php https://mlmsoftware.omc.co.th            # PASS required: 95 old URLs, noindex absent, robots, HSTS, http/www → one 301, sitemaps, secrets closed
QA_USER=… QA_PASS=… php tools/launch/staging-smoke.php https://mlmsoftware.omc.co.th   # optional, with a zz-qa-* account
```
Then: one real form per language (`/demo/`, `/en/demo/`, `/zh/demo/`) → leads list shows **ส่งแล้ว**, e-mail arrives · Search Console → submit `/sitemap.xml`, URL-inspect `/`, `/mlm-software-omc/`, 3 old Thai post URLs, `/en/`, `/zh/` · GA4/GTM IDs in CMS.
Baseline for comparison: `docs/seo/postcheck-mlmsoftware.omc.co.th-20261105-1635.md` (today's WordPress: 6 old URLs 404, `http://www` = 2-hop redirect, no HSTS, robots without Sitemap — all fixed by the new site).
Repeat `postcheck.php` (with `--psi` and `PSI_KEY=`) on day 1, 7 and 28; CMS → 404 log daily in week 1.

## 7. Monitoring
- ⬜ 👤 Uptime monitor on `https://mlmsoftware.omc.co.th/_health` (200 = app + DB)
- ✅ Lead e-mail failures: CMS dashboard alert + resend; leads are never lost
- `storage/logs/` daily in week 1 · take a DB backup (CMS → Backups) after day 1 and download it

## 8. Rollback
**Triggers:** any FAIL in `/_preflight` or `postcheck.php` that cannot be fixed in 15 minutes · site errors (500/503 > 5 min) · forms not saving leads.
1. Reverse section 4 (A/B/C) → WordPress serves again immediately (its files and database were never touched).
2. Keep the new app folder and its database for diagnosis; nothing to restore on the WordPress side.
3. If the WordPress files were damaged by mistake: restore from the section 2 backup (verified restorable).

### 8a. SEO rollback (rankings, first 14 days)
Triggers: `postcheck.php` FAIL · Search Console indexed pages −20 % vs the week before · "Not found (404)"/"Excluded by noindex" rising for old URLs · clicks on the top-20 old URLs −30 % for 2 weeks in a row (a 1–2 week migration dip is normal).
1. **Fix forward** (usual): add the missing 301 in CMS → Redirects (no chains) · fix canonical/robots in the CMS · Request indexing.
2. **Content gap:** restore that content in the new CMS from `docs/migration/content.json` / the WordPress backup.
3. **Full rollback:** section 8 step 1 — old URLs, titles and content are unchanged there.
Never change Thai URLs to "fix" a dip, never use 302 for a permanent move.

## 9. Performance (unchanged since 2026-11-05)
Lab, mobile, served over TLS + HTTP/2 like production: home TH 2.3–2.5 s · EN 1.6 · ZH 1.7 · services 1.8 · article 2.5 · blog 1.9 · contact 1.9 · CLS 0. Staging over HTTP/1.1 is slower (staging limitation). Real LCP/INP/CLS: Search Console → Core Web Vitals after ~28 days. Target p75: LCP ≤ 2.5 s · INP ≤ 200 ms · CLS ≤ 0.1.
