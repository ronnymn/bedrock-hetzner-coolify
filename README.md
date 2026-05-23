# WordPress Bedrock Starter
**Stack:** Hetzner + Coolify + Bedrock + Bunny.net

## Requirements
- PHP 8.2+
- Composer 2
- Docker (handled by Coolify)

## Local setup

```bash
git clone git@github.com:your-org/your-project.git
cd your-project
composer install
cp .env.example .env
# Fill in .env — see comments in the file
```

## Repo structure

```
├── config/
│   └── application.php     # All Config::define() calls
├── web/
│   ├── app/
│   │   ├── mu-plugins/     # Must-use plugins (s3-uploads installs here)
│   │   ├── plugins/        # Composer-managed plugins
│   │   ├── themes/         # Your custom theme lives here
│   │   └── uploads/        # Local only — production uses Bunny.net
│   ├── wp/                 # WordPress core — git-ignored, Composer-managed
│   └── index.php
├── vendor/                 # Git-ignored, Composer-managed
├── .env.example
├── composer.json
└── Dockerfile
```

## Adding plugins

```bash
# From wpackagist.org (free plugins)
composer require wpackagist-plugin/plugin-slug

# Commit composer.json and composer.lock
git add composer.json composer.lock
git commit -m "Add plugin-slug"
git push  # Coolify auto-deploys
```

## Bunny.net storage setup

1. Create a storage zone at https://dash.bunny.net/storage
2. Set primary region to **Falkenstein** (same DC as Hetzner)
3. Add **Johannesburg** as a replication region
4. Create a Pull Zone and connect it to the storage zone
5. (Optional) Add a custom CDN domain e.g. `cdn.yourdomain.com`
6. Copy the storage zone password and add to `.env` / Coolify environment variables

## Coolify environment variables

Copy all values from `.env.example` into your Coolify app's
**Environment** tab. Do not commit a real `.env` file.

## Deploy flow

```
git push → GitHub webhook → Coolify builds Dockerfile
         → composer install runs → deploys to Hetzner
```

## WP-CLI: migrate existing uploads to Bunny.net

```bash
wp s3-uploads migrate-attachments
```

## Notes

- `config_application.php` should be placed at `config/application.php` in your Bedrock repo
- Remember to rename it and place it in the correct directory
