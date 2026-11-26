---
title: "Troubleshooting the Admin Menus Screen (Pro)"
order: 8
doc_category: cme
section: Admin Menus
---

# Troubleshooting the Admin Menus Screen (Pro)

This feature requires the Pro version of the PublishPress Capabilities plugin.

This guide can help you solve issues with the “Admin Menus” screen in [PublishPress Capabilities](https://publishpress.com/links/capabilities).

One issue that occurs with “Admin Menus” is that you add an X for a menu link, but this link still shows to the user.

This can happen with some plugins that use a non-standard approach to the sub-menu / second level links. In these cases, the solution can be to block the top-level / first level links.

However, there is a workaround that allows you target specific menu links.

- Add the submenu’s CSS ID/class to the [Hide CSS Element](https://publishpress.com/knowledge-base/hide-element-wordpress-admin/) area inside the [Admin Features](https://publishpress.com/knowledge-base/how-to-use-admin-features/) screen.
- As an extra precaution, you can dd the menu URL you want to remove to [Blocked By URL](https://publishpress.com/knowledge-base/block-access-urls/) inside the [Admin Features](https://publishpress.com/knowledge-base/how-to-use-admin-features/) screen.

Please refer to the below example for a more detailed explanation.

For example, you have an e-Commerce website using WordPress and WooCommerce. You install [PDF Invoices & Packing Slips for WooCommerce](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/) plugin. This plugin adds a sub-menu under “WooCommerce” called “PDF Invoices”.

![Link for WooCommerce PDF plugin](https://docs-media.publishpress.com/publishpress-capabilities/2023-02-02-10-13-1e5b243df4.webp)

You have a user role “Report Manager” that only has access to the “Reports” sub-menu link. Nornally the solution is to limit this user role using “Admin Menus”, as in the screenshot below:

![WooCommerce admin menu](https://docs-media.publishpress.com/publishpress-capabilities/report-admin-menus-c7015e3538.webp)

However, when “Report Manager” user logs in, they are still able to see the “PDF Invoices” which they should not be able to see it.

![WooCommerce PDF link](https://docs-media.publishpress.com/publishpress-capabilities/unwanted-menu-e590898654.webp)

Our recommendation is to use the workaround that was explained above.

You need to find the main menu ID/Class. In this example the main menu ID for WooCommerce is **#toplevel_page_woocommerce**

![Find menu ID](https://docs-media.publishpress.com/publishpress-capabilities/top-level-menu-woocommerce-7e1df20e1a.webp)

Why we should find the main menu ID/Class? Because of the submenu that we want to hide, there will be no ID or class inside it. As you can see in the screenshot below, the “list item” HTML tag for the PDF Invoices sub-menu does not have an ID or class, so we should find the main menu ID/Class.

![Find menu ID / class](https://docs-media.publishpress.com/publishpress-capabilities/pdf-invoices-submenu-60fe89adc4.webp)

After you find the main menu ID, you will need to find the correct selector to that submenu. For example, in this case, correct selector would be **#toplevel_page_woocommerce ul li:nth-child(3)**

That selector is selecting **the third** **<li>** inside **<ul>** inside ID **toplevel_page_woocommerce**.

![CSS selector in WordPress admin menu](https://docs-media.publishpress.com/publishpress-capabilities/structure-of-pdf-invoice-e8512d5c6f.webp)

As you can see that there are 3 <li> elements inside <ul>, and the PDF Invoices submenu is on the third list. That’s why we select **:nth-child(3)**.

After we find the correct selector class for the submenu that we want to hide, we can [hide it using Admin Features](https://publishpress.com/knowledge-base/hide-element-wordpress-admin/).

![Admin Features](https://docs-media.publishpress.com/publishpress-capabilities/hide-css-pdf-invoice-e36e6aa586.webp)

This is the screenshot after we hide the submenu using the Hide CSS element.

![Hide a submenu](https://docs-media.publishpress.com/publishpress-capabilities/pdf-invoice-hidden-a09608cf51.webp)

In most cases, that’s all you need to do. However, if you want to be more secure, you can also block the direct access URL.

Since at this stage, your report manager indeed will not see the PDF Invoices submenu. But if they try to access the direct URL (***/wp-admin/admin.php?page=wpo_wcpdf_options_page***) they will still be able to access the PDF Invoice submenu.

You can add extra security using the [Block URL on Admin Features](https://publishpress.com/knowledge-base/block-access-urls/) option.

![Admin menu workaround added](https://docs-media.publishpress.com/publishpress-capabilities/pdf-invoice-url-128d9231bb.webp)

So whenever they tried to access the sub-menu, it will say “**You do not have permission to access this page**.”

![Admin menu workaround result](https://docs-media.publishpress.com/publishpress-capabilities/dont-have-access-pdf-invoice-7e8c7cd9cb.webp)
