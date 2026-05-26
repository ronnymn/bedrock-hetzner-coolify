# Plugin TODO

Plugins to install when building out the site (via `composer require wpackagist-plugin/<slug>`):

- [ ] **Elementor** — `wpackagist-plugin/elementor` — page builder
- [ ] **Starter Templates** — `wpackagist-plugin/astra-sites` — one-click Elementor starter sites
- [ ] **SureForms** — `wpackagist-plugin/sureforms` — form builder
- [ ] **SureRank** — `wpackagist-plugin/surerank` — SEO plugin
- [ ] **Ultimate Addons for Elementor** — `wpackagist-plugin/ultimate-addons-for-elementor` — Elementor widget pack
- [ ] **WPForms Lite** — `wpackagist-plugin/wpforms-lite` — alternative form builder

> **Note:** WP Mail SMTP is probably not needed — outbound mail is handled by the
> Coolify SMTP relay (postfix → Brevo). The mu-plugin in `web/app/mu-plugins/`
> already wires WordPress mail to the relay.
