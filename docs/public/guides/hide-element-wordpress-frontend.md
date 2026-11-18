---
title: "Hide Any Element in the WordPress Frontend (Pro)"
order: 37
doc_category: cme
section: Frontend Features
---

# Hide Any Element in the WordPress Frontend (Pro)

This feature requires the Pro version of the PublishPress Capabilities plugin.

The Pro version of [PublishPress Capabilities](https://publishpress.com/links/capabilities) has an option called Frontend Features which allows you to hide specific elements on frontend of WordPress sites. If you can locate the relevant ID or Class, you can hide these elements.

In this example, we’ll hide the “Edit with Elementor” option on the frontend for users with the “Editor” role. You can see the “Edit with Elementor” link in this screenshot below:

![Hide Any Element in the WordPress Frontend](https://docs-media.publishpress.com/publishpress-capabilities/edit-with-elementor-toolbar-c272d81508.webp)

Here are the steps on how to hide “Edit with Elementor” on frontend:

- First, you need to locate the ID or Class associated with the “Edit with Elementor” toolbar. You can do this by right-clicking and selecting “Inspect”.
- As show in this screenshot,, the ID is “wp-admin-bar-elementor_edit_page.”

![Hide Any Element in the WordPress Frontend with CSS](https://docs-media.publishpress.com/publishpress-capabilities/id-elementor-toolbar-f4c04390bf.webp)

- In your WordPress admin area, navigate to Capabilities > Frontend Features.
- Choose your role from the top left corner.
- Provide a title.
- In the Features field, select “Hide IDs or Classes” and paste the ID there.
- Specify whether it should apply to the entire site or only a selected page.
- Click “Add New” and then “Save Changes”.

![Hide Any Element in the WordPress Frontend with Custom CSS](https://docs-media.publishpress.com/publishpress-capabilities/add-id-on-frontend-feature-64b21e9bfa.webp)

Once you have completed these steps, it will hide the “Edit with Elementor” option on the frontend when an editor visits a specific post..

![Hidden element on the WordPress frontend](https://docs-media.publishpress.com/publishpress-capabilities/edit-with-elementor-hidden-718b8f5797.webp)

Please note that this action will solely hide it on the frontend and in the WordPress admin area. If you wish to hide elements in the WP-admin area, you should refer to instructions on [how to hide elements in the WordPress admin](https://publishpress.com/knowledge-base/hide-element-wordpress-admin/).
