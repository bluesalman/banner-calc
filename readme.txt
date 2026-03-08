=== BannerCalc ===
Contributors: bannercalc
Tags: woocommerce, product configurator, pricing calculator, banner, custom size
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 1.5.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Product Configurator & Pricing Engine for WooCommerce. Area-based pricing with configurable attributes, preset sizes, and custom dimensions.

== Description ==

BannerCalc replaces the default WooCommerce variation-based product configuration system with a unified pricing engine. It uses WooCommerce's existing attribute taxonomy as a data source but replaces both the admin product editor UI and the frontend product page UI with custom interfaces.

**Features:**

* Area-based pricing (price per square foot/metre)
* Preset sizes + custom dimension entry
* Multi-unit support (mm, cm, inch, ft, m) with live conversion
* Configurable attribute add-ons (fixed, per-sqft, or percentage pricing)
* Category-level defaults with per-product overrides
* Styled button/card selectors instead of dropdown menus
* Full cart, checkout, and order integration
* WooCommerce HPOS compatible

== Installation ==

1. Upload the `bannercalc` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > BannerCalc to configure global settings
4. Edit a product category and configure BannerCalc defaults
5. Products in configured categories will automatically use the BannerCalc configurator

== Frequently Asked Questions ==

= Does this work with Variable products? =

No. BannerCalc is designed for Simple products. All pricing is calculated dynamically — no WooCommerce variations are needed.

= Can I have different pricing per product? =

Yes. You can set category-level defaults and then override specific values per product using the product metabox.

== Changelog ==

= 1.5.0 =
* Service type / delivery speed markup (standard, urgent-48, urgent-24)
* Professional design service option with 3-way selector (Upload Files / Design Online / Pro Design)
* Admin per-attribute pricing UI on category config page
* Interactive SVG banner preview showing eyelets, pole pockets, hemming & cable ties
* Pricing engine extended with design service add-on and service markup steps
* Cart, checkout & order display updated for new fields
* Version bump to 1.5.0

= 1.0.0 =
* Initial release
