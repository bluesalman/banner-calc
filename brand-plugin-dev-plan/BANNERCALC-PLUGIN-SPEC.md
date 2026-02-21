# BannerCalc – Product Configurator & Pricing Engine

## Plugin Development Specification

**Version:** 1.0  
**WordPress:** 6.4+  
**WooCommerce:** 8.0+  
**PHP:** 8.0+  

---

## 1. Overview

BannerCalc is a custom WooCommerce plugin that replaces the default variation-based product configuration system with a unified pricing engine. It uses WooCommerce's existing attribute taxonomy as a data source but completely replaces both the admin product editor UI and the frontend product page UI with custom interfaces.

### Core Principle

Products are kept as **Simple** type (not Variable). There are **zero WooCommerce variations**. All pricing is calculated by the plugin using formulas and modifiers. All attribute selections, dimensions, and calculated prices are stored as cart item meta and order item meta.

### What the Plugin Replaces

| WooCommerce Default | BannerCalc Replacement |
|---|---|
| Attributes tab in product editor | Custom metabox with toggle + pricing config |
| Variations tab in product editor | Eliminated entirely — no variations |
| Frontend `<select>` dropdowns | Styled button/card selectors |
| Fixed variation prices | Dynamic pricing engine (area × rate + add-ons) |
| Single unit display (mm or ft) | Multi-unit selector with live conversion |

---

## 2. Directory Structure

```
bannercalc/
├── bannercalc.php                    # Main plugin file, bootstrap
├── readme.txt
├── uninstall.php                     # Cleanup on uninstall
│
├── includes/
│   ├── class-bannercalc.php          # Core plugin class, hooks registration
│   ├── class-admin.php               # Admin-side logic coordinator
│   ├── class-frontend.php            # Frontend-side logic coordinator
│   ├── class-pricing-engine.php      # All price calculation logic
│   ├── class-cart-handler.php        # Cart/checkout/order integration
│   ├── class-unit-converter.php      # Unit conversion utilities
│   └── class-attribute-manager.php   # Read WooCommerce taxonomy data
│
├── admin/
│   ├── class-settings-page.php       # Plugin global settings page
│   ├── class-category-defaults.php   # Category-level attribute/pricing config
│   ├── class-product-metabox.php     # Per-product metabox on edit screen
│   ├── views/
│   │   ├── settings-page.php         # Settings page HTML template
│   │   ├── category-defaults.php     # Category config HTML template
│   │   └── product-metabox.php       # Product metabox HTML template
│   ├── css/
│   │   └── admin.css                 # Admin panel styles
│   └── js/
│       └── admin.js                  # Admin panel interactivity
│
├── frontend/
│   ├── class-product-display.php     # Renders configurator on single product page
│   ├── views/
│   │   ├── configurator.php          # Main configurator wrapper template
│   │   ├── size-selector.php         # Preset sizes + custom size UI
│   │   ├── unit-selector.php         # Unit toggle buttons
│   │   ├── attribute-selector.php    # Generic attribute button/toggle selector
│   │   └── price-display.php         # Live price block
│   ├── css/
│   │   └── frontend.css              # Frontend configurator styles
│   └── js/
│       └── frontend.js               # Unit conversion, price calc, validation
│
└── assets/
    └── icons/                        # SVG icons for attribute options (optional)
```

---

## 3. Data Architecture

### 3.1 Data Source: WooCommerce Attribute Taxonomy

The plugin reads attribute data from WooCommerce's existing taxonomy system. It does **not** create its own attribute storage.

**Existing WooCommerce attributes (already registered):**

| Attribute | Taxonomy Slug | Example Terms |
|---|---|---|
| Size | `pa_size` | 1ft x 1ft, 1ft x 3ft, 2ft x 2ft, ..., 850mm x 2000mm, 1000mm x 2000mm |
| Pole Pockets | `pa_pole-pockets` | None, Top Only, Top & Bottom, 1.5 inches Top & Bottom, 2.5 inches Left & Right, 3 inches Top & Bottom, All Sides |
| Cable Ties | `pa_cable-ties` | Yes, No |
| Eyelets | `pa_eyelets` | None, 4 Corners, Every 1ft, Every 2ft |
| Hemming | `pa_hemming` | No, Taped Edge, Yes |
| Packaging | `pa_packaging` | Folded, Rolled on Core |

