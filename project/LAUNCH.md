# Production launch checklist — mlmsoftware.omc.co.th

> Production is deployed **by the owner**, never by CI (`DEPLOY.md`). **Nothing here has been done on production yet — production and DNS are untouched until OMC approves in writing.**
> ✅ = verified (Phase 6, 2026-11-04/05) · ⬜ = to do at cutover · 👤 = needs OMC / the server owner (details in `OMC-CHECKLIST.md`)
> Order on cutover day: 0 → 1 → 2 → 3/4 → 5 → 6 → **12 (post-deploy check)** → 10 → 9. Section 11 if anything in 12 fails.

## 0. Go / no-go gate (all must be ✅ before switching the document root / DNS)
- ✅ SEO regression: `php tools/seo-audit/regression.php` → 0 blocking (95 baseline URLs + 153 sitemap URLs, `docs/seo/regression.md`)
- ✅ Post-deploy check on staging: `php tools/launch/postcheck.php <staging> --staging` → PASS (`docs/seo/postcheck-*.md`)
- ✅ `php scripts/check.php` 0 must-fix · `php tests/run.php` 5,022 pass / 0 fail
- ⬜ 👤 A1–A5 of `OMC-CHECKLIST.md` (approval, document root, SSL incl. www, production `.env`, WordPress backup)
- ⬜ 👤 B1–B4 lead e-mail (strongly recommended — without it sales gets no e-mail; leads are still saved and the CMS warns)

## 1. Database backup + restore
- ✅ Restore tested (2026-11-04): `php scripts/backup.php --db-only` → restored into a scratch DB → 21 tables / 5,087 rows identical, Thai text intact
- ⬜ Before cutover: full backup of the **old WordPress** DB + files (rollback target) — keep 30 days
- ⬜ After first deploy: backup of the new DB (CMS → Backups or `php scripts/backup.php`), download it off the server
- Restore procedure: `DEPLOY.md` → "Restoring a database backup"

## 2. Environment variables (`.env` on production — written on the server, never in git, never sent in chat)
- ⬜ `APP_ENV=production` (default is production if missing — safe)
- ⬜ `APP_URL=https://mlmsoftware.omc.co.th` · `CANONICAL_URL` same value or unset
- ⬜ `APP_KEY` — **new** 64-hex value (`php -r "echo bin2hex(random_bytes(32));"`), not the staging one
- ⬜ `MIGRATE_TOKEN` — new long random value · `STAGING_PASS` empty · `APP_HTTPS` unset (production default true → Secure cookies + HSTS)
- ⬜ `DB_*` production credentials · `APP_LOCALES=th,en,zh`
- ⬜ 👤 Lead e-mail: `MAIL_ENABLED=true` `MAIL_FROM=` `MAIL_DRIVER=smtp` `MAIL_HOST=` `MAIL_PORT=587` `MAIL_USER=` `MAIL_PASS=` `MAIL_ENCRYPTION=tls` (`.env.example` explains each)

## 3. HTTPS / SSL
- ⬜ 👤 Certificate for `mlmsoftware.omc.co.th` **and** `www.mlmsoftware.omc.co.th`
- ✅ http → https and www → non-www in `public/.htaccess`: one 301, encoded Thai path + query kept (tested on Apache)
- ✅ The host's Apache has `mod_http2` (`Upgrade: h2,h2c`) → HTTPS will serve HTTP/2 — this is what brings mobile LCP under 2.5 s (section 13)
- ⬜ If a CDN/proxy terminates TLS: it must send `X-Forwarded-Proto: https` (otherwise redirect loop)

## 4. DNS / document root
- ⬜ 👤 Document root → `public/` (**required** — the root `.htaccess` is a staging-only workaround)
- ⬜ 👤 `www` record → same server · lower DNS TTL a day before if the IP changes

## 5. Database migration
- ⬜ Upload files (same exclude list as `.github/workflows/deploy.yml`) + `public/wp-content/uploads/` (originals **and** `.webp` files)
- ⬜ `storage/maintenance.flag`, then `POST /_migrate` with header `X-Migrate-Token` → `ok`, migrations up to `049_lead_notify_status.sql`; the flag is removed on success
- ⬜ A failed migration leaves the site in 503 on purpose → restore the backup (section 1), fix, redeploy

## 6. File storage / permissions
- ⬜ `storage/` and `public/uploads/` writable by PHP; everything else read-only
- ✅ Secret paths not reachable on staging (`.env` `.git` `app/` `config/` `database/` `storage/` `tools/` `project/` …) — `postcheck.php` repeats this on production

## 7. Cache / CDN
- ✅ Static assets: `Cache-Control: max-age=31536000, immutable` + `?v=<mtime>` · gzip on (app.css 139 → 26 KB)
- ✅ HTML `no-store` (session on every request) — always fresh. Do not let a CDN cache HTML while that is so
- ⬜ If a CDN is added later: do not cache `/admin`, `/_migrate`, `/_health`, POST responses

## 8. Security
- ✅ CSP (`script-src 'self'`), nosniff, X-Frame SAMEORIGIN, Referrer-Policy, Permissions-Policy, HttpOnly + SameSite cookies, CSRF on every write, role middleware (tests)
- ⬜ First admin login on production → forced password change → create real accounts (admin / editor / sales) · delete every `zz-qa-*` account

