# Glance

A classifieds theme for comparing ads. You scan the list and read each ad in a side panel without losing your place.

## Requirements

- Shopclass 7.0.0 or later
- PHP 8.0 or later
- No plugins needed. The home page map needs the Map search plugin (Shopclass 7.1).

## Install

1. Download `glance_X.Y.Z.zip` from the [releases](https://github.com/mindstellar/theme-glance/releases).
2. Go to **Appearance → Manage themes** and upload the zip.
3. Click **Activate**.

Or put this folder at `oc-content/themes/glance`.

## Set up

1. Go to **Appearance → Glance**:
   - **Logo**: about 36 px tall. Without a logo the site name shows.
   - **Accent colour**: for buttons, links and the open ad. Dark mode uses a lighter shade.
   - **List density**: Comfortable or Compact.
   - **Footer note**: one line under the site name. Leave it empty to hide it.
   - **Home page map**: a map of listings above the latest ads. Needs the Map search plugin, distance search on and listings with a map point.
2. Go to **Appearance → Manage widgets** to fill the **Under the header** and **Footer** areas.

## Features

- Ad list with a reading panel. Arrow keys move, Enter opens, Esc closes. Each ad keeps its own URL.
- On a phone, each ad opens in a bottom sheet.
- Works without JavaScript.
- Saved ads are kept on the device.
- Light, dark or auto colour scheme.
- JSON-LD on ad pages.
- With the Map search plugin, a pin on the map opens its ad in the reading panel.

## Customising

The theme owns its CSS in `css/`. Use a child theme or edit those files.

## For developers

- Hooks added by the theme:
  - `glance_item_booking` (action): a slot for a booking widget on the ad page.
  - `glance_messages_url` (filter): inbox link on a core without messaging. Return empty to hide it.
  - `glance_unread_count` (filter): unread count for that link.
- Core hooks fired: `search_form`, `search_ads_listing_top`, `search_ads_listing_medium` (every 6 ads), `home_before_featured`, `home_after_featured`, `home_before_listings`, `home_after_listings`, `item_sidebar`, `item_meta`, `item_detail`, `item_before_related`, `item_after_related`, `item_contact_form`, `footer`.
- Build: `npm install`, then `npm run build` for the minified CSS and JS.
- A `vX.Y.Z` tag runs the release workflow and publishes `glance_X.Y.Z.zip`.
- Strings use the text domain `glance`. The template is `languages/glance.pot`.
- On Apache, the theme's `.htaccess` sets long caching and compression. On Nginx, add this to your `server` block:

```nginx
gzip on;
gzip_types text/css application/javascript text/javascript image/svg+xml;
location ~* ^/oc-content/themes/glance/.+\.(css|js|woff2|svg|png|webp)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Support

Report problems at [github.com/mindstellar/theme-glance/issues](https://github.com/mindstellar/theme-glance/issues).

## License

GPL-3.0-or-later. See `LICENSE`. The font, Schibsted Grotesk, is under the SIL Open Font Licence 1.1 (`fonts/OFL.txt`). The icons, from Lucide, are under the ISC licence.
