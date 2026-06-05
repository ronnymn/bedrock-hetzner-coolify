# Multisite runbook

Subdomain multisite for theme testing. Code lives in `config/application.php`
(env-gated). This runbook covers the live conversion and per-site provisioning.

## One-time conversion

### 0. Backup (MANDATORY — prod has content)
```bash
# Via WP-CLI inside the running container (Coolify ▸ app ▸ terminal), or remotely:
wp db export /tmp/pre-multisite-$(date +%Y%m%d).sql
```
Download the dump off the container. Uploads already live on Bunny.net (safe).

### 1. Phase 1 — allow multisite
- Coolify ▸ app ▸ Environment: set `WP_ALLOW_MULTISITE=true`. Save.
- `git push` (or redeploy) and let the webhook deploy. Do NOT also hit the deploy API.
- wp-admin ▸ Tools ▸ **Network Setup** ▸ select **Sub-domains** ▸ Install.
  - WordPress creates `wp_blogs`, `wp_site`, `wp_sitemeta` (additive).
  - It prints Apache `.htaccess` + `wp-config.php` snippets — **ignore the
    htaccess** (we use Caddy); the constants map to the env vars in Phase 2.
- Verify the existing site + wp-admin still load unchanged.

### 2. Phase 2 — activate the network
- Coolify ▸ Environment: add
  - `MULTISITE=true`
  - `DOMAIN_CURRENT_SITE=<bare main host, e.g. example.com>`
  - (`PATH_CURRENT_SITE=/`, `SITE_ID_CURRENT_SITE=1`, `BLOG_ID_CURRENT_SITE=1` —
    defaults already cover these; set explicitly if you prefer.)
- Redeploy. The **Network Admin** menu (My Sites ▸ Network Admin) now appears.
- Verify: `wp site list` lists blog 1; main site front-end + admin work.

## Add a theme-test site (repeat per theme)
1. Ensure the theme is in `web/app/themes/` (Composer/git) and deployed.
2. DNS: add `theme-x.<domain>` → server IP (A or CNAME).
3. Coolify ▸ app ▸ Domains: add `https://theme-x.<domain>` → Save. Traefik
   issues a per-host cert via HTTP challenge (no wildcard).
4. Network Admin ▸ Sites ▸ Add New: site address `theme-x`, fill title/admin.
5. That site ▸ Themes: activate the theme you want to test.
6. Verify: visit `https://theme-x.<domain>`; upload a test image and confirm it
   lands under `sites/<blog_id>/` in the Bunny bucket.

## Rollback
Remove `MULTISITE` and `WP_ALLOW_MULTISITE` env vars ▸ redeploy ▸ back to single
site (network tables stay dormant, harmless). Hard fallback: restore the Phase-0
DB dump.
