# Deploy · Staging · Rollback

## Environments

| | Staging | Production (current, WordPress) |
|---|---|---|
| URL | http://203.146.127.221/~omcai/mlmsoftware/ | https://mlmsoftware.omc.co.th |
| Server path | `/domains/<dev-domain>.com/public_html/mlmsoftware` (FTP account `omcai`) | `/home/omc/domains/omc.co.th/public_html/mlmsoftware` |
| Deployed by | GitHub Actions on push to `main` | **Not touched by CI.** Switching to the new site goes through the SEO Migration Gate |
| Document root | Cannot point at `public/` (userdir URL) → root `.htaccess` routes everything into `public/` | Must point at `public/` |
| Indexing | `X-Robots-Tag: noindex` on every response (`APP_ENV=staging`) | indexable |
| Access | HTTP Basic Auth, user `omc`, password = secret `STAGING_PASS` | public |

## How a deploy runs (`.github/workflows/deploy.yml`)

1. **check**: `.env` must not be in git, and `php scripts/check.php` must pass. If either fails, nothing is uploaded.
2. Build `.env` from GitHub Secrets (`APP_ENV=staging`).
3. Set `RewriteBase` in both `.htaccess` files from the path in `APP_URL`.
4. Upload `storage/maintenance.flag` and `.env` with lftp. The site shows 503 from here on.
5. Sync the files (FTP-Deploy-Action, plain FTP; see NOTES.md).
6. Call `/_migrate?token=…`. It runs the migrations and **removes the maintenance flag** on success.
7. Verify: `/_health` returns 200, `/` returns 401 (login required), the noindex header is present, and `.env`, `app/`, `core/`, `config/` and `database/` are not reachable.

## GitHub Secrets

`FTP_SERVER` `FTP_USERNAME` `FTP_PASSWORD` `FTP_SERVER_DIR` `APP_URL` `MIGRATE_TOKEN` `DB_HOST` `DB_NAME` `DB_USER` `DB_PASS` `STAGING_PASS`

## Rollback

FTP has no atomic switch, so rollback means deploying an older commit again:

1. **Fastest:** Actions → `deploy` → *Run workflow* → choose the last good commit or tag as the ref.
2. **Recorded in history:** `git revert <bad merge>` on `main`, then push. CI deploys the reverted state.
3. If a migration broke the schema: restore the DB from `storage/backups/` (`php scripts/backup.php`) **before** redeploying. Migrations are forward-only.

The site stays in maintenance (503) when a migration fails. That is intentional: it is safer than serving a half-migrated site.

## Production cutover

Handled separately through the SEO Migration Gate (see PLAN). The old WordPress files and DB stay untouched and backed up until the gate's rollback window is over.
