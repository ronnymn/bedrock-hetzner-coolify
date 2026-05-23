# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A starter template for WordPress using [Bedrock](https://roots.io/bedrock/) — a modern WordPress boilerplate — deployed on Hetzner via Coolify. Media uploads are offloaded to Bunny.net object storage (S3-compatible). The web server is [FrankenWP](https://github.com/StephenMiracle/frankenwp) (FrankenPHP + Caddy).

## Local setup

```bash
composer install
cp .env.example .env
# Fill in .env — see comments in the file
```

Generate auth keys/salts at https://roots.io/salts.

## Common commands

```bash
# Install/update dependencies
composer install
composer update

# Add a free plugin from wpackagist.org
composer require wpackagist-plugin/<plugin-slug>

# Lint PHP with PHPCS + WordPress Coding Standards
vendor/bin/phpcs --standard=WordPress web/app/themes/ web/app/plugins/

# Migrate existing media uploads to Bunny.net
wp s3-uploads migrate-attachments

# Build and run the Docker image locally
docker build -t bedrock-starter .
docker run -p 8080:80 --env-file .env bedrock-starter
```

## Architecture

### Directory layout (Bedrock convention)
- `config/application.php` — all `Config::define()` calls; single source of truth for WordPress constants
- `web/` — document root served by Caddy/FrankenPHP
  - `web/wp/` — WordPress core (git-ignored, managed by Composer via `roots/wordpress`)
  - `web/app/` — replaces `wp-content/`
    - `plugins/` — Composer-managed plugins (wpackagist / premium)
    - `mu-plugins/` — must-use plugins (`s3-uploads` lands here)
    - `themes/` — custom themes live here
    - `uploads/` — local only; production uses Bunny.net
- `vendor/` — git-ignored, Composer-managed

### Configuration flow
Environment variables → `.env` → `vlucas/phpdotenv` → `config/application.php` → `Config::apply()` → WordPress constants. Never set constants directly; always use `Config::define()` in `application.php` or environment variables.

### Media storage
`humanmade/s3-uploads` intercepts WordPress upload calls and redirects them to Bunny.net's S3-compatible endpoint (`storage.bunnycdn.com`). Uploaded files are served back via the CDN Pull Zone URL (`S3_UPLOADS_BUCKET_URL`). Primary region: Falkenstein; replica: Johannesburg.

### Deploy flow
```
git push → GitHub webhook → Coolify builds Dockerfile (multi-stage)
         → composer install --no-dev → FrankenWP image → Hetzner
```
Coolify's Traefik handles TLS termination; the container runs plain HTTP on port 80.

### Performance stack
- FrankenPHP **worker mode** keeps PHP bootstrapped in memory (4 workers configured in `Caddyfile`)
- OPcache with `validate_timestamps=0` (aggressive; suits immutable container deploys)
- Redis object cache (`wpackagist-plugin/redis-cache`) — enabled when `REDIS_HOST` env var is set
- Autoptimize + WP Fastest Cache for asset/page caching

### Environment-dependent behaviour (`config/application.php`)
| Flag | `development` | `staging` | `production` |
|---|---|---|---|
| `WP_DEBUG` | on | off | off |
| `DISALLOW_FILE_MODS` | off | off | on |
| `FORCE_SSL_ADMIN` | off | off | on |
| `COMPRESS_CSS/JS` | off | off | on |
| `WP_AUTO_UPDATE_CORE` | true | minor | minor |

## Adding plugins

Always use Composer. Free plugins come from `wpackagist.org` (already registered as a repository). ACF Pro uses the `connect.advancedcustomfields.com` Composer endpoint (also registered). After adding, commit both `composer.json` and `composer.lock` — Coolify rebuilds on push.

## Environment variables

All runtime config is injected via environment variables. In production, set them in Coolify's **Environment** tab (never commit a real `.env`). See `.env.example` for the full list with comments.