The plugin reads these taxonomies and their terms dynamically. If the client adds a new term (e.g., a new Pole Pocket option), it's immediately available in the plugin config — no code changes needed.

### 3.2 Plugin Settings (Stored as WordPress Options)

**Global settings** stored in `wp_options` under key `bannercalc_settings`:

```php
[
    'enabled' => true,
    'currency_symbol' => '£',
    'default_unit' => 'ft',           // Default unit shown to customer
    'available_units' => ['mm', 'cm', 'inch', 'ft', 'm'],
    'price_display_decimals' => 2,
]
```

### 3.3 Category-Level Defaults (Stored as Term Meta)

Stored on WooCommerce product category terms using `update_term_meta()`.

**Key:** `_bannercalc_config`

```php
// Example: "Outdoor Vinyl Banners" category config
[
    'enabled' => true,
    'sizing_mode' => 'preset_and_custom',  // 'preset_only' | 'custom_only' | 'preset_and_custom' | 'none'
    'default_unit' => 'ft',
    'available_units' => ['mm', 'cm', 'inch', 'ft', 'm'],
    'area_rate_sqft' => 1.60,              // Base price per sqft
    'area_rate_sqm' => 17.00,              // Base price per sqm (used for conversion display)
    'min_width_m' => 0.3048,               // Min width in metres (1ft)
    'min_height_m' => 0.3048,              // Min height in metres (1ft)
    'max_width_m' => 6.096,                // Max width in metres (20ft)
    'max_height_m' => 3.048,               // Max height in metres (10ft)
    'minimum_charge' => 1.60,              // Minimum order price
    'enabled_attributes' => [
        'pa_size',
        'pa_pole-pockets',
        'pa_cable-ties',
        'pa_eyelets',
        'pa_hemming',
        'pa_packaging',
    ],
    'attribute_pricing' => [
        'pa_pole-pockets' => [
            'pricing_type' => 'fixed',     // 'fixed' | 'per_sqft' | 'percentage'
            'values' => [
                'none' => 0,
                'top-only' => 3.00,
                'top-bottom' => 5.00,
                '1-5-inches-top-bottom' => 5.00,
                '2-5-inches-left-right' => 6.00,
                '3-inches-top-bottom' => 7.00,
                'all-sides' => 10.00,
            ],
            'default_value' => 'none',
            'required' => false,
        ],
        'pa_cable-ties' => [
            'pricing_type' => 'fixed',
            'values' => [
                'no' => 0,
                'yes' => 2.00,
            ],
            'default_value' => 'no',
            'required' => false,
        ],
        'pa_eyelets' => [
            'pricing_type' => 'fixed',
            'values' => [
                'none' => 0,
                '4-corners' => 0,          // Included free
                'every-1ft' => 3.00,
                'every-2ft' => 2.00,
            ],
            'default_value' => 'none',
            'required' => false,
        ],
        'pa_hemming' => [
            'pricing_type' => 'fixed',
            'values' => [
                'no' => 0,
                'taped-edge' => 3.00,
                'yes' => 5.00,
            ],
            'default_value' => 'no',
            'required' => false,
        ],
        'pa_packaging' => [
            'pricing_type' => 'fixed',
            'values' => [
                'folded' => 0,
                'rolled-on-core' => 4.00,
            ],
            'default_value' => 'folded',
            'required' => true,
        ],
    ],
    'preset_sizes' => [
        // These reference pa_size term slugs + optional price override
        // If 'price' is null, it's calculated from area_rate
        ['slug' => '1ft-x-1ft', 'label' => '1ft × 1ft', 'width_m' => 0.3048, 'height_m' => 0.3048, 'price' => null],
        ['slug' => '2ft-x-3ft', 'label' => '2ft × 3ft', 'width_m' => 0.6096, 'height_m' => 0.9144, 'price' => null],
        ['slug' => '3ft-x-6ft', 'label' => '3ft × 6ft', 'width_m' => 0.9144, 'height_m' => 1.8288, 'price' => null],
        // ... more preset sizes
    ],
]
```

