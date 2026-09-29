# Dienstenoverzicht — WordPress Service Directory

> **Supporting portfolio project · WordPress/PHP · custom post type · taxonomy · AJAX filtering · CSV import/export**

**Developer profile:** [Andrew Baeten](https://github.com/Yolol100) · [Portfolio cases](https://andrewbaeten.nl/category/cases)

Dienstenoverzicht is a WordPress plugin for managing business services as structured content and presenting them through a searchable, filterable frontend directory. It combines an editor-friendly content model with a lightweight shortcode-based public interface.

## What it demonstrates

| Area | Implementation |
| --- | --- |
| Content architecture | Dedicated service custom post type, category taxonomy and structured service metadata |
| Front end | Reusable `[dienstenoverzicht]` shortcode with search and category filtering |
| Admin workflow | Service management, settings and controlled CSV import/export |
| Security | Nonces, capability checks, upload validation, sanitization and escaping |
| Data safety | Published-content export and conservative uninstall behaviour |
| Maintainability | WordPress-native APIs, scoped assets and separated runtime classes |
| Compatibility | Repository-native WordPress compatibility workflow |

## Usage

After activation:

1. Add services under **Diensten** in WordPress admin.
2. Organize them with the service category taxonomy.
3. Add the overview to a page with:

```text
[dienstenoverzicht]
```

Optionally start from a specific category:

```text
[dienstenoverzicht category="123"]
```

## CSV workflow

The plugin includes administrator-facing CSV import/export for controlled bulk maintenance. The current release adds upload checks, publish-only export behaviour and safer spreadsheet-formula round-tripping.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Current plugin version: **3.0.1**

See [`readme.txt`](readme.txt) for WordPress-specific installation notes and release history.

## Repository structure

```text
dienstenoverzicht-plugin.php   Plugin bootstrap
includes/                      Content model, admin, shortcode and import/export classes
css/                           Frontend/admin styling
js/                            Frontend behaviour
languages/                     Translation files
.github/workflows/             Compatibility checks
readme.txt                     WordPress plugin metadata
uninstall.php                  Conservative cleanup
```

## Portfolio context

This project represents a practical custom WordPress content solution: structured editorial data, a public search/filter experience and guarded bulk content operations in one maintainable plugin.

For larger portfolio examples, see [ACF Page Text Manager](https://github.com/Yolol100/ACF-Text-Manager), [Content Sync Manager](https://github.com/Yolol100/Content-Sync-Manager) and [SooCool for WooCommerce](https://github.com/Yolol100/soocool-for-woocommerce).

## License

GPL-2.0-or-later.
