---
title: "Hide Any Element in the WordPress Editor (Pro)"
order: 35
doc_category: cme
---

# Hide Any Element in the WordPress Editor (Pro)

This feature requires the Pro version of the PublishPress Capabilities plugin.

The Pro version of the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin allows to hide any element inside the WordPress post editor. If you can find the CSS class or ID, then you can hide that element.

Let’s see an example of how this feature works. In the screenshot below, you can see two metaboxes:

1. “Yoast SEO” from the Yoast SEO plugin.
2. “Editorial Comments” from the [PublishPress Planner](https://publishpress.com/links/planner) plugin.

![Metaboxes](https://docs-media.publishpress.com/publishpress-capabilities/metaboxes-06fa6f4fcd.webp)

*Metaboxes*

To hide one of these metaboxes, find the ID of this element. In this example, I’m using the “Inspector” tool in my browser. This image below shows that the ID is `wpseo_meta`.

![Wpseo Meta](https://docs-media.publishpress.com/publishpress-capabilities/wpseo-meta-a9ddb28028.webp)

*Wpseo Meta*

- Go to “PublishPress”, then “Editor Features” in your WordPress admin menu.
- In the top-left corner of the screen, Choose the user role you want to hide the element for, as in this next screenshot:

![Editor Feature Restrictions](https://docs-media.publishpress.com/publishpress-capabilities/editor-restrictions-role-ab40b88f91.webp)

*Hide Metaboxes Roles*

- Scroll down to the bottom of the screen and find the “Add New Gutenberg Restrictions” custom items area.
- In the “Label” box, enter a name for this restriction.
- In the “Element IDs or Classes” box, enter the ID or class.
- Click the “Add New” button.

![Hide the Yoast SEO metabox](https://docs-media.publishpress.com/publishpress-capabilities/wp-seo-button-42932261a8.webp)

*Hide Yoast Div*

- Place a red X for any element you want to hide. In the image below, we are hiding both the Yoast SEO box.

![Hide the Yoast SEO box](https://docs-media.publishpress.com/publishpress-capabilities/hide-yoast-seo-964fa25120.webp)

*Yoast Seo Red*

- Click “Save Post Restrictions”.
- Visit the post editing screen as a user in the role you selected. You will no longer see the elements you selected.