### 3.4 Per-Product Override (Stored as Post Meta)

Stored on the product post using `update_post_meta()`.

**Key:** `_bannercalc_product_config`

```php
// Only stores fields that DIFFER from the category default.
// If this meta is empty/absent, category defaults are used entirely.
[
    'override_enabled' => true,
    'sizing_mode' => 'preset_only',        // Override: this product only has preset sizes
    'area_rate_sqft' => 2.00,              // Override: different rate for this product
    'disabled_attributes' => ['pa_pole-pockets'],  // This product doesn't offer pole pockets
    'attribute_pricing_overrides' => [
        'pa_hemming' => [
            'values' => [
                'yes' => 8.00,             // Override: hemming costs more on this product
            ],
        ],
    ],
    // preset_sizes override if this product has different presets than category default
]
```

### 3.5 Resolved Config (Runtime Merge)

At runtime, the plugin merges category defaults + product overrides to produce the final config for a given product. This is the config used by both admin display and frontend rendering.

```php
// Pseudocode
function get_product_config($product_id) {
    $product = wc_get_product($product_id);
    $categories = $product->get_category_ids();
    $cat_config = get_term_meta($categories[0], '_bannercalc_config', true);
    $product_override = get_post_meta($product_id, '_bannercalc_product_config', true);
    return array_merge_recursive_custom($cat_config, $product_override);
}
```

### 3.6 Cart Item Meta

When a customer adds a configured product to cart, the plugin stores:

```php
// Stored via woocommerce_add_cart_item_data filter
[
    'bannercalc' => [
        'sizing_mode' => 'custom',                // 'preset' or 'custom'
        'preset_slug' => null,                     // Term slug if preset selected
        'unit' => 'ft',                            // Customer's selected unit
        'width_raw' => 2.5,                        // Customer's entered value
        'height_raw' => 4,                         // Customer's entered value
        'width_m' => 0.762,                        // Converted to metres (canonical)
        'height_m' => 1.2192,                      // Converted to metres (canonical)
        'area_sqft' => 10.00,                      // Calculated area
        'area_sqm' => 0.929,                       // Calculated area
        'base_price' => 16.00,                     // area × rate
        'attributes' => [
            'pa_pole-pockets' => ['slug' => 'top-bottom', 'label' => 'Top & Bottom', 'price' => 5.00],
            'pa_hemming' => ['slug' => 'yes', 'label' => 'Yes', 'price' => 5.00],
            'pa_eyelets' => ['slug' => '4-corners', 'label' => '4 Corners', 'price' => 0],
            'pa_cable-ties' => ['slug' => 'no', 'label' => 'No', 'price' => 0],
            'pa_packaging' => ['slug' => 'folded', 'label' => 'Folded', 'price' => 0],
        ],
        'addons_total' => 10.00,                   // Sum of attribute price modifiers
        'calculated_price' => 26.00,               // base_price + addons_total
    ]
]
```

### 3.7 Order Item Meta

Same data structure as cart item meta, persisted to the order for production/fulfilment visibility. Displayed in:
- Admin order detail page
- Customer order confirmation email
- Customer "My Account > Orders" page

---

## 4. Admin Interface

### 4.1 Plugin Settings Page

**Location:** WooCommerce > BannerCalc (admin submenu)

**Sections:**

**General Settings**
- Enable/disable plugin globally (toggle)
- Default measurement unit (dropdown: mm/cm/inch/ft/m)
- Available units for customers (multi-checkbox)
- Price display decimals (number input)

**Category Defaults** (repeating panel, one per product category)
- Select product category (dropdown of WooCommerce product categories)
- Enable BannerCalc for this category (toggle)
- Sizing mode (radio: Preset Only / Custom Only / Preset + Custom / None)
- Default unit for this category (dropdown)
- Area pricing rate — per sqft (number input)
- Area pricing rate — per sqm (auto-calculated display, or manual override)
- Min/max dimensions (4 number inputs: min width, min height, max width, max height — in metres)
- Minimum charge (number input)

**Attribute Configuration** (per category, nested)
- List of all WooCommerce attributes with checkboxes to enable
- For each enabled attribute, expandable panel showing:
  - Pricing type (dropdown: Fixed / Per sqft / Percentage)
  - Table of all terms with price modifier input per term
  - Default value (dropdown of terms)
  - Required (toggle)

