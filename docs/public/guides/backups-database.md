---
title: "Capabilities Backups in the Database, and Emergency Restores"
order: 15
doc_category: cme
section: Backup
---

# Capabilities Backups in the Database, and Emergency Restores

[PublishPress Capabilities](https://publishpress.com/links/capabilities) will automatically create backups every time you make a change your site’s user roles.

This article is a guide to where those backups are stored. It can also serve as a way to regain access if the capabilities on your site have been corrupt and you no longer have full admin access.

WordPress will store the current user capabilities in the `_options` table. Look for the `_user_roles` row and you’ll see the capabilities and roles.

![WordPress core user permissions](https://docs-media.publishpress.com/publishpress-capabilities/user-roles-database-41b409659d.webp)

[PublishPress Capabilities](https://publishpress.com/links/capabilities) stores back in the `_options` table. Look for rows with names that begin with the `cme_backup_` row. The capabilities and roles are stored in exactly the same format as the current core table. If you are completely locked out of your site and can not use the normal backup features in the Capabilities plugin, you can copy the data from one of these `cme_backup_` rows and use it to replace the data in the `_user_roles` row.

![CME backup tables in the database](https://docs-media.publishpress.com/publishpress-capabilities/cme-backup-d7cf8a6307.webp)
