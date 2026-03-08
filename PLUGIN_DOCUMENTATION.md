# BannerCalc — Plugin Functions & Features Documentation

**Version:** 1.4.2  
**Requires:** WordPress 6.4+ · WooCommerce 8.0+ · PHP 8.0+  
**License:** GPL-2.0-or-later

---

## Table of Contents

1. [Overview](#1-overview)
2. [Architecture & File Structure](#2-architecture--file-structure)
3. [Core Plugin Bootstrap](#3-core-plugin-bootstrap)
4. [Plugin Singleton — `BannerCalc\Plugin`](#4-plugin-singleton--bannercalcplugin)
5. [Unit Converter — `BannerCalc\UnitConverter`](#5-unit-converter--bannercalcunitconverter)
6. [Attribute Manager — `BannerCalc\AttributeManager`](#6-attribute-manager--bannercalcattributemanager)
7. [Pricing Engine — `BannerCalc\PricingEngine`](#7-pricing-engine--bannercalcpricingengine)
8. [Cart Handler — `BannerCalc\CartHandler`](#8-cart-handler--bannercalccarthandler)
9. [Preset Seeder — `BannerCalc\PresetSeeder`](#9-preset-seeder--bannercalcpresetseeder)
10. [Admin Coordinator — `BannerCalc\Admin`](#10-admin-coordinator--bannercalcadmin)
11. [Admin Settings Page — `BannerCalc\Admin\SettingsPage`](#11-admin-settings-page--bannercalcadminsettingspage)
12. [Category Defaults — `BannerCalc\Admin\CategoryDefaults`](#12-category-defaults--bannercalcadmincategorydefaults)
13. [Product Metabox — `BannerCalc\Admin\ProductMetabox`](#13-product-metabox--bannercalcadminproductmetabox)
14. [Frontend Coordinator — `BannerCalc\Frontend`](#14-frontend-coordinator--bannercalcfrontend)
15. [Product Display — `BannerCalc\Frontend\ProductDisplay`](#15-product-display--bannercalcfrontendproductdisplay)
16. [Frontend JavaScript Controller](#16-frontend-javascript-controller)
17. [Frontend View Templates](#17-frontend-view-templates)
18. [Admin View Templates](#18-admin-view-templates)
19. [Data Architecture](#19-data-architecture)
20. [WooCommerce Hooks Reference](#20-woocommerce-hooks-reference)
21. [Price Calculation Formula](#21-price-calculation-formula)
22. [Uninstall Procedure](#22-uninstall-procedure)
23. [Feature Summary](#23-feature-summary)

---

## 1. Overview

BannerCalc is a WooCommerce plugin that replaces the default variation-based product configuration system with a unified pricing engine. Products are kept as **Simple** type — no WooCommerce variations are created. All pricing is calculated dynamically using area-based formulas and attribute modifiers.

### What BannerCalc Replaces

| WooCommerce Default            | BannerCalc Replacement                           |
| ------------------------------ | ------------------------------------------------ |
| Attributes tab in product editor | Custom metabox with toggle + pricing config     |
| Variations tab in product editor | Eliminated — no variations needed               |
| Frontend `<select>` dropdowns  | Styled button/card/toggle/dropdown selectors     |
| Fixed variation prices         | Dynamic pricing engine (area × rate + add-ons)   |
| Single unit display            | Multi-unit selector with live conversion          |

### Core Principle

All attribute selections, dimensions, and calculated prices are stored as cart item meta and order item meta. The plugin reads WooCommerce's existing attribute taxonomy as a data source but completely replaces both the admin product editor UI and the frontend product page UI with custom interfaces.

---

## 2. Architecture & File Structure

```
bannercalc/
├── bannercalc.php                        # Main plugin file, bootstrap, activation/deactivation
├── readme.txt                            # WordPress readme
├── uninstall.php                         # Cleanup on plugin deletion
├── DOCUMENTATION.md                      # This file
│
├── includes/
│   ├── class-bannercalc.php              # Core singleton — wires all hooks
│   ├── class-admin.php                   # Admin-side coordinator, asset loading
│   ├── class-frontend.php                # Frontend-side coordinator, asset loading
│   ├── class-pricing-engine.php          # All price calculation logic
│   ├── class-cart-handler.php            # Cart/checkout/order integration
│   ├── class-unit-converter.php          # Unit conversion utilities
│   ├── class-attribute-manager.php       # Read WooCommerce taxonomy data
│   └── class-preset-seeder.php           # UK popular sizes reference data + AJAX import
│
├── admin/
│   ├── class-settings-page.php           # Top-level menu, submenu pages, global settings
│   ├── class-category-defaults.php       # Category-level attribute/pricing config
│   ├── class-product-metabox.php         # Per-product metabox on product edit screen
│   ├── views/
│   │   ├── dashboard.php                 # Dashboard overview page template
│   │   ├── settings-page.php             # Global settings form template
│   │   ├── categories-page.php           # Category defaults management template
│   │   ├── category-defaults.php         # Category fields on WP term-edit screen
│   │   └── product-metabox.php           # Product metabox HTML template
│   ├── css/
│   │   └── admin.css                     # Admin panel styles (branded)
│   └── js/
│       └── admin.js                      # Admin panel interactivity
│
├── frontend/
│   ├── class-product-display.php         # Renders configurator on single product page
│   ├── views/
│   │   ├── configurator.php              # Main configurator wrapper + hidden fields
│   │   ├── size-selector.php             # Preset popular sizes + custom size UI
│   │   ├── unit-selector.php             # Unit toggle buttons (mm/cm/in/ft/m)
│   │   ├── attribute-selector.php        # Generic attribute UI (pills/toggle/dropdown)
│   │   └── price-display.php             # Live price breakdown block
│   ├── css/
│   │   └── frontend.css                  # Frontend configurator styles (branded)
│   └── js/
│       └── frontend.js                   # State management, unit conversion, price calc, validation
│
├── assets/
│   └── icons/                            # SVG icons for attribute options (optional)
│
└── brand-plugin-dev-plan/                # Development reference docs (not shipped)
```

---

## 3. Core Plugin Bootstrap

**File:** `bannercalc.php`

### Constants Defined

| Constant                    | Value                            | Description                            |
| --------------------------- | -------------------------------- | -------------------------------------- |
| `BANNERCALC_VERSION`        | `'1.4.2'`                        | Current plugin version                 |
| `BANNERCALC_PLUGIN_FILE`    | `__FILE__`                        | Absolute path to main plugin file      |
| `BANNERCALC_PLUGIN_DIR`     | `plugin_dir_path(__FILE__)`       | Plugin directory path (with trailing /) |
| `BANNERCALC_PLUGIN_URL`     | `plugin_dir_url(__FILE__)`        | Plugin URL (with trailing /)           |
| `BANNERCALC_PLUGIN_BASENAME`| `plugin_basename(__FILE__)`       | Plugin basename for hooks              |

### Functions

#### `bannercalc_activate()`
**Hook:** `register_activation_hook`

Sets default global settings in `wp_options` if they don't exist:
- `enabled` → `true`
- `currency_symbol` → `'£'`
- `default_unit` → `'ft'`
- `available_units` → `['mm', 'cm', 'inch', 'ft', 'm']`
- `price_display_decimals` → `2`

Flushes rewrite rules.

#### `bannercalc_deactivate()`
**Hook:** `register_deactivation_hook`

Flushes rewrite rules on deactivation.

#### `bannercalc_check_dependencies()`
Verifies WooCommerce is installed and active. Shows an admin error notice if missing. Returns `bool`.

#### `bannercalc_init()`
**Hook:** `plugins_loaded` (priority 20)

Main initialisation function:
1. Checks WooCommerce dependency
2. Loads core classes (`Plugin`, `UnitConverter`, `AttributeManager`, `PricingEngine`, `CartHandler`, `PresetSeeder`)
3. Loads admin classes if `is_admin()` (`Admin`, `SettingsPage`, `CategoryDefaults`, `ProductMetabox`)
4. Loads frontend classes if not admin or doing AJAX (`Frontend`, `ProductDisplay`)
5. Boots the plugin singleton via `BannerCalc\Plugin::instance()`

### WooCommerce HPOS Compatibility

Declared via `before_woocommerce_init` hook using `FeaturesUtil::declare_compatibility('custom_order_tables', ...)`. Ensures compatibility with WooCommerce High-Performance Order Storage.

---

## 4. Plugin Singleton — `BannerCalc\Plugin`

**File:** `includes/class-bannercalc.php`  
**Namespace:** `BannerCalc`

Core plugin orchestrator using the singleton pattern.

### Properties

| Property      | Type               | Description                       |
| ------------- | ------------------ | --------------------------------- |
| `$instance`   | `Plugin` (static)  | Singleton instance                |
| `$admin`      | `Admin\|null`      | Admin coordinator (null on frontend) |
| `$frontend`   | `Frontend\|null`   | Frontend coordinator (null in admin) |
| `$pricing`    | `PricingEngine`    | Pricing calculation engine        |
| `$cart`       | `CartHandler`      | Cart/checkout/order handler       |
| `$units`      | `UnitConverter`    | Unit conversion utilities         |
| `$attributes` | `AttributeManager` | WooCommerce attribute reader      |

### Methods

#### `instance(): self` (static)
Returns the singleton instance, creating it on first call.

#### `ajax_get_config(): void`
**AJAX Actions:** `wp_ajax_bannercalc_get_config`, `wp_ajax_nopriv_bannercalc_get_config`

Returns the resolved (merged) product config as JSON for a given `product_id` query parameter. Works for both authenticated and guest users.

#### `get_product_config(int $product_id): array`
Returns the fully resolved configuration for a product by:
1. Finding the product's top-level parent category
2. Loading category-level config from term meta (`_bannercalc_config`)
3. Loading per-product overrides from post meta (`_bannercalc_product_config`)
4. Merging overrides onto category defaults using `merge_config()`

If no product override exists (or `override_enabled` is false), returns the category config as-is.

#### `is_enabled_for_product(int $product_id): bool`
Returns `true` if BannerCalc is enabled for the given product (either via category config or product override). Checks that the resolved config has both a non-empty array and `enabled` set to `true`.

#### `get_settings(): array` (static)
Returns global plugin settings from `wp_options` (`bannercalc_settings`), merged with defaults:
```php
[
    'enabled'                => true,
    'currency_symbol'        => '£',
    'default_unit'           => 'ft',
    'available_units'        => ['mm', 'cm', 'inch', 'ft', 'm'],
    'price_display_decimals' => 2,
]
```

#### `merge_config(array $base, array $override): array` (private)
Deep-merges category defaults with per-product overrides:
- **Scalar fields** (sizing_mode, rates, dimensions, minimum_charge): product value wins
- **Available units**: product value replaces entirely
- **Disabled attributes**: Removed from the enabled list
- **Attribute pricing overrides**: Merged at the individual value/pricing_type/default_value/required level
- **Preset sizes**: Product value replaces entirely

---

## 5. Unit Converter — `BannerCalc\UnitConverter`

**File:** `includes/class-unit-converter.php`  
**Namespace:** `BannerCalc`

Handles all measurement conversions using metres as the canonical internal unit.

### Constants

| Constant       | Type    | Description                                  |
| -------------- | ------- | -------------------------------------------- |
| `TO_METRES`    | `array` | Conversion factors: `mm → 0.001`, `cm → 0.01`, `inch → 0.0254`, `ft → 0.3048`, `m → 1.0` |
| `SQFT_PER_SQM` | `float` | `10.7639` — square feet per square metre     |

### Methods

#### `to_metres(float $value, string $from_unit): float`
Converts a value from any supported unit to metres.

#### `from_metres(float $value_m, string $to_unit): float`
Converts a value in metres to any supported unit.

#### `convert(float $value, string $from_unit, string $to_unit): float`
Converts between any two supported units (short-circuits if both units are the same).

#### `area_sqft(float $width_m, float $height_m): float`
Calculates area in square feet from metre dimensions: `(width × height) × 10.7639`

#### `area_sqm(float $width_m, float $height_m): float`
Calculates area in square metres from metre dimensions: `width × height`

#### `get_unit_labels(): array` (static)
Returns all supported units with human-readable names:
```php
['mm' => 'Millimetres (mm)', 'cm' => 'Centimetres (cm)', 'inch' => 'Inches (in)', 'ft' => 'Feet (ft)', 'm' => 'Metres (m)']
```

#### `get_unit_abbr(string $unit): string` (static)
Returns the short abbreviation for a unit (e.g., `'inch'` → `'in'`).

#### `is_valid_unit(string $unit): bool` (static)
Returns `true` if the given unit string is recognised.

---

## 6. Attribute Manager — `BannerCalc\AttributeManager`

**File:** `includes/class-attribute-manager.php`  
**Namespace:** `BannerCalc`

Reads WooCommerce's existing attribute taxonomy system. Does **not** create its own attribute storage.

### Methods

#### `get_all_attributes(): array`
Returns all registered WooCommerce product attributes, keyed by taxonomy slug (e.g., `pa_hemming`). Each entry contains:
- `id` — Attribute ID
- `name` — Human-readable label
- `slug` — Attribute slug
- `taxonomy` — Full taxonomy name (e.g., `pa_hemming`)
- `type` — Attribute type

#### `get_attribute_terms(string $taxonomy): array`
Returns all terms for a given attribute taxonomy. Each term includes:
- `term_id`
- `name` — Human-readable label
- `slug` — URL-safe slug

Returns terms ordered by `menu_order ASC`, including empty terms.

#### `get_product_attribute_terms(int $product_id, string $taxonomy): array`
Returns only the terms assigned to a specific product for a given taxonomy.

#### `term_exists(string $slug, string $taxonomy): bool`
Validates that a specific term slug exists in a taxonomy.

#### `get_term_label(string $slug, string $taxonomy): string`
Returns the human-readable name for a term, falling back to the slug if not found.

---

## 7. Pricing Engine — `BannerCalc\PricingEngine`

**File:** `includes/class-pricing-engine.php`  
**Namespace:** `BannerCalc`

Central price calculation logic. Used server-side for authoritative pricing (cart/checkout) — the frontend JS mirrors this for live display only.

### Constructor

```php
public function __construct(UnitConverter $units)
```

Receives a `UnitConverter` instance for area calculations.

### Methods

#### `calculate(array $config, float $width_m, float $height_m, array $selected_attributes, ?array $preset = null): array`

The core pricing method. Returns:

```php
[
    'area_sqft'        => float,  // Area in square feet (rounded to 4 decimals)
    'area_sqm'         => float,  // Area in square metres (rounded to 4 decimals)
    'base_price'       => float,  // Base price from area × rate (rounded to 2 decimals)
    'addons'           => array,  // Per-attribute addon breakdown
    'addons_total'     => float,  // Sum of all addon prices (rounded to 2 decimals)
    'calculated_price' => float,  // base_price + addons_total (rounded to 2 decimals)
]
```

**Calculation steps:**
1. Calculate area in sqft and sqm from metre dimensions
2. `base_price = area_sqft × area_rate_sqft`
3. Apply minimum charge if base_price is below threshold
4. Apply preset price override if a preset with a non-null `price` was selected
5. For each selected attribute, compute addon based on `pricing_type`:
   - **`fixed`** — flat amount added
   - **`per_sqft`** — modifier × area_sqft
   - **`percentage`** — modifier% of base_price
6. `calculated_price = base_price + addons_total`

#### `validate_dimensions(array $config, float $width_m, float $height_m): array`

Validates dimensions against min/max constraints in the config. Returns an array of error strings (empty = valid).

Checks:
- Width and height must be positive
- Width >= `min_width_m`
- Height >= `min_height_m`
- Width <= `max_width_m`
- Height <= `max_height_m`

---

## 8. Cart Handler — `BannerCalc\CartHandler`

**File:** `includes/class-cart-handler.php`  
**Namespace:** `BannerCalc`

Manages all WooCommerce cart, checkout, and order integration.

### Constructor

```php
public function __construct(PricingEngine $pricing)
```

Receives a `PricingEngine` instance and registers all WooCommerce hooks.

### Methods

#### `add_cart_item_data(array $cart_item_data, int $product_id, int $variation_id): array`
**Filter:** `woocommerce_add_cart_item_data`

When a BannerCalc-configured product is added to cart:
1. Reads and sanitises submitted `bannercalc` POST data
2. Converts raw dimensions to metres using the selected unit
3. Matches preset if `sizing_mode === 'preset'`
4. **Recalculates price server-side** (never trusts frontend price)
5. Builds a human-readable `size_label`
6. Stores the full `bannercalc` data array in the cart item

**Stored cart item data:**
```php
[
    'sizing_mode'      => 'custom'|'preset',
    'preset_slug'      => string|'',
    'unit'             => string,
    'width_raw'        => float,
    'height_raw'       => float,
    'width_m'          => float,
    'height_m'         => float,
    'size_label'       => string,
    'area_sqft'        => float,
    'selected_attrs'   => ['pa_hemming' => 'yes', ...],
    'base_price'       => float,
    'addons_total'     => float,
    'calculated_price' => float,
]
```

#### `set_cart_item_prices(\WC_Cart $cart): void`
**Action:** `woocommerce_before_calculate_totals` (priority 20)

Overrides the WooCommerce product price with the `calculated_price` from BannerCalc data for every cart item that has it. Ensures WooCommerce uses the plugin's calculated price for totals, tax, and payment.

#### `display_cart_item_data(array $item_data, array $cart_item): array`
**Filter:** `woocommerce_get_item_data`

Displays configuration summary under each cart item:
- **Size** — e.g., "2.5ft × 4ft" or preset label
- **Area** — e.g., "10.00 sqft"
- **Each selected attribute** — with human-readable label and term name

#### `save_order_item_meta(\WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order): void`
**Action:** `woocommerce_checkout_create_order_line_item`

Persists the complete `bannercalc` cart item data as order item meta under key `_bannercalc_data`. This ensures the configuration survives after the cart is cleared.

#### `display_order_item_meta(int $item_id, \WC_Order_Item $item, \WC_Order $order, bool $plain_text): void`
**Action:** `woocommerce_order_item_meta_end`

Renders the BannerCalc configuration on:
- Admin order detail page
- Customer order confirmation emails (HTML and plain text)
- Customer "My Account > Orders" page

Displays: Size, Area, and all selected attributes with labels.

---

## 9. Preset Seeder — `BannerCalc\PresetSeeder`

**File:** `includes/class-preset-seeder.php`  
**Namespace:** `BannerCalc`

Provides UK popular banner and print sizes as reference data for quick import into category configurations.

### Static Methods

#### `register(): void`
Registers two AJAX handlers:
- `wp_ajax_bannercalc_import_presets`
- `wp_ajax_bannercalc_get_seed_data`

#### `ajax_get_seed_data(): void`
**AJAX Action:** `bannercalc_get_seed_data`

Accepts a `cat_id` POST parameter, looks up the category's slug, and returns matching seed data. Uses nonce verification (`bannercalc_admin`) and capability check (`manage_woocommerce`).

Returns: `{ category, slug, count, sizes[] }`

#### `get_sizes_for_slug(string $slug): array`
Matches a category slug to seed data using:
1. **Exact match** first
2. **Keyword fuzzy matching** — e.g., `'vinyl'` or `'pvc'` → `'vinyl-banners'` data

**Keyword map:** vinyl, pvc → vinyl-banners · mesh → mesh-banners · roller, roll-up, pull-up → roller-banners · backdrop, pop-up → backdrop-stands · poster → posters · sticker, label → stickers-labels · flyer, leaflet → flyers-leaflets

#### `get_all_seed_data(): array`
Returns the complete reference dataset, keyed by product type slug.

**Included product types and their sizes:**

| Category           | Unit | Example Sizes                                     |
| ------------------ | ---- | ------------------------------------------------- |
| Vinyl (PVC) Banners | ft  | 3×2, 4×2, 4×3, 6×2, 6×3, 8×2, 8×3, 8×4, 10×3, 12×3 |
| Mesh Banners       | ft   | 4×2, 6×3, 8×3, 8×4, 10×4, 12×4, 16×5, 20×6      |
| Roller Banners     | mm   | 600×1600, 800×2000, 850×2000, 1000×2000, 1200×2000, 1500×2000, 2000×2000 |
| Backdrop Stands    | mm   | 2000×2000, 2400×2000, 3000×2000, 3000×2250, etc. |
| Posters            | mm   | A5, A4, A3, A2, A1, A0, UK Quad, US 20×30        |
| Stickers & Labels  | mm   | Circle 25/37/51/76mm, Square 50/75mm, Rectangles  |
| Flyers & Leaflets  | mm   | A6, A5, A4, DL, Square sizes                     |

Each size entry includes: `label`, `width`, `height`, `unit`, `description`, `popularity` (1–5).

---

## 10. Admin Coordinator — `BannerCalc\Admin`

**File:** `includes/class-admin.php`  
**Namespace:** `BannerCalc`

Loads all admin components and manages admin asset enqueueing.

### Properties

| Property             | Type                            |
| -------------------- | ------------------------------- |
| `$settings_page`     | `Admin\SettingsPage`            |
| `$category_defaults` | `Admin\CategoryDefaults`        |
| `$product_metabox`   | `Admin\ProductMetabox`          |

### Methods

#### `enqueue_assets(string $hook_suffix): void`
**Action:** `admin_enqueue_scripts`

Enqueues admin CSS and JS only on relevant pages:
- BannerCalc admin pages
- Product edit screen
- Shop order screen
- Product category edit screen

**Assets loaded:**
- **Google Fonts:** Outfit (display), Plus Jakarta Sans (body), IBM Plex Mono (mono)
- **CSS:** `admin/css/admin.css`
- **JS:** `admin/js/admin.js` (depends on `jquery`, `wp-util`)

Localises the admin script with `bannercalcAdmin` object containing `ajaxUrl` and `nonce`.

---

## 11. Admin Settings Page — `BannerCalc\Admin\SettingsPage`

**File:** `admin/class-settings-page.php`  
**Namespace:** `BannerCalc\Admin`

Manages the top-level BannerCalc admin menu and all submenu pages.

### Menu Structure

Registers a top-level admin menu (position 56, after WooCommerce) with a custom SVG banner/flag icon and three submenu pages:

| Submenu        | Slug                      | Capability            | Renderer              |
| -------------- | ------------------------- | --------------------- | --------------------- |
| Dashboard      | `bannercalc`              | `manage_woocommerce`  | `render_dashboard()`  |
| Settings       | `bannercalc-settings`     | `manage_woocommerce`  | `render_settings()`   |
| Category Defaults | `bannercalc-categories` | `manage_woocommerce`  | `render_categories()` |

### Methods

#### `register_menu(): void`
**Action:** `admin_menu`

Registers the top-level menu and all three submenu pages.

#### `register_settings(): void`
**Action:** `admin_init`

Registers the `bannercalc_settings` option with the WordPress Settings API using `bannercalc_settings_group`.

#### `sanitize_settings(array $input): array`
**Sanitize callback** for WordPress Settings API.

Sanitises:
- `enabled` — boolean
- `currency_symbol` — `sanitize_text_field()`
- `default_unit` — `sanitize_text_field()`
- `price_display_decimals` — `absint()`
- `available_units` — Intersected with allowed values `['mm', 'cm', 'inch', 'ft', 'm']`; defaults to `['ft']` if empty

#### `render_dashboard(): void`
Renders the Dashboard page (includes `admin/views/dashboard.php`). Shows:
- Plugin status (Active/Disabled)
- Configured category count
- Default unit and currency
- Quick links to Settings, Category Defaults, and Products
- List of active categories

#### `render_settings(): void`
Renders the Global Settings form (includes `admin/views/settings-page.php`). Fields:
- Enable/Disable plugin globally (checkbox)
- Currency symbol (text input)
- Default measurement unit (dropdown)
- Available units for customers (multi-checkbox)
- Price display decimals (number input, 0–4)

#### `render_categories(): void`
Renders the Category Defaults management page (includes `admin/views/categories-page.php`).

---

## 12. Category Defaults — `BannerCalc\Admin\CategoryDefaults`

**File:** `admin/class-category-defaults.php`  
**Namespace:** `BannerCalc\Admin`

Manages BannerCalc configuration at the product category level.

### Hooks Registered

| Hook                                        | Purpose                                   |
| ------------------------------------------- | ----------------------------------------- |
| `product_cat_edit_form_fields` (priority 20) | Render BannerCalc fields on category edit |
| `edited_product_cat`                         | Save category config on term update       |
| `wp_ajax_bannercalc_save_category_config`    | AJAX category config save                 |

### Methods

#### `render_category_fields(\WP_Term $term): void`
Renders BannerCalc configuration fields on the WordPress product category edit screen. Loads existing config from term meta `_bannercalc_config`.

#### `save_category_fields(int $term_id): void`
Saves BannerCalc config when a product category is updated. Requires `manage_woocommerce` capability. Sanitises input and stores as term meta.

#### `ajax_save_category_config(): void`
AJAX endpoint for saving category config from the unified settings page. Validates nonce (`bannercalc_admin`) and capability.

#### `sanitize_category_config(array $raw): array` (private)
Sanitises the full category configuration:
- `enabled` — boolean
- `sizing_mode` — sanitize_text_field
- `default_unit` — sanitize_text_field
- `area_rate_sqft`, `area_rate_sqm`, `minimum_charge` — float cast
- `min_width_m`, `min_height_m`, `max_width_m`, `max_height_m` — float cast
- `available_units` — intersected with allowed list
- `enabled_attributes` — array of sanitize_text_field
- `attribute_pricing` — passed through (deep sanitisation pending)
- `preset_sizes` — passed through (deep sanitisation pending)

### Category Config Fields (via categories-page.php)

The admin categories page provides a split-panel layout:
- **Left panel:** Category list with active/inactive badges
- **Right panel:** Full configuration form for the selected category

**Configuration options include:**
- Enable/disable BannerCalc for category
- Sizing mode (Preset Only / Custom Only / Preset + Custom / None)
- Default unit for category
- Available units (multi-checkbox)
- Area rate per sqft and per sqm
- Minimum charge
- Dimension constraints (min/max width/height in a configurable unit, stored internally in metres)
- Enabled WooCommerce attributes (multi-checkbox)
- Per-attribute pricing configuration (pricing type + per-term modifiers)
- Preset sizes repeater (label, dimensions, unit, price override, description, popularity)
- "Import Popular Sizes" button (triggers `PresetSeeder`)

---

## 13. Product Metabox — `BannerCalc\Admin\ProductMetabox`

**File:** `admin/class-product-metabox.php`  
**Namespace:** `BannerCalc\Admin`

Provides per-product BannerCalc configuration overrides on the WooCommerce product edit screen.

### Hooks Registered

| Hook                | Purpose                          |
| ------------------- | -------------------------------- |
| `add_meta_boxes`    | Register the metabox             |
| `save_post_product` | Save metabox data on post save   |

### Methods

#### `register_metabox(): void`
Registers a metabox titled **"BannerCalc Configuration"** on the product edit screen (`normal` context, `high` priority).

#### `render_metabox(\WP_Post $post): void`
Renders the metabox content:
1. Outputs a nonce field for security
2. Loads per-product config from post meta `_bannercalc_product_config`
3. Loads the fully resolved config (category + overrides) via `Plugin::get_product_config()`
4. Includes the `admin/views/product-metabox.php` template

#### `save_metabox(int $post_id): void`
Saves metabox data on product save:
1. Verifies nonce (`bannercalc_product_metabox`)
2. Skips autosave
3. Checks `edit_post` capability
4. If no `bannercalc_product` data submitted, deletes the meta entirely
5. Otherwise, sanitises and saves to `_bannercalc_product_config`

#### `sanitize_product_config(array $raw): array` (private)
Sanitises per-product override config. Only stores overridden fields:
- `override_enabled` — boolean (if false, returns immediately with only this field)
- `sizing_mode` — sanitize_text_field
- `area_rate_sqft` — float cast
- `disabled_attributes` — array of sanitize_text_field
- `attribute_pricing_overrides` — passed through (deep sanitisation pending)
- `preset_sizes` — passed through (deep sanitisation pending)

---

## 14. Frontend Coordinator — `BannerCalc\Frontend`

**File:** `includes/class-frontend.php`  
**Namespace:** `BannerCalc`

Loads frontend components and manages asset enqueueing on product pages.

### Properties

| Property           | Type                             |
| ------------------ | -------------------------------- |
| `$product_display` | `Frontend\ProductDisplay`        |

### Methods

#### `enqueue_assets(): void`
**Action:** `wp_enqueue_scripts`

Only enqueues on single product pages where BannerCalc is enabled.

**Assets loaded:**
- **Google Fonts:** Outfit, Plus Jakarta Sans, IBM Plex Mono
- **Dashicons** — for attribute icons
- **CSS:** `frontend/css/frontend.css` (cache-busted with file modification timestamp)
- **JS:** `frontend/js/frontend.js` (depends on `jquery`, cache-busted)

Localises the frontend script with `bannercalcFrontend` object:
```javascript
{
    ajaxUrl: '...',
    nonce: '...',
    productId: 123,
    currencySymbol: '£',
    decimals: 2
}
```

---

## 15. Product Display — `BannerCalc\Frontend\ProductDisplay`

**File:** `frontend/class-product-display.php`  
**Namespace:** `BannerCalc\Frontend`

The main frontend class — renders the configurator and manages product page behaviour.

### Hooks Registered

| Hook                                            | Priority | Purpose                                         |
| ----------------------------------------------- | -------- | ----------------------------------------------- |
| `woocommerce_before_add_to_cart_button`          | 10       | Render configurator (Cards 1 & 2)               |
| `woocommerce_before_add_to_cart_button`          | 11       | Render extras card (design accordion + pricing)  |
| `woocommerce_before_add_to_cart_quantity`         | 1        | Open quantity flex row wrapper                   |
| `woocommerce_after_add_to_cart_button`            | 99       | Close quantity flex row + extras card wrappers   |
| `woocommerce_product_get_price`                   | 10       | Ensure purchasable price for BannerCalc products |
| `woocommerce_product_get_regular_price`           | 10       | Same as above                                    |
| `woocommerce_get_price_html`                      | 10       | Replace default price HTML                       |
| `woocommerce_before_single_product`               | —        | Suppress WC variation form                       |
| `woocommerce_product_data_tabs`                   | —        | Stub: hide variations tab                        |

### Methods

#### `render_configurator(): void`
Renders the main BannerCalc configurator on the product page. Only renders for BannerCalc-enabled products.

**Outputs:**
1. A wrapper `<div id="bannercalc-configurator">` with the full resolved config as a `data-config` JSON attribute
2. Includes `frontend/views/configurator.php` which renders:
   - **Card 1 (Size):** Size selector with preset grid and/or custom dimension inputs
   - **Card 2 (Options):** Attribute selectors (pills, toggles, dropdowns)
   - Hidden form fields for server submission

**Config passed to JavaScript:**
```javascript
{
    productId, sizingMode, defaultUnit, availableUnits, areaRateSqft,
    minimumCharge, minWidthM, minHeightM, maxWidthM, maxHeightM,
    presetSizes, enabledAttrs, attributePricing, currency, decimals
}
```

#### `render_extras_card(): void`
Renders Card 3 containing:
- **Design accordion** — collapsible section for "Upload or Design with Online Designer" (JS relocates the Personalize link and file uploader into this slot)
- **Pricing section** — includes `frontend/views/price-display.php` with live price breakdown

#### `open_quantity_row(): void` / `close_quantity_row(): void`
Wraps the WooCommerce quantity input and Add to Cart button in a `<div class="bannercalc-quantity-row">` flex container for BannerCalc products.

#### `suppress_wc_variation_form(): void`
For BannerCalc-enabled products that are still Variable type:
- Removes WooCommerce's default variation add-to-cart template
- Replaces it with the simple product template (which has the BannerCalc hook points)

#### `ensure_purchasable_price($price, $product): string`
**Filters:** `woocommerce_product_get_price`, `woocommerce_product_get_regular_price`

Returns a meaningful price for BannerCalc products that have no WC price set. Uses the category's `minimum_charge` (or `0.01` as fallback). This ensures:
- WooCommerce considers the product purchasable
- Payment gateways (PayPal, Apple Pay) see a non-zero amount and display their buttons

The actual calculated price is set by `CartHandler` at add-to-cart time.

#### `replace_price_html($price_html, $product): string`
**Filter:** `woocommerce_get_price_html`

- **Single product page:** Replaces price with a CMYK-styled brand bar element
- **Archive/shop pages:** Shows "From £X.XX" using the minimum charge, or "Price on configuration" if no minimum

---

## 16. Frontend JavaScript Controller

**File:** `frontend/js/frontend.js`

A jQuery-based IIFE that manages all frontend interactivity for the BannerCalc configurator.

### Constants

```javascript
TO_METRES  = { mm: 0.001, cm: 0.01, inch: 0.0254, ft: 0.3048, m: 1 }
SQFT_PER_SQM = 10.7639
UNIT_ABBR  = { mm: 'mm', cm: 'cm', inch: 'in', ft: 'ft', m: 'm' }
```

### State Object (`BannerCalc.state`)

| Property              | Type       | Description                                  |
| --------------------- | ---------- | -------------------------------------------- |
| `sizingMode`          | `string`   | `'preset'` or `'custom'`                    |
| `selectedPreset`      | `string`   | Selected preset slug or `null`               |
| `selectedUnit`        | `string`   | Currently selected unit (e.g., `'ft'`)       |
| `widthRaw`            | `float`    | User's entered width in selected unit        |
| `heightRaw`           | `float`    | User's entered height in selected unit       |
| `widthMetres`         | `float`    | Width converted to metres                    |
| `heightMetres`        | `float`    | Height converted to metres                   |
| `areaSqft`            | `float`    | Calculated area in sqft                      |
| `areaSqm`             | `float`    | Calculated area in sqm                       |
| `selectedAttributes`  | `object`   | `{ 'pa_hemming': 'yes', ... }`              |
| `basePrice`           | `float`    | Area × rate (or preset override)             |
| `addonsTotal`         | `float`    | Sum of attribute add-on prices               |
| `calculatedPrice`     | `float`    | Final price (base + addons)                  |
| `isValid`             | `boolean`  | Whether current config passes validation     |
| `validationErrors`    | `array`    | List of current validation error messages    |

### Methods

#### `init()`
Called on DOM ready. Parses the config from the configurator's `data-config` attribute, sets the default unit and sizing mode, initialises default attribute selections, binds events, and renders initial UI state.

#### `initDefaults()`
Reads `default_value` from each attribute's pricing config and pre-selects it in the state. Also syncs dropdown `<select>` elements to their default values.

#### `bindEvents()`
Binds all UI event handlers:

| Event                          | Target                            | Behaviour                                                              |
| ------------------------------ | --------------------------------- | ---------------------------------------------------------------------- |
| Click                          | `.bannercalc-toggle-btn`          | Toggle between Preset/Custom size modes                                |
| Click                          | `.bannercalc-preset-card`         | Select a preset size, show description, calculate price               |
| Click                          | `.bannercalc-unit-btn`            | Change measurement unit, convert existing values                      |
| Input (300ms debounce)         | `.bannercalc-dim-input`           | Read custom dimensions, calculate area, validate, update price        |
| Click                          | `.bannercalc-pill`                | Select an attribute option (pill-style), update hidden input, recalc  |
| Change                         | `.bannercalc-attr-dropdown`       | Select attribute via dropdown, update hidden input, recalc            |
| Change                         | `.bannercalc-toggle-input`        | Toggle yes/no attribute, update label text, recalc                    |

#### `changeUnit(newUnit)`
Changes the selected unit while preserving physical dimensions:
1. Updates unit label text in the UI
2. Converts existing raw dimension values to the new unit (keeps metres constant)
3. Updates constraint display and preset prices

#### `readCustomDimensions()`
Reads width/height from the input fields, converts to metres using `state.selectedUnit`.

#### `calculateArea()`
Computes `areaSqm` and `areaSqft` from metre dimensions. Updates the area display element.

#### `validateDimensions()`
Validates custom dimensions against min/max constraints from config. Sets `validationErrors` and applies/removes error styling on input groups.

#### `calculatePrice()`
Mirrors the server-side `PricingEngine::calculate()` logic:
1. `basePrice = areaSqft × areaRateSqft`
2. Apply minimum charge
3. Apply preset price override
4. Sum attribute add-ons (fixed / per_sqft / percentage)
5. `calculatedPrice = basePrice + addonsTotal`

#### `updatePriceDisplay()`
Updates the price display block:
- If no area calculated: shows "Select a size to see price" placeholder
- Otherwise: shows base price calculation, add-ons row (if > 0), and total
- Updates the hidden `bannercalc-input-price` field

#### `updateConstraintsDisplay()`
Shows min/max dimension constraints converted to the currently selected unit.

#### `updatePresetPrices()`
Recalculates and updates the displayed price on each preset size card (based on area rate or price override).

#### `updateHiddenFields()`
Syncs all state values to hidden form `<input>` fields for form submission:
- `bannercalc[sizing_mode]`
- `bannercalc[preset_slug]`
- `bannercalc[unit]`
- `bannercalc[width_raw]`
- `bannercalc[height_raw]`
- `bannercalc[calculated_price]`

### DOM Initialisation

On `$(document).ready`:
1. Calls `BannerCalc.init()`
2. Relocates the Personalize link (`.product_type_customizable`) and file uploader (`.wc-dnd-file-upload`) into the design accordion slot
3. Hides the accordion entirely if no design content is available

---

## 17. Frontend View Templates

### `configurator.php`
Main wrapper template. Renders three visual cards:

- **Card 1 (Size):** Contains the size section (presets + custom) only if `sizing_mode !== 'none'`
- **Card 2 (Options):** Contains all attribute selectors with intelligent layout grouping:
  - Compact 2-column row for `pa_hemming` + `pa_packaging`
  - Inline 2-column row for `pa_finish` + `pa_cable-ties`
  - Ungrouped attributes rendered individually
  - Dropdown pair at bottom for `pa_eyelets` + `pa_pole-pockets`
- **Hidden fields:** Six `<input type="hidden">` elements for form submission (product_id, sizing_mode, preset_slug, unit, width_raw, height_raw, calculated_price)

### `size-selector.php`
Size selection UI with two modes:

- **Preset mode:** Responsive grid of clickable size cards, sorted by popularity (highest first). Each card shows label, calculated price, and an optional "Popular" badge (popularity ≥ 5). Selecting a card shows a description callout below the grid.
- **Custom mode:** Unit selector buttons + width/height number inputs + constraint display + live area calculation.
- **Both modes:** Toggle tabs shown when `sizing_mode === 'preset_and_custom'`.

### `unit-selector.php`
Row of clickable unit buttons (mm, cm, in, ft, m). Only shows units enabled in the config. Default unit starts as `active`.

### `attribute-selector.php`
Generic attribute UI that auto-selects a display mode based on the attribute:

| Display Mode | When Used                                             | UI Element                    |
| ------------ | ----------------------------------------------------- | ----------------------------- |
| **Toggle**   | Boolean yes/no attributes (2 terms, e.g., cable ties) | Toggle switch + label + price |
| **Dropdown** | Eyelets, pole pockets, or attributes with 8+ terms   | Styled `<select>` dropdown    |
| **Pills**    | All other attributes                                  | Clickable pill buttons         |

Features:
- Dashicon + brand colour per attribute type
- Required field indicator (`*`)
- Price modifier display ("+£X.XX") when modifier > 0
- Default value pre-selected
- Hidden `<input>` per attribute for form submission

### `price-display.php`
Live price breakdown block:
- **Placeholder state:** "Select a size to see price" (shown when no size selected)
- **Active state:** Three rows — base price calculation, add-ons total (hidden if £0), estimated total price

---

## 18. Admin View Templates

### `dashboard.php`
Dashboard overview page with branded CMYK bar header:
- **Status cards row:** Plugin status, configured category count, default unit, currency
- **Quick links:** Global Settings, Category Defaults, Products
- **Active categories list:** Tag-style display of enabled categories
- **Info notice:** Shown if no categories are configured yet
- **Version footer**

### `settings-page.php`
Global settings form using WordPress Settings API:
- Enable/disable toggle
- Currency symbol text input
- Default unit dropdown (all 5 units)
- Available units multi-checkbox
- Price decimals number input
- Save button

### `categories-page.php`
Split-panel category management (504 lines):
- **Left panel:** Scrollable list of parent-level product categories with active/inactive badges
- **Right panel:** Full configuration form for the selected category

**Category form sections:**
1. Enable BannerCalc toggle
2. Sizing mode dropdown
3. Default unit dropdown
4. Available units checkboxes
5. Area rates (per sqft and per sqm)
6. Minimum charge
7. Dimension constraints (min/max in configurable unit, auto-converted to metres on save)
8. Enabled WooCommerce attributes (checkboxes)
9. Per-attribute pricing (pricing type dropdown + term modifier inputs)
10. Preset sizes repeater (add/remove rows)
11. "Import Popular Sizes" button
12. Save button

### `category-defaults.php`
Minimal template for BannerCalc fields on the WordPress term-edit screen (when editing a product category directly).

### `product-metabox.php`
Per-product override interface within the product edit screen metabox.

---

## 19. Data Architecture

### 19.1 Global Settings — `wp_options`

**Key:** `bannercalc_settings`

```php
[
    'enabled'                => bool,
    'currency_symbol'        => string,    // e.g., '£'
    'default_unit'           => string,    // 'mm'|'cm'|'inch'|'ft'|'m'
    'available_units'        => string[],  // Subset of above
    'price_display_decimals' => int,       // 0–4
]
```

### 19.2 Category Config — Term Meta

**Key:** `_bannercalc_config` on product category terms

```php
[
    'enabled'              => bool,
    'sizing_mode'          => 'preset_only'|'custom_only'|'preset_and_custom'|'none',
    'default_unit'         => string,
    'available_units'      => string[],
    'area_rate_sqft'       => float,       // Base price per square foot
    'area_rate_sqm'        => float,       // Base price per square metre
    'min_width_m'          => float,       // In metres
    'min_height_m'         => float,       // In metres
    'max_width_m'          => float,       // In metres
    'max_height_m'         => float,       // In metres
    'minimum_charge'       => float,       // Floor price
    'constraint_unit'      => string,      // Unit used for admin display of constraints
    'enabled_attributes'   => string[],    // e.g., ['pa_pole-pockets', 'pa_hemming', ...]
    'attribute_pricing'    => [
        'pa_hemming' => [
            'pricing_type'  => 'fixed'|'per_sqft'|'percentage',
            'values'        => ['slug' => float, ...],    // Per-term modifiers
            'default_value' => string,                     // Default term slug
            'required'      => bool,
        ],
        // ... more attributes
    ],
    'preset_sizes' => [
        [
            'label'        => string,      // Display label
            'slug'         => string,      // URL-safe slug
            'width_m'      => float,       // In metres
            'height_m'     => float,       // In metres
            'display_w'    => float,       // Original entered width
            'display_h'    => float,       // Original entered height
            'display_unit' => string,      // Unit used for display
            'price'        => float|null,  // Override price (null = auto-calculate)
            'description'  => string,      // Tooltip/callout text
            'popularity'   => int,         // 1–5 (5 = most popular)
        ],
        // ... more presets
    ],
]
```

### 19.3 Product Override — Post Meta

**Key:** `_bannercalc_product_config`

Only stores fields that differ from category defaults:

```php
[
    'override_enabled'              => bool,
    'sizing_mode'                   => string,      // Optional
    'area_rate_sqft'                => float,       // Optional
    'disabled_attributes'           => string[],    // e.g., ['pa_pole-pockets']
    'attribute_pricing_overrides'   => [
        'pa_hemming' => [
            'values' => ['yes' => 8.00],  // Override specific term prices
            'pricing_type' => string,     // Optional
            'default_value' => string,    // Optional
            'required' => bool,           // Optional
        ],
    ],
    'preset_sizes' => [...],  // Replaces category presets entirely
]
```

### 19.4 Cart Item Meta

Stored in the WooCommerce cart session via `woocommerce_add_cart_item_data`:

```php
$cart_item_data['bannercalc'] = [
    'sizing_mode'      => 'custom'|'preset',
    'preset_slug'      => string,
    'unit'             => string,
    'width_raw'        => float,
    'height_raw'       => float,
    'width_m'          => float,
    'height_m'         => float,
    'size_label'       => string,       // Human-readable, e.g., "2.5ft × 4ft"
    'area_sqft'        => float,
    'selected_attrs'   => ['pa_hemming' => 'yes', ...],
    'base_price'       => float,
    'addons_total'     => float,
    'calculated_price' => float,        // Final price used by WooCommerce
]
```

### 19.5 Order Item Meta

**Key:** `_bannercalc_data`

Same structure as cart item meta, persisted to the order on checkout via `woocommerce_checkout_create_order_line_item`. Visible in admin order detail, emails, and customer account.

---

## 20. WooCommerce Hooks Reference

### Actions Used

| Hook                                           | Class/Function          | Purpose                                          |
| ---------------------------------------------- | ----------------------- | ------------------------------------------------ |
| `plugins_loaded` (20)                          | `bannercalc_init()`     | Initialise the plugin                            |
| `before_woocommerce_init`                      | anonymous               | Declare HPOS compatibility                       |
| `admin_menu`                                   | `SettingsPage`          | Register admin menus                             |
| `admin_init`                                   | `SettingsPage`          | Register settings                                |
| `admin_enqueue_scripts`                        | `Admin`                 | Enqueue admin CSS/JS                             |
| `wp_enqueue_scripts`                           | `Frontend`              | Enqueue frontend CSS/JS                          |
| `add_meta_boxes`                               | `ProductMetabox`        | Register product metabox                         |
| `save_post_product`                            | `ProductMetabox`        | Save product metabox data                        |
| `product_cat_edit_form_fields` (20)            | `CategoryDefaults`      | Render category fields                           |
| `edited_product_cat`                           | `CategoryDefaults`      | Save category config                             |
| `woocommerce_before_add_to_cart_button` (10)   | `ProductDisplay`        | Render configurator                              |
| `woocommerce_before_add_to_cart_button` (11)   | `ProductDisplay`        | Render extras card                               |
| `woocommerce_before_add_to_cart_quantity` (1)  | `ProductDisplay`        | Open quantity flex row                           |
| `woocommerce_after_add_to_cart_button` (99)    | `ProductDisplay`        | Close quantity flex row                          |
| `woocommerce_before_single_product`            | `ProductDisplay`        | Suppress WC variation form                       |
| `woocommerce_before_calculate_totals` (20)     | `CartHandler`           | Override cart item prices                        |
| `woocommerce_checkout_create_order_line_item`  | `CartHandler`           | Persist data to order                            |
| `woocommerce_order_item_meta_end`              | `CartHandler`           | Display config in order/emails                   |
| `wp_ajax_bannercalc_get_config`                | `Plugin`                | AJAX: get product config                         |
| `wp_ajax_nopriv_bannercalc_get_config`         | `Plugin`                | AJAX: get product config (guests)                |
| `wp_ajax_bannercalc_save_category_config`      | `CategoryDefaults`      | AJAX: save category config                       |
| `wp_ajax_bannercalc_import_presets`            | `PresetSeeder`          | AJAX: import preset sizes                        |
| `wp_ajax_bannercalc_get_seed_data`             | `PresetSeeder`          | AJAX: get seed data for import                   |

### Filters Used

| Hook                                    | Class/Function    | Purpose                                      |
| --------------------------------------- | ----------------- | -------------------------------------------- |
| `woocommerce_add_cart_item_data`        | `CartHandler`     | Inject BannerCalc data into cart item        |
| `woocommerce_get_item_data`            | `CartHandler`     | Display config summary in cart               |
| `woocommerce_product_get_price`        | `ProductDisplay`  | Return purchasable price for BC products     |
| `woocommerce_product_get_regular_price`| `ProductDisplay`  | Return purchasable price for BC products     |
| `woocommerce_get_price_html`          | `ProductDisplay`  | Replace price HTML on product/archive pages  |
| `woocommerce_product_data_tabs`       | `ProductDisplay`  | Stub: hide variations tab                     |

---

## 21. Price Calculation Formula

```
┌────────────────────────────────────────────────────┐
│ 1. AREA CALCULATION                                │
│    area_sqm  = width_m × height_m                  │
│    area_sqft = area_sqm × 10.7639                  │
│                                                    │
│ 2. BASE PRICE                                      │
│    base_price = area_sqft × area_rate_sqft         │
│                                                    │
│ 3. MINIMUM CHARGE                                  │
│    if base_price < minimum_charge:                 │
│        base_price = minimum_charge                 │
│                                                    │
│ 4. PRESET OVERRIDE                                 │
│    if preset selected AND preset.price ≠ null:     │
│        base_price = preset.price                   │
│                                                    │
│ 5. ADD-ONS                                         │
│    for each selected attribute:                    │
│        if pricing_type == 'fixed':                 │
│            addon += modifier                       │
│        if pricing_type == 'per_sqft':              │
│            addon += modifier × area_sqft           │
│        if pricing_type == 'percentage':            │
│            addon += base_price × (modifier / 100)  │
│                                                    │
│ 6. FINAL PRICE                                     │
│    calculated_price = base_price + addons_total    │
└────────────────────────────────────────────────────┘
```

### Example Calculation

For a **2.5ft × 4ft** custom vinyl banner at **£1.60/sqft** with hemming (Yes, +£5) and pole pockets (Top & Bottom, +£5):

| Step               | Computation                            | Result    |
| ------------------ | -------------------------------------- | --------- |
| Width in metres    | 2.5 × 0.3048                           | 0.762 m   |
| Height in metres   | 4 × 0.3048                             | 1.2192 m  |
| Area (sqm)         | 0.762 × 1.2192                         | 0.929 sqm |
| Area (sqft)        | 0.929 × 10.7639                        | 10.00 sqft|
| Base price         | 10.00 × £1.60                          | £16.00    |
| Hemming (fixed)    | +£5.00                                 | £5.00     |
| Pole Pockets (fixed)| +£5.00                                | £5.00     |
| Add-ons total      | £5.00 + £5.00                          | £10.00    |
| **Calculated price** | £16.00 + £10.00                      | **£26.00** |

---

## 22. Uninstall Procedure

**File:** `uninstall.php`

Executed when the plugin is deleted (not just deactivated). Removes all plugin data:

1. **Global settings:** Deletes `bannercalc_settings` from `wp_options`
2. **Category configs:** Deletes `_bannercalc_config` term meta from all product categories
3. **Product configs:** Deletes all `_bannercalc_product_config` post meta entries
4. **Order item meta:** Deletes all `_bannercalc_data` entries from `woocommerce_order_itemmeta`

---

## 23. Feature Summary

### Pricing & Calculation
- [x] Area-based pricing (price per square foot)
- [x] Minimum charge enforcement
- [x] Preset size price overrides
- [x] Three add-on pricing types: fixed, per-sqft, percentage
- [x] Server-side price recalculation (never trusts client)
- [x] Live frontend price calculation with breakdown display

### Size Configuration
- [x] Preset sizes with clickable card grid
- [x] Custom dimension entry with number inputs
- [x] Hybrid mode (toggle between preset and custom)
- [x] None mode (fixed-price products)
- [x] Min/max dimension constraints with validation
- [x] Preset popularity ranking and "Popular" badges
- [x] Preset size descriptions with callout display

### Unit System
- [x] Five supported units: mm, cm, inch, ft, m
- [x] Metres as canonical internal unit
- [x] Live unit conversion on unit change (preserves physical size)
- [x] Per-category default unit and available units
- [x] Constraint display updates with selected unit
- [x] Preset label display in configured unit

### Attribute System
- [x] Reads from WooCommerce's existing attribute taxonomy
- [x] Three display modes: pills, toggle switches, styled dropdowns
- [x] Auto-detection of boolean yes/no attributes
- [x] Per-attribute icons with brand colours
- [x] Required field indicators
- [x] Default value pre-selection
- [x] Price modifier display on buttons (+£X.XX)
- [x] Per-term pricing configuration
- [x] Category-level attribute management
- [x] Per-product attribute disabling/override

### Admin Interface
- [x] Top-level admin menu with branded SVG icon
- [x] Dashboard with status overview and quick links
- [x] Global settings page with WordPress Settings API
- [x] Split-panel category management page
- [x] Per-product metabox with override capabilities
- [x] Preset size importer with UK popular sizes seed data
- [x] Branded admin UI with Google Fonts and CMYK accents

### Cart & Checkout Integration
- [x] Full cart item meta with all configuration data
- [x] Server-side price override in cart totals
- [x] Configuration summary in cart and checkout
- [x] Order item meta persistence on checkout
- [x] Configuration display in order emails (HTML + plain text)
- [x] Configuration display in admin order detail
- [x] Configuration display in customer "My Account > Orders"

### WooCommerce Compatibility
- [x] HPOS (High-Performance Order Storage) declared compatible
- [x] Simple product type (no variations needed)
- [x] Suppresses WC variation form on enabled products
- [x] Makes priceless products purchasable for payment gateways
- [x] Replaces default price HTML with branded display
- [x] Archive pages show "From £X.XX" pricing

### Frontend UX
- [x] Branded card-based layout
- [x] Design accordion for file upload/personalize integration
- [x] Flex-row quantity + add to cart button wrapper
- [x] 300ms debounced dimension inputs
- [x] Real-time validation with error styling
- [x] Responsive preset size grid
- [x] CMYK brand bar design elements

### Data Management
- [x] Category-level defaults with per-product overrides
- [x] Deep config merge (category base + product overrides)
- [x] Category hierarchy support (walks up to root parent)
- [x] Clean uninstall removes all plugin data
- [x] Nonce verification on all admin operations
- [x] Capability checks (`manage_woocommerce`, `edit_post`)