**Preset Sizes** (per category)
- Table: Term slug | Display label | Width (m) | Height (m) | Price override
- "Add row" button
- Option to auto-populate from existing pa_size terms assigned to products in this category
- Drag-to-reorder

### 4.2 Product Metabox

**Location:** Product edit screen, custom metabox titled "BannerCalc Configuration"

**Behaviour:**
- If product belongs to a category with BannerCalc enabled, the metabox appears
- Shows inherited category defaults as read-only with "Override" toggle per field
- Only stores overridden values in product meta

**Fields:**
- "Override category defaults" master toggle
- When enabled, shows same fields as category config but pre-populated with inherited values
- Each field has a small "reset to category default" link
- Preview button that opens the frontend configurator in a modal/new tab for testing

### 4.3 Admin Order Display

On the order edit screen, for each line item configured via BannerCalc, display a clear summary box:

```
Size: 2.5ft × 4ft (Custom) — 10.00 sqft
Unit: Feet
Base Price: £16.00 (£1.60/sqft × 10.00 sqft)
─────────────────────
Pole Pockets: Top & Bottom — +£5.00
Hemming: Yes — +£5.00
Eyelets: 4 Corners — +£0.00
Cable Ties: No — +£0.00
Packaging: Folded — +£0.00
─────────────────────
Total: £26.00
```

---

## 5. Frontend Interface

### 5.1 Where It Renders

The plugin hooks into `woocommerce_before_add_to_cart_button` (or replaces the entire add-to-cart form via `woocommerce_single_product_summary`). It hides WooCommerce's default variation dropdowns and renders its own configurator.

**The configurator only renders on products where BannerCalc is enabled** (via category config or product override). All other products render normally.

### 5.2 Configurator Layout

The frontend configurator renders in this order, top to bottom:

```
┌─────────────────────────────────────────┐
│ 1. SIZE SECTION                         │
│    ┌─────────────────────────────────┐  │
│    │ [Popular Sizes] [Custom Size]   │  │  ← Mode toggle (if both enabled)
│    └─────────────────────────────────┘  │
│                                         │
│    IF PRESET MODE:                      │
│    ┌──────────┐ ┌──────────┐ ┌───────┐ │
│    │ 2ft × 3ft│ │ 3ft × 5ft│ │4ft×6ft│ │  ← Clickable size cards
│    │   £9.60  │ │  £24.00  │ │£38.40 │ │
│    └──────────┘ └──────────┘ └───────┘ │
│                                         │
│    IF CUSTOM MODE:                      │
│    [mm] [cm] [inch] [ft✓] [m]          │  ← Unit selector buttons
│    ┌──────────┐     ┌──────────┐       │
│    │ Width    │  ×  │ Height   │       │  ← Dimension inputs
│    │ 2.5 ft   │     │ 4 ft     │       │
│    └──────────┘     └──────────┘       │
│    ⚠ Min: 1ft × 1ft | Max: 20ft × 10ft│  ← Constraint display
│    Area: 10.00 sqft (0.93 sqm)         │  ← Live area calc
│                                         │
├─────────────────────────────────────────┤
│ 2. ATTRIBUTE SELECTORS                  │
│                                         │
│    Pole Pockets                         │
│    [None] [Top Only] [Top & Bottom✓]    │  ← Button-style selectors
│    [1.5" T&B] [2.5" L&R] [3" T&B]     │
│    [All Sides]                          │
│                                         │
│    Hemming                              │
│    [No] [Taped Edge +£3] [Yes +£5✓]    │
│                                         │
│    Eyelets                              │
│    [None] [4 Corners] [Every 1ft +£3]   │
│    [Every 2ft +£2]                      │
│                                         │
│    Cable Ties                           │
│    [No] [Yes +£2]                       │
│                                         │
│    Packaging                            │
│    [Folded] [Rolled on Core +£4]        │
│                                         │
├─────────────────────────────────────────┤
│ 3. PRICE BLOCK                          │
│    ┌─────────────────────────────────┐  │
│    │ ██████████████████████████████  │  │
│    │ █  Estimated Price    £26.00 █  │  │  ← Dark background, prominent
│    │ █  10 sqft × £1.60 = £16.00  █  │  │
│    │ █  + Add-ons: £10.00         █  │  │
│    │ ██████████████████████████████  │  │
│    └─────────────────────────────────┘  │
│                                         │
├─────────────────────────────────────────┤
│ 4. UPLOAD + ADD TO CART                 │
│    [Upload Your Design]                 │  ← Existing upload plugin (untouched)
│    [− 1 +]  [████ Add To Basket ████]   │
└─────────────────────────────────────────┘
```

