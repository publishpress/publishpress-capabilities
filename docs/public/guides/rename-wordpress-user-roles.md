---
title: "Rename WordPress User Roles"
order: 50
doc_category: cme
---

# Rename WordPress User Roles

Table of Contents

- [How to rename roles](#h-how-to-rename-roles)
- [Limits to renaming roles](#h-limits-to-renaming-roles)

The [PublishPress Capabilities plugin](https://publishpress.com/capabilities/) allows you to rename any existing user roles on your WordPress site.

You may want to do this because the default WordPress roles have names that are not always very clear (Subscriber, Contributor, Author, Editor, Administrator).

Please note: one or two plugins such as [bbPress](https://wordpress.org/plugins/bbpress/) do not use the normal WordPress approach for user roles. Those plugins may require different ways to rename their roles.

## How to rename roles [#](#h-how-to-rename-roles)

- After installing the plugin, go to “Capabilities” then “Roles” in your WordPress admin menu.
- Click “Edit” under role you want to rename:
- Enter the new name for the role into the “Role Name” box.
- Click “Update Role”.

![](https://docs-media.publishpress.com/publishpress-capabilities/rename-role-281bec1882.webp)

*New Role Name*

## Limits to renaming roles [#](#h-limits-to-renaming-roles)

Please also note that is a visual change, but WordPress will continue to use the old name of the role for some behind-the-scenes tasks. For example, [PublishPress Capabilities](https://publishpress.com/links/capabilities) will continue to load the “Author Renamed” permissions at this URL:

```
/wp-admin/admin.php?page=capsman&role=author
```
