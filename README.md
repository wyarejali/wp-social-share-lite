# WP Social Share Lite

> Effortless social sharing for WordPress — By [Wyarej Ali](https://wyarejali.com)

[![Version](https://img.shields.io/badge/version-2.0.1-6366f1?style=flat-square)](https://github.com/wyarejali/wp-social-share-lite/releases)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=flat-square&logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb3?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green?style=flat-square)](LICENSE)

WP Social Share Lite automatically injects a fully customizable social sharing bar into your posts, pages, and custom post types — no shortcodes, no manual placement. Everything is controlled from a clean, modern settings dashboard.

---

## Features

### Auto-Injection

Automatically injects the share bar into your content based on your settings. No shortcode or template editing required.

### 11 Social Networks

Facebook, X (Twitter), LinkedIn, WhatsApp, Telegram, Pinterest, Reddit, Tumblr, VKontakte, Email, and Copy Link — all with official brand colors and SVG icons.

### Custom Link Button

Add a fully custom button with your own label, URL, and color — great for linking to a landing page, affiliate link, or any external destination.

### 6 Display Positions

- Before content
- After content
- Before & After content
- Floating left sidebar
- Floating right sidebar
- Sticky bottom bar

### Share Count Tracking

Share counts are tracked per-network via AJAX and stored as post meta. Counts are displayed on each button and as a total above the bar, with no external API dependency.

### Share Stats Dashboard

A dedicated **Share Stats** page in the admin shows total shares, per-network totals, and a sortable table of your top-shared posts with per-network breakdown.

### Live Preview

The settings page includes a live preview panel that renders your share bar in real time as you change settings — no page reload required.

### Design Controls

- **Button shape:** Square, Rounded, or Pill
- **Button size:** Small, Medium, or Large
- **Color scheme:** Brand colors, Monochrome, or fully Custom (with color pickers for background and text)

### Open Graph / SEO

Automatically injects Open Graph and Twitter Card meta tags for better social previews. Auto-detects and disables itself when a dedicated SEO plugin (AIOSEO, Yoast, RankMath) is active.

### Per-Post Override

A **Social Share Settings** meta box on every post and page editor lets you override the global settings — force show, force hide, or use global defaults. Also shows per-network share counts directly in the editor sidebar.

### Mobile Visibility Toggle

Optionally hide the share bar on mobile devices without touching any CSS.

### Exclude by Post ID

Enter a comma-separated list of post/page IDs to exclude from all injection.

---

## Screenshots

> _Screenshots will be added here._

---

## Installation

### From a ZIP file

1. Download the latest release ZIP from the [Releases](https://github.com/wyarejali/wp-social-share-lite/releases) page.
2. In your WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Install Now**.
4. Click **Activate Plugin**.

### From Source (development)

```bash
git clone https://github.com/wyarejali/wp-social-share-lite.git
```

Place the `wp-social-share-lite` folder in your `/wp-content/plugins/` directory and activate it from the WordPress admin.

---

## Requirements

| Requirement | Minimum |
| ----------- | ------- |
| WordPress   | 5.8     |
| PHP         | 7.4     |
| MySQL       | 5.6     |

---

## Configuration

After activating the plugin, go to **Social Share** in the WordPress admin sidebar.

### General Tab

| Setting          | Description                                        |
| ---------------- | -------------------------------------------------- |
| Enable Plugin    | Global on/off toggle                               |
| Post Types       | Select which post types display the share bar      |
| Display Position | Where to inject the bar (inline, floating, sticky) |
| Exclude Post IDs | Comma-separated IDs to skip                        |

### Social Networks

Drag to reorder the network buttons. Check/uncheck to enable or disable individual networks. A Custom Link Button section is available at the bottom.

### Display Options

Control the label text, icon style (icon only vs icon + label), share count display, and mobile visibility.

### Design

Choose button shape, size, and color scheme. The Custom color scheme exposes background and text color pickers.

### Open Graph / SEO

Enable or disable automatic Open Graph and Twitter Card meta tag injection. This setting is automatically locked when a known SEO plugin is detected.

---

## File Structure

```
wp-social-share-lite/
├── admin/
│   ├── class-wpssl-admin.php       # Admin menu, settings page, stats page
│   ├── css/
│   │   └── wpssl-admin.css         # Admin styles
│   └── js/
│       └── wpssl-admin.js          # Admin JS (drag/drop, preview, color pickers)
├── includes/
│   ├── class-wpssl-injector.php    # Auto-injects share bar via the_content filter
│   ├── class-wpssl-meta-box.php    # Per-post override meta box
│   ├── class-wpssl-networks.php    # Network definitions and SVG icons
│   ├── class-wpssl-og-tags.php     # Open Graph / Twitter Card meta tags
│   ├── class-wpssl-options.php     # Settings storage and defaults
│   ├── class-wpssl-renderer.php    # Builds share bar HTML
│   └── class-wpssl-share-count.php # AJAX share count tracking
├── public/
│   ├── css/
│   │   └── wpssl-public.css        # Frontend share bar styles
│   └── js/
│       └── wpssl-public.js         # Frontend JS (click tracking, copy link)
└── wp-social-share-lite.php        # Plugin bootstrap and constants
```

---

## Prefix & Naming Conventions

All functions, classes, hooks, options, and CSS classes use the `wpssl_` / `WPSSL_` / `wpssl-` prefix to avoid conflicts with other plugins.

| Type                  | Prefix                    |
| --------------------- | ------------------------- |
| PHP classes           | `WPSSL_`                  |
| PHP functions / hooks | `wpssl_`                  |
| WordPress options     | `wpssl_settings`          |
| Post meta keys        | `_wpssl_*`                |
| CSS classes           | `wpssl-`                  |
| JS globals            | `WPSSLData`, `WPSSLAdmin` |

---

## Hooks & Filters

WP Social Share Lite is built to be developer-friendly. The following hooks are available:

```php
// Filter the share bar HTML before output
add_filter( 'wpssl_share_bar_html', function( $html, $post_id ) {
    return $html;
}, 10, 2 );

// Filter which post types are eligible
add_filter( 'wpssl_post_types', function( $post_types ) {
    return $post_types;
} );
```

> Additional hooks and a developer API will be expanded in future releases.

---

## Changelog

See [RELEASE_NOTES.md](RELEASE_NOTES.md) for the full changelog.

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you'd like to change.

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request

---

## License

This plugin is licensed under the [GPL-2.0+](LICENSE) license, the same license as WordPress itself.

---

## Author

**Wyarej Ali**

- Website: [wyarejali.com](https://wyarejali.com)
- Plugin page: [wyarejali.com/plugins/wp-social-share-lite](https://wyarejali.com/plugins/wp-social-share-lite)
- GitHub: [@wyarejali](https://github.com/wyarejali)
