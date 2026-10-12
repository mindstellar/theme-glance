<picture>
  <source media="(prefers-color-scheme: dark)" srcset="assets/brand/glance-logo-dark.svg">
  <img src="assets/brand/glance-logo.svg" alt="Glance" width="240">
</picture>

# Glance

A Classifieds-only theme for comparing. Scan a list of ads and read each one in a side panel without losing your place.

## Requirements

- Shopclass 7.0.0 or later
- PHP 8.0 or later

## Install

- Upload the release zip under **Settings → Appearance**, or
- Put this folder at `oc-content/themes/glance`.

Then activate Glance under **Settings → Appearance**.

## Settings

Under **Appearance → Glance**:

- Logo (about 36 px tall)
- Accent colour
- List density: comfortable or compact
- Footer note

## Features

- Ad list with a reading panel. Arrow keys move, Enter opens, Esc closes. Each ad keeps its real URL.
- Phone: each ad opens in a bottom sheet.
- Works without JavaScript.
- Saved ads are kept on the device.
- Light, dark or auto colour scheme.
- Self-hosted font.
- JSON-LD on ad pages.
- Minified CSS and JS in releases.

## Hooks and filters

Added by the theme (name starting `glance_`):

- `glance_item_booking` (action): output slot for a booking widget on the ad page.
- `glance_messages_url` (filter): inbox URL. Return empty to hide messaging controls.
- `glance_unread_count` (filter): unread message count for the header badge.

Fired by the theme (core hooks):

- `search_form`: extra search form fields.
- `search_ads_listing_top`: top of home and search results.
- `search_ads_listing_medium`: between search results, every 6 ads.
- `home_before_listings`, `home_after_listings`: around the home list.
- `item_sidebar`: ad page sidebar.
- `item_meta`: ad page meta.
- `item_detail`: ad page body.
- `item_before_related`, `item_after_related`: around related ads.
- `item_contact_form`: inside the seller contact form.
- `footer`: footer area.

## Server tips

On Apache, the theme's `.htaccess` turns on long caching and compression for its CSS, JS and fonts. Nginx ignores it; add this to your `server` block instead:

```nginx
gzip on;
gzip_types text/css application/javascript text/javascript image/svg+xml;
location ~* ^/oc-content/themes/glance/.+\.(css|js|woff2|svg|png|webp)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Development

```bash
npm install
npm run build
```

Release: push a `vX.Y.Z` tag. The workflow builds the minified assets and publishes `glance_X.Y.Z.zip`.

Translation template: `languages/glance.pot` (domain `glance`).

## Licence

- Theme: GPL-3.0-or-later. See `LICENSE`.
- Font: Schibsted Grotesk, SIL Open Font Licence 1.1. See `fonts/OFL.txt`.
- Icons: Lucide, ISC licence.
