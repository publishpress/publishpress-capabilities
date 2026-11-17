---
title: "How to Use PublishPress Capabilities"
order: 42
doc_category: cme
section: Getting Started
---

# How to Use PublishPress Capabilities

Table of Contents

- [The basics of PublishPress Capabilities](#h-the-basics-of-publishpress-capabilities)
- [How to access the Capabilities features](#h-how-to-access-the-capabilities-features)
- [How to change the permissions for a role](#h-how-to-change-the-permissions-for-a-role)

[PublishPress Capabilities](https://publishpress.com/capabilities/) is a plugin that allows you to customize user permissions on your WordPress site.

The [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin allows you to manage WordPress user roles. You can choose specific permissions for each role.

These guides have useful background information as you’re getting started:

- [Technical requirements for our plugins](https://publishpress.com/knowledge-base/technical-requirements/).
- [How to move from Free to Pro plugins](https://publishpress.com/knowledge-base/how-to-move-from-free-to-pro-plugins/).

## The basics of PublishPress Capabilities [#](#h-the-basics-of-publishpress-capabilities)

The Free version of the plugin [is available on WordPress.org](https://wordpress.org/plugins/capability-manager-enhanced/).

There is also a Pro version with more features and extra support, [available on PublishPress.com](https://publishpress.com/capabilities/).

![Caps Download](https://docs-media.publishpress.com/publishpress-capabilities/caps-download-d62df3bd8c.webp)

*Caps Download*

## How to access the Capabilities features [#](#h-how-to-access-the-capabilities-features)

- Install either the Free or Pro version of Capabilities.
- After installing the plugin, go to “Capabilities” in your WordPress admin menu.

![Capabilitites Menu 1](https://docs-media.publishpress.com/publishpress-capabilities/capabilitites-menu-1-eb2a5d478a.webp)

*Capabilitites Menu 1*

## How to change the permissions for a role [#](#h-how-to-change-the-permissions-for-a-role)

- Make sure you are on the main “Capabilities” screen.
- Look in the top-left corner for the user role you want to edit:

![User Roles Select](https://docs-media.publishpress.com/publishpress-capabilities/user-roles-select-acdc1ef197.webp)

*User Roles Select*

- Look in the center of the screen and you can either check or uncheck the box for all the permissions on your WordPress site.
- If you choose the “Administrator” role, then nearly every box will be checked, as in the image below. This is because the Administrator role is very powerful and can do anything on a WordPress site.

![](https://docs-media.publishpress.com/publishpress-capabilities/administrator-capabilities-cd0cd26a16.webp)

*Administrator Permissions*

- If you choose the “Subscriber” role, then nearly every box will be unchecked, as in the image below. This is because the Subscriber role has very few permissions.

![](https://docs-media.publishpress.com/publishpress-capabilities/subscriber-capabilities-d8ac5aeff8.webp)

*Subscriber Permissions*

In each box, you can either enter a checkmark, leave the box blank, or you can click the red “X” to deny these permissions. Here is the difference between those options.

- **Checkmark**: This user role does have this capability.
- **Empty box**: This user role does not have this capability. However, users in this role can be given this capability if they are added to a second role.
- **Red X**: This user roles does not have this capability, and anyone in this roe can not be given the capability, even if they are added to second role.

This image below shows an example of all three options:

![](https://docs-media.publishpress.com/publishpress-capabilities/detail-on-capabilities-9fdca1b6d5.webp)

Please note two things:

1. [PublishPress Capabilities](https://publishpress.com/links/capabilities) won’t save your permissions automatically. Click the “Save Changes” button to make sure your changes are applied.
2. Changes **remain in the database** after plugin deactivation. Fortunately, [you can rollback your changes if you make a mistake](https://publishpress.com/knowledge-base/backup-restore-permissions/).