### 5.3 Frontend Behaviour Rules

**Size Section:**
- If `sizing_mode` = `preset_only`: Show only preset size cards, no toggle
- If `sizing_mode` = `custom_only`: Show only unit selector + dimension inputs, no toggle
- If `sizing_mode` = `preset_and_custom`: Show toggle with both panels
- If `sizing_mode` = `none`: Hide size section entirely (for fixed-price products)

**Preset Sizes:**
- Displayed as clickable cards in a responsive grid
- Each card shows: size label (in default unit), price
- When customer changes unit (if toggle visible), preset labels convert to selected unit
- Selecting a preset auto-fills width/height internally for price calculation
- Only one can be selected at a time

**Custom Size:**
- Unit selector shows only units enabled in config
- Width and height inputs are `type="number"` with `step="0.01"`
- Label beside each input updates with selected unit abbreviation
- On every input change (debounced 300ms), recalculate area and price
- Show validation message if outside min/max constraints
- Show calculated area in both sqft and sqm

**Attribute Selectors:**
- Rendered as styled button groups (not `<select>` dropdowns)
- Each button shows: term label + price modifier (e.g., "+£5.00") if modifier > 0
- If modifier is 0, just show label (no "+£0.00")
- Default value pre-selected based on config
- Required attributes: must have a selection before Add to Basket enables
- Optional attributes: "None" option always available

**Price Block:**
- Updates live on every change (size, unit, attribute selection)
- Shows breakdown: base price calculation + add-ons total = final price
- If no size selected yet, show "Select a size to see price" in muted state
- Minimum charge applied if calculated price is below threshold

**Add to Basket:**
- Disabled until: a valid size is selected AND all required attributes have a selection
- On click: passes all BannerCalc data as cart item meta via AJAX (or form POST)

### 5.4 Unit Conversion Table

All conversions go through metres as the canonical unit:

```javascript
const TO_METRES = {
    mm:   0.001,
    cm:   0.01,
    inch: 0.0254,
    ft:   0.3048,
    m:    1
};

// Convert input to metres: value * TO_METRES[unit]
// Convert metres to display: value / TO_METRES[unit]
// Area in sqft: (width_m * height_m) * 10.7639
// Area in sqm:  width_m * height_m
```

### 5.5 Price Calculation Formula

```
base_price = area_sqft × area_rate_sqft

// Apply minimum charge
if (base_price < minimum_charge) base_price = minimum_charge

// For preset sizes with price override
if (preset_selected && preset.price !== null) base_price = preset.price

// Sum attribute add-ons
addons_total = 0
for each selected attribute:
    if pricing_type == 'fixed':
        addons_total += modifier_value
    if pricing_type == 'per_sqft':
        addons_total += modifier_value × area_sqft
    if pricing_type == 'percentage':
        addons_total += base_price × (modifier_value / 100)

// Final price
calculated_price = base_price + addons_total
```

---

## 6. Cart & Checkout Integration

### 6.1 Add to Cart

**Hook:** `woocommerce_add_cart_item_data`

When a BannerCalc-configured product is added to cart, the plugin:
1. Validates all submitted data server-side (size within constraints, required attributes present)
2. Recalculates price server-side (never trust frontend price)
3. Stores the full `bannercalc` array as cart item data (see Section 3.6)
4. Returns the cart item data with custom price

**Hook:** `woocommerce_before_calculate_totals`

On every cart calculation, the plugin overrides the product price with the `calculated_price` from the stored `bannercalc` data. This ensures WooCommerce uses the plugin's price for totals, tax, and payment.

### 6.2 Cart Display

