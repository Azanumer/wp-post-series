# Post Series

Group WordPress posts into a **series** (multi-part guides, tutorials, story arcs) with an automatic navigation box, prev/next links and a shortcode. No JavaScript, no settings bloat.

## Features

- **Series taxonomy** — assign posts to a series from the post editor sidebar (REST-ready, so it works in the block editor).
- **Automatic navigation box** — appended to single posts, listing every part in order with the current part highlighted.
- **Prev / next links** — jump to the previous or next part without leaving the page.
- **Shortcodes** — `[post_series]` renders the full box anywhere; `[series_nav]` renders only the prev/next links.
- **Settings** — box position (before / after / both), part-count display, under **Settings → Post Series**.

## Installation

1. Upload the `wp-post-series` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins** in WordPress admin.
3. Create a series under **Posts → Series**, then assign posts to it.

## Usage

- In the post editor, open the **Series** panel in the sidebar and tick a series.
- The box appears automatically on single posts. To place it manually instead, set the position to your liking or use the shortcode in a block:

```
[post_series]
[series_nav]
```

Pass an explicit post ID if you need another post's series: `[post_series id="42"]`.

## Filters

| Filter | Purpose |
|---|---|
| — | No filters in v1.0 — the settings page covers position and part count. |

## Uninstall

Deactivating keeps your series assignments. Deleting the plugin removes the `wpseries_settings` option; series terms stay as ordinary taxonomy terms.

## License

MIT — see [LICENSE](LICENSE).
