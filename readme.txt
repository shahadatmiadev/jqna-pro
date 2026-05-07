=== JQNA Pro – Q&A System ===
Contributors:       Shahadat Mia
Tags:               q&a, faq, woocommerce, question answer, islamic
Requires at least:  5.8
Tested up to:       6.5
Requires PHP:       7.4
Stable tag:         1.0.0
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

A secure Q&A system gated by WooCommerce order verification. Features accordion FAQ, AJAX pagination, category sidebar, and admin moderation.

== Description ==

**JQNA Pro** lets you publish a protected Q&A page on your WordPress / WooCommerce site.

= How it works =

1. A user visits the page containing the `[jqna_pro]` shortcode.
2. They are presented with a 3-field verification form:
   * WordPress username or email address
   * Their account password
   * The phone number used on a **completed** WooCommerce order
3. Only after all three checks pass do they gain access.
4. Inside, they see an accordion FAQ (10 questions per page, AJAX-powered) and a category sidebar.
5. They can submit their own questions; admins moderate, answer, and publish from the dashboard.

= Features =

* WooCommerce completed-order phone verification
* AES-256 encrypted access cookie (no PHP sessions)
* Accordion FAQ with AJAX pagination (10 per page)
* Category sidebar with live question counts
* Category filter with Reset button
* Question submission form with admin moderation queue
* Admin can Add / Edit / Approve / Delete questions
* Dynamic Categories menu (like WordPress "Posts")
* Default "Islamic" category created on activation
* Full nonce, sanitisation, and escaping throughout
* WordPress.org coding standards compliant

= Shortcode =

Place `[jqna_pro]` on any page.

== Installation ==

1. Upload the `jqna-pro` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins > Installed Plugins**.
3. Ensure WooCommerce is installed and active.
4. Create a page and insert `[jqna_pro]` in the content.
5. Go to **JQNA Pro > Categories** to manage categories.
6. Go to **JQNA Pro > Add New** to add Q&A entries.

== Frequently Asked Questions ==

= Does this require WooCommerce? =
Yes. The phone validation step queries WooCommerce orders.

= Which user roles can access the content? =
Only users with the `subscriber` role (the default role created by most registration plugins).

= Where does the "Islamic" category come from? =
It is created automatically on activation as the default category. You can rename it or add more via **JQNA Pro > Categories**.

== Screenshots ==

1. Login / verification form
2. Two-column FAQ layout with category sidebar
3. Admin questions list
4. Add / edit question page

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
