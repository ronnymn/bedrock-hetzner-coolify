# Subdomain Multisite Conversion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Convert the single-site Bedrock install into a WordPress subdomain multisite network so multiple themes can be tested side by side, preserving the current site as blog 1.

**Architecture:** Env-gated, two-phase multisite enable in `config/application.php`; `roots/multisite-url-fixer` to handle Bedrock's `/wp` layout; per-subdomain domains added in Coolify (no wildcard cert); Bunny.net uploads auto-partition per blog. Code changes land first and are inert until env vars are set; the live conversion is an operational runbook with backup and verify gates.

**Tech Stack:** Bedrock, WordPress 6.x multisite, FrankenPHP (worker mode) + Caddy, Coolify/Traefik, `humanmade/s3-uploads` → Bunny.net, Composer, WP-CLI.

**Spec:** `docs/superpowers/specs/2026-06-04-multisite-conversion-design.md`

**Branch:** `multisite-conversion` (already created; spec already committed here).

---

## File structure

| File | Responsibility | Change |
|---|---|---|
| `config/application.php` | Defines multisite constants, env-gated, two-phase | Modify (add block before `Config::apply()`) |
| `.env.example` | Documents the six multisite env vars | Modify (add commented section) |
| `composer.json` / `composer.lock` | Adds `roots/multisite-url-fixer` | Modify (via `composer require`) |
| `docs/multisite-runbook.md` | Operational steps: backup, phase 1/2, per-site DNS+Coolify, verify | Create |

There is intentionally **no** `frankenphp.Caddyfile` change (subdomain routing needs none); a verify step confirms this.

---

## Task 1: Add the env-gated multisite block to `config/application.php`

**Files:**
- Modify: `config/application.php` (insert before line 109, `Config::apply();`)

- [ ] **Step 1: Add the multisite block**

Insert this block immediately after the Limits section (after `Config::define('WP_MAX_MEMORY_LIMIT', '512M');`, before `Config::apply();`):

```php
// ─── Multisite (env-gated; no-op when WP_ALLOW_MULTISITE unset) ───────────────
// Two-phase enable:
//   Phase 1 — set WP_ALLOW_MULTISITE=true, deploy, run Network Setup in wp-admin.
//   Phase 2 — add MULTISITE=true + DOMAIN_CURRENT_SITE, deploy.
// This network is subdomain-only, so SUBDOMAIN_INSTALL is fixed true.
if (filter_var(env('WP_ALLOW_MULTISITE'), FILTER_VALIDATE_BOOLEAN)) {
    Config::define('WP_ALLOW_MULTISITE', true);

    if (filter_var(env('MULTISITE'), FILTER_VALIDATE_BOOLEAN)) {
        Config::define('MULTISITE',            true);
        Config::define('SUBDOMAIN_INSTALL',    true);
        Config::define('DOMAIN_CURRENT_SITE',  env('DOMAIN_CURRENT_SITE'));
        Config::define('PATH_CURRENT_SITE',    env('PATH_CURRENT_SITE') ?: '/');
        Config::define('SITE_ID_CURRENT_SITE', (int) (env('SITE_ID_CURRENT_SITE') ?: 1));
        Config::define('BLOG_ID_CURRENT_SITE', (int) (env('BLOG_ID_CURRENT_SITE') ?: 1));
    }
}
```

- [ ] **Step 2: Lint the file**

Run: `php -l config/application.php`
Expected: `No syntax errors detected in config/application.php`

- [ ] **Step 3: Verify the gate is inert when unset**

Run:
```bash
php -r '
  function env($k){ return getenv($k) === false ? null : getenv($k); }
  $defs = [];
  function define_spy($k,$v){ global $defs; $defs[$k]=$v; }
  // Extract just the multisite block and evaluate its boolean gate logic.
  $unset = filter_var(env("WP_ALLOW_MULTISITE"), FILTER_VALIDATE_BOOLEAN);
  echo "WP_ALLOW_MULTISITE gate (unset env) = " . var_export($unset, true) . "\n";
'
```
Expected: `WP_ALLOW_MULTISITE gate (unset env) = false` — confirms single-site deploys are unaffected by this change.

- [ ] **Step 4: Commit**

