=== Popsi Cart Drawer for WooCommerce ===
Contributors: developerhasan99
Tags: woocommerce, cart, side cart, upsell, cart drawer
Requires Plugins: woocommerce
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful WooCommerce side cart plugin with an AOV Booster, cart drawer, upsells, rewards progress bars, announcements, and full design control.

== Description ==

**Popsi Cart Drawer for WooCommerce** is a premium WooCommerce side-cart plugin that transforms your store's cart experience into a conversion-optimized, fully customizable off-canvas drawer. It is designed to boost your Average Order Value (AOV) and reduce cart abandonment with a suite of powerful features — all manageable from a beautiful, no-code admin panel.

= Core Features =

**🛒 Slide-in Cart Drawer**
A smooth, animated off-canvas cart drawer that slides in from the right. Works site-wide and integrates seamlessly with WooCommerce's AJAX add-to-cart flow.

**📈 Rewards Progress Bar (AOV Booster)**
Motivate customers to spend more with a fully configurable rewards/progress bar system. Define multiple bars with custom thresholds, icons (truck, tag, gift), and reward labels like "Free Shipping" or "10% Discount."

**🤩 Upsells & Product Recommendations**
Show contextual upsell products inside the cart drawer. Source products from best sellers, newest arrivals, WooCommerce upsells, cross-sells, related products, or a specific category. Supports variable products with a variation selector.

**🏷 Coupon / Discount System**
An elegant, accordion-style coupon input inside the cart drawer. Apply or remove coupons without leaving the page.

**📢 Announcement Bar**
A configurable top-bar inside the cart drawer to display countdown timers, promotions, or urgency messages.

**🔒 Trust Badges**
Display a custom trust/payment badge image at the bottom of the cart to build buyer confidence.

**🎨 Full Design Customization**
Total control over colors, fonts (theme inheritance), button radius, icon style, cart bubble, and more — all from the admin panel live preview.

**🛍 Cart Icon**
Use `[popsi_cart_icon]` shortcode or auto-inject the cart icon into any navigation menu.

**🌍 Translation Ready**
All user-facing strings are translatable. The text domain is `popsi-cart-drawer`.

= Use Cases =

* Increase Average Order Value with smart reward milestones.
* Reduce friction at checkout with a non-interruptive slide-in cart.
* Promote upsells without redirecting customers away from their current page.
* Keep customers engaged with urgency timers and announcements.

= Requirements =

* WordPress 6.0 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the `popsi-cart-drawer` folder to the `/wp-content/plugins/` directory, or install through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **Popsi Cart** in the WordPress admin menu.
4. In the **Placement** tab, enable **"Enable Cart Drawer Site-wide"**.
5. Customize the design, rewards, upsells, and other settings to match your store.

== Frequently Asked Questions ==

= Does Popsi Cart Drawer for WooCommerce work without WooCommerce? =

No. Popsi Cart Drawer for WooCommerce is built exclusively for WooCommerce and requires it to be installed and activated.

= How do I display the cart icon in my header or navigation? =

You can use the `[popsi_cart_icon]` shortcode anywhere on your site. Alternatively, go to **Popsi Cart → Placement → Menu Placement** to automatically inject the cart icon into a specific navigation menu.

= Can I change the colors and design of the cart? =

Yes! Go to **Popsi Cart → Design** and customize the background color, text color, button colors, border radius, cart icon style, and more. Changes are reflected in the live preview on the right.

= Does Popsi Cart Drawer for WooCommerce support variable products in upsells? =

Yes. When a variable product is shown as an upsell recommendation, a variation selector (dropdown) is displayed so the customer can choose their option before adding to cart.

= Is the plugin compatible with AJAX add-to-cart? =

Yes. Popsi Cart Drawer for WooCommerce intercepts standard WooCommerce add-to-cart form submissions and handles them via AJAX, then opens the cart drawer automatically.

= Can I run multiple progress bars? =

Yes! Popsi Cart Drawer for WooCommerce supports multiple reward progress bars per cart. Each bar can have its own thresholds, type (subtotal or quantity), and reward checkpoints.

== Screenshots ==

1. Cart Drawer — frontend slide-in cart with rewards progress bar, items, upsells, and checkout footer.
2. Admin Panel — Placement tab with live preview and key toggles.
3. Rewards Tab — configure multiple progress bars with custom thresholds and icons.
4. Design Tab — full color, icon, and styling customization with a live preview.
5. Upsells Tab — configure product recommendation source and display options.

== Upgrade Notice ==

= 1.1.3 =
This release includes reliability and performance improvements for cart refresh behavior.
