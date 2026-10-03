=== Product Table ===
Contributors: wellplugins
Tags: woocommerce, product table, product list, bulk order, shop table
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create searchable WooCommerce product tables with a shortcode or display them on selected shop archive pages.

== Description ==

Product Table lets you build tables for WooCommerce products. Choose the columns, products, sort order, filters, and purchase controls for each table. Display a table with a shortcode or use it on selected WooCommerce shop and product archive pages.

Features include:

* Select products from all products, specific products, categories, tags, or brands when a product brand taxonomy is available.
* Exclude products or categories from all-product tables.
* Choose and reorder columns such as name, image, SKU, stock, price, description, attributes, quantity total, and buy controls.
* Add category, tag, attribute, product type, product visibility, or author filters where the corresponding data is available.
* Search, paginate, and sort product results.
* Show variable products with a variation dropdown, separate variation rows, or a link to the product page.
* Add one product or select multiple products to add to the cart together.
* Configure button text and placement, table headings and footer, sticky headers, product visibility, page size, and optional query caching.

The table scrolls horizontally when its columns do not fit a narrow screen. WooCommerce must be installed and active. The plugin does not require an external service.

== Installation ==

1. Install and activate WooCommerce.
2. Install and activate Product Table through **Plugins > Add New**, or upload the plugin folder to `wp-content/plugins/` and activate it.
3. Open **Products > Product Table** in the WordPress admin.
4. Select **Add New Table**, enter a name, configure the table, and save it.
5. For a shortcode table, copy the shortcode shown after saving and paste it into a page or post. To replace WooCommerce archive content, choose **Display on Shop Specific Page** and select the pages to use.

== Frequently Asked Questions ==

= How do I insert a table into a page? =

Create and save a table under **Products > Product Table**, then use its generated shortcode. For example, `[product_table id="123"]` displays table 123.

= Can I use a table on the shop or category page? =

Yes. Edit a table, select **Display on Shop Specific Page**, and enable the shop, product search, category, tag, attribute, or brand archive pages you want to replace. Brand archives require a registered `product_brand` taxonomy.

= Can customers purchase variable products from a table? =

Yes. Choose a variation dropdown or separate variation rows in the table settings. The link option sends customers to the product page to make their selection.

= Does uninstalling remove my tables and settings? =

Only if **Permanently delete all WooCommerce Product Table settings and data when deleting the plugin** is enabled in the plugin settings. Deactivating the plugin does not delete the data.

== Changelog ==

= 1.0.0 =

* Initial release of shortcode and WooCommerce archive product tables.

== Source Code ==

The readable source for the bundled admin JavaScript is included in the plugin's `src/` directory. The frontend JavaScript is in `assets/js/frontend.js`, and styles are in `assets/css/`. To rebuild the admin bundle, run `npm install` and `npm run build` in the plugin directory. The build dependencies are declared in `package.json` and locked in `package-lock.json`.