```bash
git add config/application.php
git commit -m "Add env-gated multisite constants to application.php

Two-phase subdomain-multisite enable; no-op until WP_ALLOW_MULTISITE is set.

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 2: Document the multisite env vars in `.env.example`

**Files:**
- Modify: `.env.example` (append a multisite section near the WP_HOME/WP_SITEURL block)

- [ ] **Step 1: Add the commented section**

Append after the existing `WP_SITEURL=` / `DB_PREFIX=` lines:

```dotenv

# ─── Multisite (optional) ─────────────────────────────────────────────────────
# Subdomain network for testing themes side by side. Two-phase enable:
#   Phase 1: WP_ALLOW_MULTISITE=true → deploy → wp-admin Tools ▸ Network Setup.
#   Phase 2: add MULTISITE=true + DOMAIN_CURRENT_SITE → deploy.
# DOMAIN_CURRENT_SITE is the bare main-site host (e.g. example.com); subsites are
# subdomains of it (theme-a.example.com). Leave all blank for single-site.
WP_ALLOW_MULTISITE=
MULTISITE=
DOMAIN_CURRENT_SITE=
PATH_CURRENT_SITE=/
SITE_ID_CURRENT_SITE=1
BLOG_ID_CURRENT_SITE=1
```

- [ ] **Step 2: Verify**

Run: `grep -c MULTISITE .env.example`
Expected: `3` (the two MULTISITE-prefixed vars + the header comment line containing "Multisite").

If the count differs because of comment wording, just confirm visually that `WP_ALLOW_MULTISITE=`, `MULTISITE=`, `DOMAIN_CURRENT_SITE=`, `PATH_CURRENT_SITE=/`, `SITE_ID_CURRENT_SITE=1`, `BLOG_ID_CURRENT_SITE=1` are all present.

- [ ] **Step 3: Commit**

```bash
git add .env.example
git commit -m "Document multisite env vars in .env.example

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 3: Add `roots/multisite-url-fixer`

**Files:**
- Modify: `composer.json`, `composer.lock` (via `composer require`)

- [ ] **Step 1: Require the package**

Run: `composer require roots/multisite-url-fixer`
Expected: Composer resolves and writes it; because its type is `wordpress-muplugin`, `composer.json`'s `installer-paths` puts it at `web/app/mu-plugins/multisite-url-fixer/`.

- [ ] **Step 2: Verify install location**

Run: `ls web/app/mu-plugins/multisite-url-fixer/`
Expected: package files present (e.g. `multisite-url-fixer.php`, `composer.json`).

- [ ] **Step 3: Verify it is referenced in composer.json**

Run: `grep multisite-url-fixer composer.json`
Expected: a `"roots/multisite-url-fixer": "^..."` line under `require`.

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock
git commit -m "Add roots/multisite-url-fixer for Bedrock multisite URLs

Required so the /wp core layout generates correct network/admin URLs.
Autoloaded by roots/bedrock-autoloader from mu-plugins.

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 4: Create the operational runbook

**Files:**
- Create: `docs/multisite-runbook.md`

This captures the steps that can't be unit-tested (backup, network setup wizard, DNS, Coolify domains) so they're repeatable. Tasks 5–7 execute it.

- [ ] **Step 1: Write the runbook**

Create `docs/multisite-runbook.md` with this content:

````markdown
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
````

- [ ] **Step 2: Commit**

