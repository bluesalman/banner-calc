# BannerCalc Plugin — Requirements for v1.5.0 Update

**Target:** VS Code AI Agent (Claude Opus 4.6)  
**Plugin Version:** Current v1.4.2 → Target v1.5.0  
**Scope:** 4 new features + 1 frontend enhancement  
**Constraint:** All changes must be consistent with the existing plugin architecture, coding patterns, class structure, and frontend design. Do NOT break existing functionality.

---

## Pre-Read — Mandatory Context Files

Before making any changes, the agent MUST read and understand:

1. **`DOCUMENTATION.md`** (or `PLUGIN_DOCUMENTATION.md` in project root) — Full plugin documentation covering every class, method, hook, data structure, and template. This is the authoritative reference for the plugin's architecture.
2. **`includes/class-pricing-engine.php`** — Current pricing logic (the `calculate()` method).
3. **`includes/class-cart-handler.php`** — How cart data is structured and prices are overridden.
4. **`admin/class-category-defaults.php`** and **`admin/views/categories-page.php`** — How category config is saved, sanitised, and rendered in admin.
5. **`frontend/class-product-display.php`** — How the frontend configurator renders and hooks into WooCommerce.
6. **`frontend/js/frontend.js`** — The frontend state management, event binding, and price calculation mirror.
7. **`frontend/views/configurator.php`** — Main template that assembles the configurator cards.
8. **`frontend/views/attribute-selector.php`** — How attributes render (pills, toggles, dropdowns).
9. **`frontend/views/price-display.php`** — Price breakdown template.
10. **`frontend/css/frontend.css`** — All existing CSS classes and the branded design system.

---

## Architecture Principles (DO NOT VIOLATE)

- Products are **Simple type** — no WooCommerce variations.
- All attribute data reads from **WooCommerce's existing attribute taxonomy** (`wc_get_attribute_taxonomies()`, `get_terms()`).
- Pricing is **always recalculated server-side** in `CartHandler::add_cart_item_data()` — the frontend JS calculation is a live preview only, never trusted.
- Category config stored as **term meta** `_bannercalc_config`. Product overrides stored as **post meta** `_bannercalc_product_config`.
- Config resolution: category defaults → deep-merged with product overrides via `Plugin::merge_config()`.
- Frontend templates live in `frontend/views/`. Admin templates in `admin/views/`.
- CSS uses the Banner Printing brand system: `--bp-cyan`, `--bp-magenta`, `--bp-yellow`, `--bp-key` as primary colours. Fonts: Outfit (display), Plus Jakarta Sans (body), IBM Plex Mono (mono).
- All admin AJAX uses nonce `bannercalc_admin` and capability `manage_woocommerce`.

---

## REQUIREMENT 1: Service Type Markup (Urgent Delivery)

### What It Is

A **global** attribute that applies to ALL categories. It adds a percentage markup to the final price for urgent production/delivery:

| Service Level | Markup |
|---|---|
| Standard (default) | 0% (no change) |
| Urgent — 48 Hours | +15% of calculated price |
| Urgent — 24 Hours | +25% of calculated price |

This is NOT a WooCommerce taxonomy attribute. It is a **plugin-managed** global option, similar to how the global settings work.

### Data Structure Changes

#### Global Settings (`bannercalc_settings` in `wp_options`)

Add a new key to the existing settings array:

```php
'service_types' => [
    [
        'slug'    => 'standard',
        'label'   => 'Standard Delivery',
        'markup'  => 0,        // percentage
        'default' => true,
    ],
    [
        'slug'    => 'urgent-48',
        'label'   => 'Urgent — 48 Hours',
        'markup'  => 15,       // percentage
        'default' => false,
    ],
    [
        'slug'    => 'urgent-24',
        'label'   => 'Urgent — 24 Hours',
        'markup'  => 25,       // percentage
        'default' => false,
    ],
],
```

The admin should be able to edit the label and markup percentage for each tier on the Global Settings page (`admin/views/settings-page.php`). The number of tiers (3) can be fixed — no need for a dynamic repeater.

