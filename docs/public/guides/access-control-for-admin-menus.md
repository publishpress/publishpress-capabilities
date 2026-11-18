---
title: "Access Control for Admin Menu Links"
order: 1
doc_category: cme
section: Admin Menus
---

# Access Control for Admin Menu Links

The [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin allows you to control access to the links in your WordPress admin menu. You can add admin menu restrictions to each user role. You can do this via the “[Admin Menus](https://publishpress.com/knowledge-base/admin-menus-screen/)” screen in [PublishPress Capabilities](https://publishpress.com/links/capabilities).

- In the top-left corner of this screen, choose the role that you want to edit.

![Admin Menu role choosing](https://docs-media.publishpress.com/publishpress-capabilities/admin-menu-restrictions-ea174e4360.webp)

- Scroll down and you can enter a red X for any menu link that you don’t want users to access.

![Admin Menu Screens](https://docs-media.publishpress.com/publishpress-capabilities/admin-menu-screens-cf6798e26b.webp)

*Admin Menu Screens*

In the image below, users in the “Author” role will not be able see or use any of the menu links marked in red.

![Admin Menu](https://docs-media.publishpress.com/publishpress-capabilities/admin-menu-b3988afab9.webp)

*Admin Menu*

The Admin Menus screen also allows you to see what WordPress capability is controlling access to each menu link. Hover over the question mark icon and you’ll see a tooltip with details. These capabilities can be given to users via [the Capabilities screen](https://publishpress.com/knowledge-base/capabilities-screen/).

![Admin Menus screen](https://docs-media.publishpress.com/publishpress-capabilities/admin-menus-tooltip-ee3a70507b.webp)

Things to note for Admin Menu access:

- The Admin Menus feature allows you to remove access for a user role. It does not to give access to a menu link if a user role does not already have access.
- “Blocked” always wins. If a user is [in multiple roles](https://publishpress.com/knowledge-base/adding-multiple-roles-to-a-user/) and one role is allowed to access a menu link and one is blocked, then the user will be blocked from the menu link.