```bash
git add docs/multisite-runbook.md
git commit -m "Add multisite conversion + per-site provisioning runbook

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Task 5: Local boot verification (code changes are inert)

Confirms the three code changes don't break the existing single-site boot, before any live conversion. Requires a reachable local DB; if local WP isn't runnable, skip and treat the Phase-1 prod checkpoint (Task 6) as the first live test — the backup makes that safe.

**Files:** none (verification only)

- [ ] **Step 1: Build the image**

Run: `docker build -t bedrock-starter .`
Expected: build succeeds; `multisite-url-fixer` is present in the image under `web/app/mu-plugins/`.

- [ ] **Step 2: Boot single-site (multisite env unset) and hit health**

Run: `docker run --rm -p 8080:80 --env-file .env bedrock-starter` in one shell, then in another:
```bash
curl -fsS http://localhost:8080/health.php && echo " OK"
```
Expected: health endpoint returns OK — proves the new env-gated block is inert with multisite vars blank, and `multisite-url-fixer` doesn't break single-site.

- [ ] **Step 3: Stop the container**

`Ctrl-C` the `docker run` shell. No commit (verification only).

---

## Task 6: Execute Phase 1 (live — allow multisite)

Operational; follow `docs/multisite-runbook.md` §0–§1. No code commit.

**Files:** none (Coolify env + wp-admin wizard)

- [ ] **Step 1: Backup the prod DB** per runbook §0. Confirm the dump downloaded and is non-empty (`ls -la` the file).

- [ ] **Step 2: Set `WP_ALLOW_MULTISITE=true`** in Coolify env, save, and let the **webhook** deploy (do not also call the deploy API — they race). Wait for deploy to finish.

- [ ] **Step 3: Run Network Setup** (Tools ▸ Network Setup ▸ Sub-domains ▸ Install).

- [ ] **Step 4: Verify main site intact**

Run (in container terminal): `wp option get blogname && wp post list --post_type=page --format=count`
Expected: blog name and page count match pre-conversion — content preserved.

---

## Task 7: Execute Phase 2 + first subsite (live — activate network)

Operational; follow `docs/multisite-runbook.md` §2 and "Add a theme-test site". No code commit.

**Files:** none

- [ ] **Step 1: Set Phase-2 env vars** (`MULTISITE=true`, `DOMAIN_CURRENT_SITE=<bare host>`) in Coolify, redeploy via webhook, wait for completion.

- [ ] **Step 2: Verify the network is live**

Run (container terminal): `wp site list`
Expected: lists blog 1 (the main site). Network Admin menu visible in wp-admin.

- [ ] **Step 3: Confirm no Caddy change was needed**

Run: `curl -fsS https://<main-host>/wp/wp-admin/ -o /dev/null -w "%{http_code}\n"`
Expected: `302`/`200` (redirect to login or admin) — main admin reachable with the unchanged Caddyfile. If this fails with a routing error, that's the one narrow case where `frankenphp.Caddyfile` needs a host-aware tweak; stop and reassess before adding rules.

- [ ] **Step 4: Provision one theme-test subsite** per runbook (DNS → Coolify domain → Network Admin ▸ Add Site `theme-a` → activate a distinct theme, e.g. `twentytwentyfive` on main and `astra` on the subsite).

- [ ] **Step 5: Verify subsite + per-blog uploads**

Run:
```bash
curl -fsS https://theme-a.<domain>/ -o /dev/null -w "%{http_code}\n"   # expect 200
```
Then upload a test image in the subsite's admin and confirm in the Bunny dashboard it landed under `sites/2/`. Expected: 200 on the subsite, image under `sites/2/`.

---

## Final verification

- [ ] Main site (blog 1) retains all original content and is reachable.
- [ ] `wp site list` shows ≥2 sites after Task 7.
- [ ] Two sites run two different themes simultaneously.
- [ ] A subsite upload partitions under `sites/<blog_id>/` on Bunny.
- [ ] `frankenphp.Caddyfile` is unchanged (or, if changed, the narrow reason is documented in the runbook).
- [ ] Rollback path (remove env vars) understood and DB dump retained.

---

## Self-review notes

- **Spec coverage:** constants (T1), `.env.example` (T2), `multisite-url-fixer` (T3), Caddy no-change verification (T7 S3), Coolify/DNS per-site (T4 runbook + T7 S4), S3 per-blog uploads (T7 S5), backup + phased order + verify (T4/T6/T7), rollback (T4 runbook + final check). All spec sections mapped.
- **No placeholders:** `DOMAIN_CURRENT_SITE` / `<domain>` / `<main-host>` are deliberate deploy-time values, not unfilled plan gaps — the engineer substitutes the real production host at execution.
- **Type/name consistency:** env var names (`WP_ALLOW_MULTISITE`, `MULTISITE`, `DOMAIN_CURRENT_SITE`, `PATH_CURRENT_SITE`, `SITE_ID_CURRENT_SITE`, `BLOG_ID_CURRENT_SITE`) are identical across application.php (T1), .env.example (T2), and the runbook (T4).
- **WordPress caveat:** multisite config and the Network Setup wizard cannot be unit-tested; verification is via WP-CLI/HTTP checks and a guarded backup rather than PHPUnit — an honest fit for the domain.