**Hook:** `woocommerce_get_item_data`

Displays a summary under each cart item:

```
Size: 2.5ft × 4ft (Custom)
Pole Pockets: Top & Bottom (+£5.00)
Hemming: Yes (+£5.00)
Eyelets: 4 Corners
Packaging: Folded
```

### 6.3 Cart Item Uniqueness

**Important:** Two items of the same product but with different configurations must be treated as separate cart items. The plugin achieves this by including a hash of the `bannercalc` data in the cart item key.

```php
// In woocommerce_add_cart_item_data
$cart_item_data['bannercalc_hash'] = md5(serialize($bannercalc_data));
```

### 6.4 Order Storage

**Hook:** `woocommerce_checkout_create_order_line_item`

Persists all `bannercalc` cart item data as order item meta. This ensures the data survives after the cart is cleared and is accessible in:
- Admin order detail page
- Order confirmation emails
- REST API order responses
- "My Account > Orders" customer view

### 6.5 Order Emails

**Hook:** `woocommerce_order_item_meta_end`

Appends a formatted summary of the BannerCalc configuration to each line item in order notification emails (admin new order, customer processing, customer completed).

---

## 7. WooCommerce Hooks Reference

### Hooks the Plugin Uses

| Hook | Type | Purpose |
|---|---|---|
| `admin_menu` | Action | Register plugin settings page |
| `add_meta_boxes` | Action | Register product metabox |
| `save_post_product` | Action | Save product metabox data |
| `woocommerce_single_product_summary` | Action | Render frontend configurator |
| `woocommerce_add_cart_item_data` | Filter | Inject BannerCalc data into cart item |
| `woocommerce_before_calculate_totals` | Action | Override product price with calculated price |
| `woocommerce_get_item_data` | Filter | Display config summary in cart |
| `woocommerce_checkout_create_order_line_item` | Action | Persist data to order item meta |
| `woocommerce_order_item_meta_end` | Action | Display config in order emails |
| `wp_enqueue_scripts` | Action | Enqueue frontend CSS/JS |
| `admin_enqueue_scripts` | Action | Enqueue admin CSS/JS |
| `wp_ajax_bannercalc_get_config` | Action | AJAX endpoint to fetch product config |
| `wp_ajax_nopriv_bannercalc_get_config` | Action | Same for logged-out users |

### Hooks the Plugin May Need to Suppress

| Hook/Feature | Reason |
|---|---|
| WooCommerce variation dropdowns | Hide on BannerCalc-enabled products |
| Default add-to-cart form | Replace with configurator form |
| Variation price display | Replace with live calculated price |

---

## 8. JavaScript Architecture (Frontend)

### 8.1 State Management

```javascript
const BannerCalcState = {
    productId: 0,
    config: {},                    // Resolved config from server
    
    // Current selections
    sizingMode: 'preset',          // 'preset' or 'custom'
    selectedPreset: null,          // Preset slug or null
    selectedUnit: 'ft',
    widthRaw: null,                // User's entered value
    heightRaw: null,               // User's entered value
    widthMetres: null,             // Converted
    heightMetres: null,            // Converted
    areaSqft: null,
    areaSqm: null,
    
    selectedAttributes: {},        // { 'pa_hemming': 'yes', 'pa_packaging': 'folded', ... }
    
    // Calculated
    basePrice: 0,
    addonsTotal: 0,
    calculatedPrice: 0,
    isValid: false,
    validationErrors: [],
};
```

### 8.2 Event Flow

```
User Action → Update State → Validate → Calculate Price → Update DOM

1. User selects unit         → state.selectedUnit = 'cm'
                             → convert min/max labels
                             → convert preset size labels
                             → if custom mode, recalculate from raw inputs
                             → update price display

2. User clicks preset size   → state.sizingMode = 'preset'
                             → state.selectedPreset = slug
                             → state.widthMetres = preset.width_m
                             → state.heightMetres = preset.height_m
                             → calculate area
                             → calculate price
                             → update price display

3. User types custom width   → state.widthRaw = value
                             → state.widthMetres = value * TO_METRES[unit]
                             → validate against min/max
                             → calculate area
                             → calculate price (debounced 300ms)
                             → update price display

4. User selects attribute    → state.selectedAttributes[attr] = termSlug
                             → calculate addons total
                             → calculate final price
                             → update price display

5. User clicks Add to Basket → validate all required fields
                             → if invalid, show errors
                             → if valid, submit form/AJAX with full state
```

