# Deploy · Staging · Rollback

## Environments

| | Staging | Production (current, WordPress) |
|---|---|---|
| URL | http://203.146.127.221/~omcai/mlmsoftware/ | https://mlmsoftware.omc.co.th |
| Server path | `/domains/<dev-domain>.com/public_html/mlmsoftware` (FTP account `omcai`) | `/home/omc/domains/omc.co.th/public_html/mlmsoftware` |
| Deployed by | GitHub Actions on push to `main` | **Not touched by CI.** Switching to the new site goes through the SEO Migration Gate |
| Document root | Cannot point at `public/` (userdir URL) → root `.htaccess` routes everything into `public/` | Must point at `public/` |
| Indexing | `X-Robots-Tag: noindex` on every response (`APP_ENV=staging`) | indexable |
| Access | Open, no login (user decision 2026-11-04). Still noindex: `X-Robots-Tag` + robots.txt `Disallow: /` | public |

## How a deploy runs (`.github/workflows/deploy.yml`)

1. **check**: `.env` must not be in git, and `php scripts/check.php` must pass. If either fails, nothing is uploaded.
2. Build `.env` from GitHub Secrets (`APP_ENV=staging`).
3. Set `RewriteBase` in both `.htaccess` files from the path in `APP_URL`.
4. Upload `storage/maintenance.flag` and `.env` with lftp. The site shows 503 from here on.
5. Sync the files (FTP-Deploy-Action, plain FTP; see NOTES.md).
6. Call `/_migrate` with header `X-Migrate-Token`. It runs the migrations and **removes the maintenance flag** on success.
7. Verify: `/_health` returns 200, `/` is not 401 (staging is open), the noindex header is present, and `.env`, `app/`, `core/`, `config/` and `database/` are not reachable.

## GitHub Secrets

`FTP_SERVER` `FTP_USERNAME` `FTP_PASSWORD` `FTP_SERVER_DIR` `APP_URL` `MIGRATE_TOKEN` `DB_HOST` `DB_NAME` `DB_USER` `DB_PASS` `STAGING_PASS` `APP_KEY`

**`APP_KEY` is required since Phase 3** (the deploy stops if it is missing). It signs the device cookie that lets an account owner log in while their username is being attacked from many IPs (`core/DeviceCookie.php`). Generate one per environment: `php -r "echo bin2hex(random_bytes(32));"` and add it as a repository secret before the next push to `main`. Changing it only invalidates the device cookies (owners may hit the per-username cap once more); it never logs anyone out.

## Rollback

FTP has no atomic switch, so rollback means deploying an older commit again:

1. **Fastest:** Actions → `deploy` → *Run workflow* → choose the last good commit or tag as the ref.
2. **Recorded in history:** `git revert <bad merge>` on `main`, then push. CI deploys the reverted state.
3. If a migration broke the schema: restore the DB from `storage/backups/` **before** redeploying. Migrations are forward-only. Backups are made with `php scripts/backup.php` or from CMS → Backups (admin).

### Restoring a database backup (CLI only — there is no one-click restore in the web UI on purpose)

1. Put the site in maintenance: create `storage/maintenance.flag`.
2. Download the file (`db-YYYYMMDD-HHMMSS.sql.gz`) from CMS → Backups, or take it from `storage/backups/` over FTP.
3. `gunzip db-....sql.gz` then `mysql --default-character-set=utf8mb4 -u <user> -p <db_name> < db-....sql` (the file starts with `SET NAMES utf8mb4`, but keep the flag on Windows).
4. Open `/_health`, log in to `/admin`, check a few pages, then delete `storage/maintenance.flag`.
5. The restore replaces every table, including `audit_logs` and `users`: anything written after the backup time is gone.

The site stays in maintenance (503) when a migration fails. That is intentional: it is safer than serving a half-migrated site.

## Production cutover

Handled separately through the SEO Migration Gate (see PLAN). The old WordPress files and DB stay untouched and backed up until the gate's rollback window is over.

## Production (mlmsoftware.omc.co.th) — deployed by the owner (2026-11-04)
The owner deploys production manually; CI only deploys staging. Before uploading:
1. Wait until Phase 3 + 4 are merged and the staging SEO regression report passes (every baseline URL 200/301/410 as mapped).
2. Document root must point at `public/` (no root `.htaccess` needed in production).
3. Create `.env` with `APP_ENV=production`, production DB credentials, `APP_URL=https://mlmsoftware.omc.co.th`, a new `MIGRATE_TOKEN`, and `APP_KEY`. Leave `STAGING_PASS` empty.
4. Upload files (same exclude list as `deploy.yml`) and the `public/wp-content/uploads/` images, then run `/_migrate` with header `X-Migrate-Token`.
5. Check: `/robots.txt` says `Allow`, no `X-Robots-Tag: noindex` header on `/`, and canonical tags point to https://mlmsoftware.omc.co.th.
6. Keep the WordPress backup ready for rollback.
