---
title: "Block Access to URLs in the WordPress Admin (Pro)"
order: 16
doc_category: cme
section: Admin Features
---

# Block Access to URLs in the WordPress Admin (Pro)

This feature requires the Pro version of the PublishPress Capabilities plugin.

The Pro version of the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin allows to you block access to any URL in the WordPress admin area. This is possible using the “[Admin Features](https://publishpress.com/knowledge-base/admin-features-screen/)” option in the plugin.

In this example, we will block access to the Settings screen in the [PublishPress Series](https://publishpress.com/links/series) plugin.

- Go to “Capabilities” and then “Admin Features”.
- Click the “Block by URL” tab.
- Choose the user role for which you want to hide the search box.
- Give a Label to this setting.
- Enter the URL of the screen you want to block.
- Click “Add New”.

![Block feature by URL](https://docs-media.publishpress.com/publishpress-capabilities/block-feature-99466a5333.webp)

*Block Series*

- Log in to your site as a user in the role you have blocked. When you try to access the URL, you will see the message, “You do not have permission to access this page.”

![Block access to URL](https://docs-media.publishpress.com/publishpress-capabilities/forbidden-message-0a53f9a80c.webp)

If you want to try this feature before creating your own use cases, the plugin does provide five examples, as you can see in the screenshot below.

![Block by URL](https://docs-media.publishpress.com/publishpress-capabilities/block-url-example-0819e4467e.webp)