#### Cart Item Meta

Add to the `$cart_item_data['bannercalc']` array:

```php
'service_type'       => 'standard',   // slug
'service_markup_pct' => 0,            // percentage applied
'service_markup_amt' => 0.00,         // calculated £ amount
```

### Pricing Engine Changes

**File:** `includes/class-pricing-engine.php`

Update the `calculate()` method signature to accept service type:

```php
public function calculate(array $config, float $width_m, float $height_m, array $selected_attributes, ?array $preset = null, string $service_type = 'standard'): array
```

Add a new step **after step 5 (add-ons)** and **before the final price**:

```
6. SERVICE TYPE MARKUP
   service_markup_pct = lookup from global settings by service_type slug
   service_markup_amt = (base_price + addons_total) × (service_markup_pct / 100)

7. FINAL PRICE
   calculated_price = base_price + addons_total + service_markup_amt
```

Return array gains: `'service_type'`, `'service_markup_pct'`, `'service_markup_amt'`

### Frontend Changes

#### JavaScript (`frontend/js/frontend.js`)

- Add `serviceType` to the state object (default: `'standard'`).
- The service types config should be passed from PHP to JS via the `data-config` JSON on the configurator wrapper. Add a `serviceTypes` array to the config output in `ProductDisplay::render_configurator()`.
- In `calculatePrice()`, after computing `addonsTotal`, apply the service markup: `serviceMarkup = (basePrice + addonsTotal) * (serviceMarkupPct / 100)`, then `calculatedPrice = basePrice + addonsTotal + serviceMarkup`.
- In `updatePriceDisplay()`, if service markup > 0, show a new row in the price breakdown between add-ons and total: "Urgent 48h (+15%): £X.XX".
- Add a new hidden field: `bannercalc[service_type]`.

#### Template — New UI Element

Add a service type selector to the **price section area** (inside the extras card / Card 3, just above the pricing block). This should render as a **pill button row** (consistent with existing attribute pill styling using `.bannercalc-pill` classes). Each option shows the label and the markup percentage if > 0.

Use the existing `.bannercalc-attr-pills` and `.bannercalc-pill` CSS classes. The "Urgent" options should show a subtle warning/orange tint when active (using the existing `--bp-orange` / `--bp-warning` brand colour).

```html
<!-- Service Type Selector -->
<div class="bannercalc-attribute bannercalc-attr--service-type">
    <div class="bannercalc-attr-header">
        <span class="bannercalc-attr-label">Delivery Speed</span>
    </div>
    <div class="bannercalc-attr-pills">
        <button type="button" class="bannercalc-pill active" data-service="standard">Standard</button>
        <button type="button" class="bannercalc-pill" data-service="urgent-48">48 Hours (+15%)</button>
        <button type="button" class="bannercalc-pill" data-service="urgent-24">24 Hours (+25%)</button>
    </div>
    <input type="hidden" name="bannercalc[service_type]" value="standard">
</div>
```

#### Cart Handler Changes

**File:** `includes/class-cart-handler.php`

In `add_cart_item_data()`:
- Read `$_POST['bannercalc']['service_type']` and sanitise.
- Look up the markup percentage from `Plugin::get_settings()['service_types']`.
- Pass the `service_type` parameter to `PricingEngine::calculate()`.
- Store `service_type`, `service_markup_pct`, and `service_markup_amt` in cart item meta.

In `display_cart_item_data()`:
- If service type is not `'standard'`, show it as a line item in the cart summary (e.g., "Delivery: Urgent — 48 Hours (+15%)").

In `display_order_item_meta()`:
- Same — display the service type if not standard.

### Admin Settings Page Changes

**File:** `admin/views/settings-page.php`

Add a new section "Service Types / Delivery Speed" with three rows, each having:
- Label (text input)
- Markup % (number input)
- A "Default" radio button

**File:** `admin/class-settings-page.php`

Update `sanitize_settings()` to handle the `service_types` array.

---

