---
title: "Hide a WordPress User Role"
order: 34
doc_category: cme
---

# Hide a WordPress User Role

Table of Contents

- [How to hide a WordPress user role](#h-how-to-hide-a-wordpress-user-role)
- [What happens to hidden roles?](#h-what-happens-to-hidden-roles)

With the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin, it is possible to hide a WordPress user role. If a role is marked as “hidden” it will not appear in any WordPress role selection options, but it can be used by developers.

Let’s show you how this feature works:

## How to hide a WordPress user role [#](#h-how-to-hide-a-wordpress-user-role)

- Install the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin.
- Install the [PublishPress Permissions](https://publishpress.com/links/permissions) plugin.
- Go to “Capabilities” then “Roles: in your WordPress admin menu.
- For each custom role created, you will be able to select the “Hide” button:

![Hide A Role](https://docs-media.publishpress.com/publishpress-capabilities/hide-a-role-ddd354113f.webp)

*Hide A Role*

## What happens to hidden roles? [#](#h-what-happens-to-hidden-roles)

If a role is marked as “hidden” it will not appear in any WordPress role selection options:

- Add User
- Edit User
- Change role to

The role will still be available in some areas of WordPress:

- It can be used a [supplemental role](https://publishpress.com/knowledge-base/supplemental-roles/) in [PublishPress Permissions](https://publishpress.com/links/permissions).
- It can be used by plugins and themes. They can use the machine name of the role. The role will remain in the option name `wp_user_roles` in the `wp_options` database table.

![Wp User Roles](https://docs-media.publishpress.com/publishpress-capabilities/wp-user-roles-4e69400d70.webp)

*Wp User Roles*