### 8.3 Initialisation

```javascript
// On page load for BannerCalc-enabled products:
// 1. Read config from data attribute or AJAX call
// 2. Set default unit from config
// 3. Set default attribute values from config
// 4. Render preset sizes (if applicable)
// 5. Render attribute selectors
// 6. Set price block to "Select a size" state
// 7. Attach event listeners
```

---

## 9. Product Types Supported

| Product Type | Sizing Mode | Attributes | Example |
|---|---|---|---|
| Custom-size-only | `custom_only` | Varies | Window Stickers, Wall Stickers, Floor Stickers |
| Preset + Custom hybrid | `preset_and_custom` | Size + Pole Pockets + Hemming + Eyelets + Cable Ties + Packaging | Vinyl Banners, Mesh Banners |
| Preset sizes only | `preset_only` | Size + limited add-ons | Roller Banners |
| Fixed price (no calculator) | `none` | None (use default WooCommerce) | Posters, Flyers, Backdrops |

---

## 10. Edge Cases & Validation Rules

### Server-Side Validation (on Add to Cart)

1. Product has BannerCalc enabled
2. If custom size: width and height are positive numbers
3. If custom size: dimensions are within min/max constraints (in metres)
4. If preset size: submitted slug exists in config's preset list
5. All required attributes have a valid term selected
6. Selected attribute terms exist in WooCommerce taxonomy
7. Recalculated server-side price matches submitted price (within £0.01 tolerance)
8. If price mismatch, use server-calculated price (never trust client)

### Frontend Validation

1. Dimension inputs: `min="0.01"`, prevent negative values
2. Dimension inputs: show red border + error message if outside constraints
3. Required attributes: highlight unselected required attributes on Add to Basket click
4. Disable Add to Basket until all validations pass

### Special Cases

- **Minimum charge**: If area × rate < minimum_charge, use minimum_charge as base_price
- **Price override on preset**: If a preset size has a non-null `price` field, use that instead of area calculation
- **Zero-price add-ons**: Don't show "+£0.00" on attribute buttons, just show the label
- **Unit change with values entered**: When customer switches unit, keep the physical size the same (convert displayed values), don't reset inputs
- **Multiple categories**: If product belongs to multiple categories with different configs, use the first category's config (or the one explicitly set in product override)

---

## 11. Migration Path

### Phase 1: Plugin Development
Build and test the complete plugin on staging site.

### Phase 2: Configure Categories
Set up category-level defaults for each product category (Vinyl Banners, Mesh Banners, Roller Banners, Stickers, etc.).

### Phase 3: Test on Single Product
Enable on one vinyl banner product. Verify: frontend display, price calculation, cart integration, order display. Compare prices against existing variation prices to ensure formula accuracy.

### Phase 4: Gradual Rollout
Enable category by category. For each product:
1. Verify BannerCalc config renders correctly
2. Verify prices match expected values
3. Remove WooCommerce variations (they're no longer needed)
4. Change product type from Variable to Simple

### Phase 5: Cleanup
Once all products are migrated:
- Remove unused variations from database
- Optionally keep attribute terms (they're still used as the data source)
- Deactivate any variation-dependent plugins if no longer needed

---

## 12. Future Extensibility

The architecture supports these additions without structural changes:

- **New attributes**: Add to WooCommerce taxonomy, enable in category config, set pricing — no code change
- **New product categories**: Configure in plugin settings — no code change
- **Tiered area pricing**: Extend `area_rate_sqft` to an array of rate tiers (e.g., 0–10 sqft = £1.60, 10–50 sqft = £1.40)
- **Quantity discounts**: Add quantity-based multiplier to pricing engine
- **Conditional attributes**: e.g., "Show Pole Pockets only if size > 3ft" — add condition rules to attribute config
- **Multi-currency**: Swap `area_rate_sqft` per currency
- **REST API**: Expose BannerCalc config and pricing calculation as WooCommerce REST API endpoints