## REQUIREMENT 2: Price Impact for Hemming & Pole Pockets

### What It Is

Hemming and Pole Pockets already exist as enabled/disabled attributes per category, and already render on the frontend when enabled. However, they currently have **no price impact** — all their term modifiers are `0`. This requirement adds the ability to set a flat price for each.

### What Already Exists (DO NOT REBUILD)

The infrastructure is already in place:
- `attribute_pricing` in category config supports `pricing_type` (`fixed`, `per_sqft`, `percentage`) and per-term `values` (modifier amounts).
- `PricingEngine::calculate()` already iterates over selected attributes and applies modifiers.
- The frontend JS `calculatePrice()` already mirrors this logic.
- The admin `categories-page.php` already renders per-attribute pricing fields.

### What Needs to Change

This is primarily a **data/config task**, not a code task. The agent should verify that:

1. **Admin UI** (`admin/views/categories-page.php`): The per-attribute pricing section already shows pricing type dropdown and per-term modifier inputs for every enabled attribute. Verify that when `pa_hemming` and `pa_pole-pockets` are enabled for a category, their pricing fields appear and are saveable. If the admin UI is working correctly, no code changes are needed here.

2. **Verify the pricing flow end-to-end:**
   - Set `pa_hemming` pricing_type to `fixed`, and set modifier values (e.g., `yes` → `5.00`, `taped-edge` → `2.50`, `no` → `0`).
   - Set `pa_pole-pockets` pricing_type to `fixed`, and set modifier values (e.g., `top-only` → `4.99`, `top-bottom` → `7.99`, `none` → `0`).
   - Confirm the frontend JS `calculatePrice()` picks up these modifiers and adds them to the price.
   - Confirm `PricingEngine::calculate()` on the server side also applies them.
   - Confirm the price display shows the add-ons row when these are selected.

3. **If the pricing fields are NOT appearing in admin for hemming/pole pockets**, debug the `categories-page.php` template. The attribute pricing section should iterate over `$config['enabled_attributes']` and for each one, render pricing_type dropdown + per-term value inputs. This may be a template rendering bug — the data structure supports it.

4. **Frontend price modifier display**: The `attribute-selector.php` template already appends "+£X.XX" to pills/options when a modifier > 0. Verify this works for hemming pills and pole pocket dropdown options. If modifiers are `0` in the saved config, no prices will show — this is expected until the admin sets them.

### Summary

This requirement should need **minimal to zero code changes** if the existing infrastructure is working. The agent should:
- Verify the admin UI renders pricing fields for hemming and pole pockets when they are enabled.
- Set test values and confirm end-to-end pricing works.
- Fix any bugs found in the flow.
- If the admin pricing fields are not rendering, trace the issue in `categories-page.php` and fix the template.

---

## REQUIREMENT 3: Professional Design Services (Global Add-on)

### What It Is

A **global** optional add-on available on all product categories. When the customer opts in, a flat fee of **£14.99** is added to the order. This represents professional design services offered by Banner Printing UK.

This is conceptually similar to Service Type (Requirement 1) — it is plugin-managed, not a WooCommerce taxonomy attribute.

### Data Structure Changes

#### Global Settings (`bannercalc_settings`)

Add:

```php
'design_service' => [
    'enabled' => true,
    'label'   => 'Professional Design Service',
    'price'   => 14.99,
    'description' => 'Our team will create a professional design for your banner',
],
```

#### Cart Item Meta

Add:

```php
'design_service'       => false,   // boolean — opted in?
'design_service_price' => 0.00,    // £14.99 or 0
```

### Pricing Engine Changes

Add as step 5.5 (after attribute add-ons, before service type markup):

```
5.5 DESIGN SERVICE
    if design_service === true:
        design_service_price = global_settings.design_service.price
        addons_total += design_service_price
```

The design service price should be added to `addons_total` so that it is ALSO subject to service type markup (if urgent delivery is selected, the design service cost should also be marked up).

Return array gains: `'design_service'` (bool), `'design_service_price'` (float).

### Frontend Changes

#### UI Element

