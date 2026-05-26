# Plugin TODO

Installed (via Composer, on `coolio-wp-franken-v1.1`):

- [x] **Astra** — `wpackagist-theme/astra`
- [x] **Elementor** — `wpackagist-plugin/elementor`
- [x] **Starter Templates** — `wpackagist-plugin/astra-sites`
- [x] **SureForms** — `wpackagist-plugin/sureforms`
- [x] **SureRank** — `wpackagist-plugin/surerank`
- [x] **WPForms Lite** — `wpackagist-plugin/wpforms-lite`

Still pending:

- [ ] **Ultimate Addons for Elementor** (Brainstorm Force) — premium, not on
      wpackagist. Needs a Composer endpoint or manual install. Skipped for now.

> **Note:** WP Mail SMTP is probably not needed — outbound mail is handled by the
> Coolify SMTP relay (postfix → Brevo). The mu-plugin in `web/app/mu-plugins/`
> already wires WordPress mail to the relay.
