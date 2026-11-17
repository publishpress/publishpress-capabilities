---
title: "WPBakery Permissions in PublishPress Capabilities"
order: 59
doc_category: cme
section: Integrations
---

# WPBakery Permissions in PublishPress Capabilities

WPBakery is a popular WordPress page builder. Access to the WPBakery interface is controlled by the “Backend Editor and “Frontend Editor” links you see in the screenshot below. If you are creating [custom role using PublishPress Capabilities](https://publishpress.com/knowledge-base/create-or-copy-user-roles/), sometime you might find that users in your new role can not see this WPBakery option.

![](https://docs-media.publishpress.com/publishpress-capabilities/wpbakery-editor-button-a9bcffc584.webp)

This issue arises because WPBakery restricts editing access within its interface.

To solve this limitation, navigate to the WPBakery Role Manager and ensure that both the backend and frontend editors are enabled for your custom role.

![](https://docs-media.publishpress.com/publishpress-capabilities/wpbakery-role-manager-be4646c970.webp)

If you cannot find your custom role in the list, please ensure your role has these 3 capabilities:

- *[edit_posts](https://publishpress.com/links/edit_posts)*
- *[edit_pages](https://publishpress.com/links/edit_pages)*
- *[manage_options](https://publishpress.com/links/manage_options)*

It’s important to be aware that the “*[manage_options](https://publishpress.com/links/manage_options)*” capability is quite broad, and various plugins may rely on it. As a result, enabling this capability might unexpectedly reveal unwanted menus in your WordPress dashboard.

To address this issue, consider utilizing the “[Admin Menus](https://publishpress.com/knowledge-base/admin-menus-screen/)” feature available in [PublishPress Capabilities Pro](https://publishpress.com/links/capabilities). This feature allows you to selectively hide specific menus for a particular role, effectively managing and customizing the dashboard experience for your users.