Render as a **toggle switch** (consistent with the Cable Ties toggle pattern using `.bannercalc-toggle-switch`, `.bannercalc-switch`, `.bannercalc-switch-slider` CSS classes). Place it inside Card 3 (extras card), above the design accordion or above the service type selector.

```html
<div class="bannercalc-attribute bannercalc-attr--design-service">
    <div class="bannercalc-toggle-switch">
        <div class="bannercalc-attr-header" style="margin-bottom:0;">
            <span class="bannercalc-attr-label">Professional Design Service</span>
        </div>
        <label class="bannercalc-switch">
            <input type="checkbox" class="bannercalc-toggle-input" id="bannercalc-design-service">
            <span class="bannercalc-switch-slider"></span>
        </label>
        <span class="bannercalc-switch-label">No</span>
    </div>
    <p class="bannercalc-design-service-desc">Our team designs your banner — £14.99</p>
    <input type="hidden" name="bannercalc[design_service]" value="0">
</div>
```

When toggled on, the price display should show "Professional Design: +£14.99" as a line item in the add-ons area.

#### JavaScript

- Add `designService` (boolean) to state.
- Pass the design service config via `data-config` JSON.
- In `calculatePrice()`, if `designService` is true, add the price to `addonsTotal`.
- Bind the toggle change event to update state and recalculate.
- Add hidden field `bannercalc[design_service]`.

#### Cart & Order Display

- In `CartHandler::add_cart_item_data()`: read `$_POST['bannercalc']['design_service']`, cast to boolean.
- In `CartHandler::display_cart_item_data()`: show "Professional Design Service: +£14.99" if opted in.
- In `CartHandler::display_order_item_meta()`: same.

### Admin Settings Page

Add a "Professional Design Service" section to the Global Settings page with:
- Enable/disable checkbox
- Label (text)
- Price (number input)
- Description (text)

---

## REQUIREMENT 4: Visual Banner Preview (Product Image Tab)

### What It Is

An interactive SVG-based banner preview that shows the customer's selected attributes (eyelets, pole pockets, hemming, cable ties) rendered visually on a proportionally-scaled rectangle representing their banner.

This preview displays **in place of the product image gallery** on the single product page, as a **second tab**. By default, the product image shows. When the customer starts selecting options, a tab appears allowing them to switch to the "Banner Settings" visual preview.

### Where It Renders

On WooCommerce single product pages, the product image is in the `.woocommerce-product-gallery` container (left column). This feature adds a **tab switcher** above or inside that gallery area:

- **Tab 1: "Product Image"** (default, active) — Shows the normal WooCommerce product gallery.
- **Tab 2: "Your Banner"** — Shows the interactive SVG preview reflecting the user's current selections.

When Tab 2 is active, the product gallery is hidden and the SVG preview is shown in its place. The preview panel should fill the same width as the product gallery.

### Implementation Approach

#### PHP Hook

In `ProductDisplay`, hook into `woocommerce_before_single_product_summary` (which fires before the right-column summary but after the left-column gallery wrapper opens) or use `woocommerce_product_thumbnails` to inject content. Alternatively, the most reliable approach:

1. Hook `woocommerce_before_single_product` to inject the tab switcher and preview container **above** the gallery.
2. Use JavaScript to relocate the preview container into the gallery column if needed, similar to how the plugin already relocates the Personalize link into the design accordion.

The preview container HTML:

```html
<div class="bannercalc-preview-tabs" id="bannercalc-preview-tabs" style="display:none;">
    <button class="bannercalc-preview-tab active" data-tab="gallery">Product Image</button>
    <button class="bannercalc-preview-tab" data-tab="preview">Your Banner</button>
</div>
<div class="bannercalc-preview-panel" id="bannercalc-preview-panel" style="display:none;">
    <div class="bannercalc-preview-canvas" id="bannercalc-preview-canvas">
        <!-- SVG rendered by JavaScript -->
    </div>
    <div class="bannercalc-preview-legend">
        <!-- Legend items for active attributes -->
    </div>
</div>
```

