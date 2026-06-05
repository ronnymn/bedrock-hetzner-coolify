# Design: Convert bedrock-starter to subdomain multisite

**Date:** 2026-06-04
**Status:** Approved (pending spec review)

## Goal

Convert the single-site Bedrock install into a WordPress **subdomain multisite
network** so that multiple themes can be developed and tested side by side, each
on its own subsite, all sharing one codebase, database, and container.

The current production site becomes **blog 1** (the network's main site) and
keeps all of its existing content. New theme-test sites are created from Network
Admin as subdomains: `theme-a.<domain>`, `theme-b.<domain>`, etc.

### Why subdomain (not subdirectory)

Subdirectory multisite on this stack (Bedrock's `/wp` core layout + Caddy /
FrankenPHP, no `.htaccess`) requires hand-written, brittle rewrite rules that
strip a per-site prefix from `wp-admin` / `wp-includes` / `wp-content` / `.php`
requests. Subdomain multisite routes by HTTP host instead, so it needs
effectively **no** custom rewrites. The only cost is one DNS record + one Coolify
domain entry per new site — far cheaper than maintaining rewrite rules across WP
and Caddy upgrades. A wildcard cert is avoided by enumerating each subdomain as
an explicit Coolify domain (Traefik issues a per-host cert via HTTP challenge).

## Constraints / context

- **Stack:** Bedrock (core in `web/wp/`, content in `web/app/`), FrankenPHP
  worker mode (`worker.php`), Caddy via `frankenphp.Caddyfile`, Coolify/Traefik
  TLS termination, Bunny.net S3 uploads (`humanmade/s3-uploads`).
- **Live data:** the production DB has content that must survive the conversion.
- **`roots/bedrock-autoloader`** is already a dependency — it autoloads nested
  mu-plugin packages, so `roots/multisite-url-fixer` loads with no manual glue.
- **Repo convention:** plugins/themes are Composer/git-managed; `.env` /
  Coolify env vars are the single source of runtime config (`Config::define`).

## Components / changes

### 1. `config/application.php` — env-gated multisite block

Added before `Config::apply()`. No-op for single-site (gate is unset), and split
into two phases so rollout is incremental:

```php
// ─── Multisite (env-gated; no-op when WP_ALLOW_MULTISITE unset) ───
if (filter_var(env('WP_ALLOW_MULTISITE'), FILTER_VALIDATE_BOOLEAN)) {
    Config::define('WP_ALLOW_MULTISITE', true);

    if (filter_var(env('MULTISITE'), FILTER_VALIDATE_BOOLEAN)) {
        Config::define('MULTISITE', true);
        Config::define('SUBDOMAIN_INSTALL',    true);
        Config::define('DOMAIN_CURRENT_SITE',  env('DOMAIN_CURRENT_SITE'));
        Config::define('PATH_CURRENT_SITE',    env('PATH_CURRENT_SITE') ?: '/');
        Config::define('SITE_ID_CURRENT_SITE', (int) (env('SITE_ID_CURRENT_SITE') ?: 1));
        Config::define('BLOG_ID_CURRENT_SITE', (int) (env('BLOG_ID_CURRENT_SITE') ?: 1));
    }
}
```

- `SUBDOMAIN_INSTALL` is hardcoded `true` (this design is subdomain-only).
- `DOMAIN_CURRENT_SITE` is the bare production domain, set as a Coolify env var
  at deploy time — not committed.

### 2. `.env.example` — document the new vars

Add a commented multisite section:

```dotenv
# ─── Multisite (optional) ───
# Phase 1: set WP_ALLOW_MULTISITE=true, deploy, run Network Setup in wp-admin.
# Phase 2: add MULTISITE=true + DOMAIN_CURRENT_SITE, deploy.
WP_ALLOW_MULTISITE=
MULTISITE=
DOMAIN_CURRENT_SITE=
PATH_CURRENT_SITE=/
SITE_ID_CURRENT_SITE=1
BLOG_ID_CURRENT_SITE=1
```

### 3. Composer — `roots/multisite-url-fixer`

`composer require roots/multisite-url-fixer`. Required for Bedrock's `/wp` layout;
rewrites network/admin URLs that would otherwise break. Installs to
`web/app/mu-plugins/multisite-url-fixer/` and is picked up by
`roots/bedrock-autoloader`. Commit `composer.json` + `composer.lock`.

### 4. `frankenphp.Caddyfile`

**Expected: no change.** Subdomain multisite routes by host; the existing
`try_files {path} {path}/ /index.php?{query}` and the `:80` catch-all already
cover all hosts. Verified locally; if a narrow gap appears it is fixed here.

### 5. Coolify / DNS (per new subsite)

For each `theme-x.<domain>`:
1. DNS record `theme-x.<domain>` → server IP.
2. Add `theme-x.<domain>` as an additional **Domain** on the Coolify app →
   Traefik issues a per-host cert via HTTP challenge (no wildcard).

The existing `X-Forwarded-Proto` shim in `application.php` already makes HTTPS
detection work behind Traefik — unchanged.

### 6. S3 uploads (Bunny)

`humanmade/s3-uploads` is multisite-aware: each subsite's media auto-prefixes
under `sites/<blog_id>/` in the bucket; blog 1 keeps its current paths. The
`web/app/mu-plugins/02-s3-uploads-bunny.php` path-style/checksum filter applies
network-wide unchanged.

## Data flow / conversion order

1. **Backup:** DB dump via WP-CLI/Coolify before any change. Uploads already
   live on Bunny (safe).
2. **Phase 1:** set `WP_ALLOW_MULTISITE=true` (Coolify) → deploy → wp-admin →
   Tools → Network Setup → choose **Sub-domains** → Install. This creates
   `wp_blogs`, `wp_site`, `wp_sitemeta` (additive; `wp_*` blog-1 tables
   untouched). Ignore the Apache `.htaccess` snippet it prints (Caddy stack);
   translate its `wp-config` constants into the env vars from §2.
3. **Verify:** main site loads and admin works unchanged.
4. **Phase 2:** add `MULTISITE=true` + `DOMAIN_CURRENT_SITE=<bare domain>`
   (others default) → deploy → network is live; Network Admin appears.
5. **Verify:** create a second site, activate a different theme, confirm a fresh
   upload lands under `sites/2/` in the Bunny bucket.

## Error handling / caveats

- **Worker mode:** current site is resolved per-request from `HTTP_HOST`, so
  worker mode is compatible. Risk is limited to a misbehaving plugin caching
  `blog_id` across requests — watch for it, not expected from core.
- **`DISALLOW_FILE_MODS`** (on in prod) blocks admin theme/plugin *uploads* but
  not *activation*; themes arrive via Composer/git, consistent with the repo.
- **Network Setup screen** emits Apache + wp-config snippets — we use neither
  verbatim (Caddy + env vars).

## Rollback

Remove `MULTISITE` / `WP_ALLOW_MULTISITE` env vars → redeploy → back to single
site. Network tables remain dormant and harmless. The Phase-1 DB dump is the
hard fallback.

## Testing

- **Local:** enable multisite in the local Docker setup first (Phase 1 → Phase
  2), create a second subsite, confirm admin, front-end, and a media upload all
  work before touching prod.
- **Prod:** follow the phased conversion with the verify steps above.

## Out of scope (YAGNI)

Wildcard certs, custom per-site TLDs / domain mapping, per-site plugin sets,
automated site provisioning. Revisit only if a concrete need appears.
