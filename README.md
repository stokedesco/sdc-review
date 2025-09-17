# SDC GMB Review Badge

SDC GMB Review Badge is a lightweight WordPress plugin from [Stoke Design Co](https://stokedesign.co) that displays your Google Business Profile (GBP) rating anywhere on your site. Fetch reviews data via the Google Places Details API, cache it for performance, and customise the badge through an intuitive settings screen.

## Features

- Pull your live GBP star rating and total review count via the Google Places API.
- Cache API responses to stay within quota limits while keeping data fresh.
- Customise the number of stars, the star fill colour, and the text/icon accent colour.
- Accessible, responsive SVG badge markup that works in any theme.
- Shortcode aliases for backward compatibility with previous plugin versions.

## Requirements

- WordPress 6.0 or newer.
- A Google Cloud project with the Places API enabled.
- A Places Details API key with appropriate HTTP referrer restrictions.
- The Google Place ID for the business location you wish to display.

## Installation

1. Download or clone this repository into your WordPress `wp-content/plugins` directory.
2. Ensure the folder name is `sdc-gmb-review-badge` and that the main plugin file is `sdc-gmb-review-badge.php`.
3. Log into the WordPress admin dashboard and activate **SDC GMB Review Badge** from the Plugins screen.

## Configuration

1. In the WordPress admin area, navigate to **Settings → SDC GMB Review Badge**.
2. Enter your restricted Google Places API key.
3. Paste the Place ID for the Google Business Profile you want to showcase.
4. Adjust the optional styling controls:
   - **Number of Stars to Display** – any value from 1 to 10.
   - **Star Colour** – the fill colour for the rating stars.
   - **Text and Icon Colour** – the accent colour for the Google "G" icon and text.
   - **Cache Duration** – number of minutes to cache the API response (default 720 minutes / 12 hours).
5. Click **Save Changes**.

> 💡 You can find your Place ID with the [Place ID Finder](https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder) and create your API key inside the [Google Cloud Console](https://console.cloud.google.com/).

## Shortcode usage

Add the badge anywhere shortcodes are supported (pages, posts, widgets, block editor shortcode block, etc.). The primary shortcode is:

```text
[sdc_gmb_review_badge]
```

### Attributes

| Attribute       | Description                                                                 | Default (from settings) |
|-----------------|-----------------------------------------------------------------------------|-------------------------|
| `place_id`      | Override the configured Place ID for a single badge instance.               | Saved Place ID          |
| `api_key`       | Override the configured API key (use cautiously, the value is public).      | Saved API key           |
| `stars`         | Number of star icons to display (1–10).                                     | Saved star count        |
| `star_color`    | Hex colour used for the filled portion of the stars.                        | Saved star colour       |
| `accent_color`  | Hex colour used for the text and Google icon.                               | Saved accent colour     |
| `cache_minutes` | Minutes to cache the API response. Use `0` to bypass the cache temporarily. | Saved cache duration    |

#### Examples

```text
[sdc_gmb_review_badge]
[sdc_gmb_review_badge stars="5" star_color="#ff9900"]
[sdc_gmb_review_badge place_id="YOUR_PLACE_ID" api_key="YOUR_KEY" cache_minutes="60"]
```

For convenience, the plugin still recognises the legacy shortcodes `[sdc_gmb_badge]` and `[stoke_gbp_badge]`, both of which resolve to the same output.

## Styling tips

- The star and accent colours are managed via the settings page or shortcode attributes.
- Need more control? Target the `.sdc-gmb-review-badge` class in your theme or custom CSS to adjust spacing, typography, or layout.
- The badge uses inline SVG icons, so colours inherit from the accent colour you set.

## Caching & API usage

- Each badge request caches the Places API response in a WordPress transient. This prevents repeated API calls and helps you respect Google quota limits.
- Adjust the cache duration globally in the settings screen or per-shortcode using the `cache_minutes` attribute.
- Set `cache_minutes="0"` to skip caching for the next render (useful after receiving a new review).

## Troubleshooting

- **Reviews unavailable message** – confirm that the API key is valid, the Places API is enabled, and the Place ID is correct.
- **No stars displayed** – ensure the star colour is a valid hex value and that no aggressive caching plugin is stripping inline styles.
- **Quota errors** – reduce the cache duration temporarily to refresh the data, then raise it back to minimise API calls.

## Support

This is an open-source project maintained by [Stoke Design Co](https://stokedesign.co). For customisations or support packages, please get in touch via our website.

## Change log

See [CHANGELOG.md](CHANGELOG.md) for a full history of updates.