The tabs should only appear once the customer has selected at least one visual attribute (eyelets, pole pockets, or hemming). Before that, just the normal product image shows.

#### JavaScript SVG Rendering

Add a new module/section to `frontend/js/frontend.js` (or a separate file `frontend/js/preview.js` that is conditionally enqueued).

The SVG rendering logic should:

1. **Read the current state** from `BannerCalc.state` — specifically `widthMetres`, `heightMetres`, and `selectedAttributes`.

2. **Scale the banner rectangle** to fit the available container width (the product gallery column, typically ~48% of the product page). Use the aspect ratio from the selected size. The banner rectangle should maintain correct proportions.

3. **Draw the base banner** as a white/light-gray filled `<rect>` with a subtle shadow, plus placeholder text showing dimensions (e.g., "4ft × 2ft" and "YOUR DESIGN").

4. **Render attribute overlays** based on selected values:

##### Eyelets (SVG circles at edge positions)

| Value | Rendering |
|---|---|
| `none` | No circles |
| `4-corners` / `4 Corners` | 4 circles at banner corners, inset ~12px |
| `3-top-3-bottom` / `3 Top 3 Bottom` | 3 evenly spaced circles along top edge + 3 along bottom |
| `4-top-4-bottom` / `4 Top 4 Bottom` | 4 evenly spaced circles along top + 4 along bottom |
| `50cm-apart` / `50cm Apart` / `Every 50cm` | Circles spaced every 50cm (1.64ft) around all edges, count calculated from actual banner dimensions |
| `top-only` / `Top Only` | Evenly spaced circles along top edge only |
| `all-sides` / `All Sides` | Evenly spaced circles on all 4 edges |
| `2-top-corners` / `2 Top Corners` | 2 circles at top-left and top-right corners |
| `4-at-top` / `4 at Top` | 4 evenly spaced circles along top edge |

Each eyelet: outer circle (stroke `--bp-cyan`, no fill) + inner dot (filled `--bp-cyan` at 50% opacity). Radius ~5px.

##### Pole Pockets (SVG rects as shaded strips)

| Value | Rendering |
|---|---|
| `none` | Nothing |
| `top-only` / `Top Only` | Hatched/shaded strip along top edge |
| `top-bottom` / `Top & Bottom` | Strips along top and bottom |
| `1-5-inches-top-bottom` | Strips top+bottom, depth proportional to 1.5" vs banner height |
| `3-inches-top-bottom` | Strips top+bottom, depth proportional to 3" |
| `top-bottom-2-5-inches` | Strips top+bottom, depth proportional to 2.5" |
| `top-bottom-3-5-inches` | Strips top+bottom, depth proportional to 3.5" |
| `2-5-inches-left-right` | Strips on left+right edges |
| `all-sides` | Strips on all 4 edges |

Pockets render as: `<rect>` filled with a hatched SVG `<pattern>` (diagonal lines in `--bp-magenta` at ~25% opacity), with a magenta stroke border. Label text showing the pocket depth inside.

##### Hemming (SVG dashed inner border)

| Value | Rendering |
|---|---|
| `no` / `No` | Nothing |
| `yes` / `Yes` | Dashed inner rectangle, inset ~6px, stroke `--bp-orange`, dash pattern `4 3` |
| `taped-edge` / `Taped Edge` | Same as Yes but with a dotted pattern instead (dash `2 2`) to differentiate |

##### Cable Ties (small loops at eyelet positions)

Only render if BOTH cable ties = `yes` AND eyelets ≠ `none`. Draw small dashed green circles extending outward from each eyelet position. Stroke `--bp-green`.

##### Finish (subtle overlay)

| Value | Rendering |
|---|---|
| `matte` | No overlay (flat fill) |
| `gloss` | Diagonal gradient overlay (white at ~12% opacity band across the banner) |
| `satin` / `textured` | No distinct visual (too subtle to differentiate) |

5. **Dimension labels**: Show width label below the banner and height label to the left, using `--bp-muted` colour and the mono font.