## 9. Error logging / monitoring
- ✅ Errors → `storage/logs/` (size-capped) · no stack traces on production
- ✅ Lead e-mail failures are visible: CMS dashboard alert + leads list "อีเมลแจ้ง" column + resend button (leads are never lost)
- ⬜ 👤 Uptime monitor on `https://mlmsoftware.omc.co.th/_health` (200 = app + DB up)
- ⬜ First week: daily look at `storage/logs/`, CMS → 404 log, CMS dashboard (e-mail alert)

## 10. SEO / Search Console
- ✅ robots.txt production `Allow` + `Disallow: /admin/` + Sitemap (tests) · non-production `Disallow: /` + noindex header
- ⬜ 👤 Search Console (domain property): submit `/sitemap.xml`; URL-inspect `/`, `/mlm-software-omc/`, 3 old Thai post URLs, `/en/`, `/zh/`
- ⬜ 👤 GA4 / GTM IDs in CMS → Settings

## 11. Rollback plan
1. **Within minutes:** point the document root (or DNS) back to the old WordPress install — its files and DB are untouched (section 1).
2. **Code problem only:** redeploy the previous good commit (`DEPLOY.md` → Rollback).
3. **Migration broke data:** maintenance flag → restore the pre-deploy backup → redeploy.
4. Keep the WordPress copy for 30 days after launch before deleting anything.

### 11a. SEO rollback plan (rankings)
Triggers — any one of these within the first 14 days:
- `postcheck.php` FAIL on production (404s on old URLs, noindex, wrong canonical, redirect chains)
- Search Console: indexed pages drop > 20 % vs the week before launch, or "Excluded by noindex" / "Not found (404)" rises for old URLs
- Clicks on the top-20 old URLs drop > 30 % week-on-week for 2 weeks (normal migration dip is 1–2 weeks)

Response, smallest first:
1. **Fix forward** (most cases): add the missing redirect in CMS → Redirects (301, no chains) · fix the page's canonical/robots in the CMS · re-run `postcheck.php` · Search Console → URL inspection → Request indexing
2. **Partial fix**: if one section is the problem (e.g. articles lost text or images), the two systems cannot run side by side on one domain — restore that content in the new CMS from the WordPress backup (`docs/migration/content.json` has every old post as imported)
3. **Full rollback**: section 11 step 1 (document root back to WordPress). The old URLs, titles and content are unchanged there, so rankings recover with the next crawl. Keep `docs/seo/baseline-2026-11-03/` to compare
Never: change Thai URLs to "fix" a ranking dip, or 302 instead of 301.

## 12. Post-deploy check (run immediately after cutover, then day 1 / day 7 / day 28)
```
php tools/launch/postcheck.php https://mlmsoftware.omc.co.th          # SEO + production rules → docs/seo/postcheck-*.md, exit 1 on any problem
PSI_KEY=<key> php tools/launch/postcheck.php https://mlmsoftware.omc.co.th --psi   # + PageSpeed Insights from Google's servers
php tools/seo-audit/crawl.php https://mlmsoftware.omc.co.th docs/seo/after-launch   # full crawl → compare with docs/seo/baseline-2026-11-03/
```
`postcheck.php` checks: 95 old URLs (no 404, single 301, canonical on the production host, 410 only for `/wp-login.php` `/xmlrpc.php`) · no `X-Robots-Tag`/meta noindex · robots.txt Allow + Sitemap · HSTS · `http://`, `http://www.`, `https://www.` → one 301 to `https://mlmsoftware.omc.co.th` with path + query · every sitemap 200 · `/_health` · secret paths closed.

Login check with authorised test accounts (any environment): `QA_USER=zz-qa-sales QA_PASS=… php tools/launch/staging-smoke.php <url>` (see the script header).

Lead e-mail: CMS → Settings → **Send test e-mail** → confirm it arrives (and not in spam). Then send one real form per language (`/demo/`, `/en/demo/`, `/zh/demo/`) and check the leads list shows "ส่งแล้ว".

## 13. Performance — what was measured and how to re-measure
| Where (home, mobile, Lighthouse lab) | LCP | Why |
|---|---|---|
| Staging as served today (HTTP/1.1) | 2.6–3.5 s | staging limitation: no TLS → no HTTP/2 |
| Same staging over TLS + HTTP/2 (local proxy, same server) | **2.3–2.5 s** | what production HTTPS will be |
| Other key pages over HTTP/2 | services 1.8 · article 2.5 · EN home 1.6 · ZH home 1.7 · contact 1.9 · blog 2.7 s | blog: first card image was lazy → now eager (deployed after this run) |

Code fixes done: no forced synchronous layout before first paint (reveal/parallax/header reads after the first frame — A/B: perf 61 → 68, LCP 3.8 → 3.3 s on a loaded test PC) · first blog card eager · WebP + srcset for all old images · fonts self-hosted, subset, `swap`, preloaded.
Measurement caveats on the test PC (not the site): its browser traffic to plain-HTTP staging gets a 137 KB script injected by a local network layer (VPN/web filter) — staging Lighthouse runs from that PC were pessimistic; CPU at ~55 % from desktop apps makes TBT noisy.
**Re-measure on production:** section 12 `--psi` (lab, Google servers) on day 1 · Search Console → Core Web Vitals and PSI "field data" (CrUX) after ~28 days of real traffic — that is the real LCP/INP/CLS. Target p75: LCP ≤ 2.5 s · INP ≤ 200 ms · CLS ≤ 0.1.