6. **Legend**: Below the SVG, show small coloured dots with labels for each active attribute type (Eyelets = cyan, Pole Pockets = magenta, Hemming = orange, Cable Ties = green). Only show legend items for attributes that are currently active/visible on the banner.

#### Size Display Consistency

**Important**: The banner preview dimensions must use the same value order as preset sizes. In preset sizes, the format is always `Width × Height` (e.g., "4ft × 2ft" means 4ft wide, 2ft tall). The SVG should render a landscape rectangle for this.

When the first value in the preset label is larger than the second, it is width. When the first is smaller, it is still width — the label format is always `Width × Height` as entered in the preset configuration. The SVG must respect this and draw the rectangle accordingly (wider than tall for "4ft × 2ft", or taller than wide for "2ft × 4ft" if such a preset existed).

For custom sizes: the first input field is Width, the second is Height. Same rule applies.

#### Triggering the Preview Tab

- On initial page load: only the product image shows, no tabs visible.
- When the user selects any attribute value for eyelets, pole pockets, or hemming (the three most visual attributes), the tab switcher fades in.
- The preview SVG updates in real-time whenever any attribute or size changes.
- Switching between tabs shows/hides the gallery vs preview. Use CSS `display:none`/`display:block` toggling.

#### CSS

Add styles for the preview tab switcher and panel to `frontend/css/frontend.css`. Use existing brand variables and keep consistent with the configurator card styling:

- Tab buttons: similar to `.bannercalc-toggle-btn` styling
- Preview panel: white background, same border-radius as configurator cards
- Legend: small flex row at bottom

---

## REQUIREMENT 5 (Meta): Version Bump & Documentation Update

After implementing all features:

1. Bump `BANNERCALC_VERSION` to `'1.5.0'` in `bannercalc.php`.
2. Update the price calculation formula in documentation to include Service Type Markup and Design Service steps.
3. Update the Feature Summary checklist to include:
   - `[x] Service type markup (standard/urgent delivery with % pricing)`
   - `[x] Professional Design Service global add-on`
   - `[x] Hemming and Pole Pocket price modifiers (verified working)`
   - `[x] Interactive SVG banner preview on product page`
4. Update the Data Architecture section for new fields in global settings, cart item meta, and order item meta.

---

## Implementation Order (Recommended)

1. **Requirement 2** first (Hemming/Pole Pocket pricing) — Smallest change, mostly verification. Gets the agent familiar with the pricing flow.
2. **Requirement 1** (Service Type Markup) — New global attribute with full stack implementation.
3. **Requirement 3** (Design Service) — Similar pattern to Requirement 1, slightly simpler.
4. **Requirement 4** (Visual Banner Preview) — Largest feature, purely frontend, no pricing impact.
5. **Requirement 5** (Version bump + docs).

---

## Testing Checklist

After implementation, verify:

- [ ] Standard price calculation still works (no regression)
- [ ] Hemming with a modifier > 0 adds to the price on frontend and in cart
- [ ] Pole Pocket with a modifier > 0 adds to the price on frontend and in cart
- [ ] Service type "Urgent 48h" adds 15% markup to (base + addons) in frontend display
- [ ] Service type markup is applied server-side in `CartHandler` and shows in cart
- [ ] Service type shows in order emails and admin order detail
- [ ] Professional Design Service toggle adds £14.99 when on
- [ ] Design service is subject to service type markup (14.99 is included before % is applied)
- [ ] Design service shows in cart, order, and emails
- [ ] Banner preview SVG renders when eyelets are selected
- [ ] Banner preview scales correctly for different preset sizes
- [ ] Pole pockets, hemming, and cable ties render correctly on the SVG
- [ ] Tab switcher appears only after a visual attribute is selected
- [ ] Switching tabs shows/hides product image vs SVG preview
- [ ] All new hidden fields submit correctly on Add to Cart
- [ ] Admin Global Settings page shows service type and design service fields
- [ ] All existing functionality unchanged (presets, custom sizes, units, existing attributes)
