---
title: "Backup and Restore User Capabilities"
order: 14
doc_category: cme
section: Backup
---

# Backup and Restore User Capabilities

Table of Contents

- [Automatic Backups](#h-automatic-backups)
- [Create a Manual Backup](#h-create-a-manual-backup)

[PublishPress Capabilities](https://publishpress.com/links/capabilities) offers you the ability to backup and restore your permissions.

This feature is very helpful if you want to test out changes on your site, or you’ve installed a new plugin that has changed your site’s permissions.

## Automatic Backups [#](#h-automatic-backups)

Changing permissions in WordPress can be a tricky task. It’s possible to lock yourself or others out of some important features.

So we’ve built a way to keep your site safe.

Every time you change your permissions, the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin will automatically create a backup. If you make a mistake, go to the “Backup” menu link and click the “Restore” tab. Here you can browse through the most recent 20 automatic backups. The plugin also saves a backup from you first installed PublishPress Capabilities. And you can click the “Backup” tab to manually create backups.

Click the “Restore Selected Roles” button and you’ll be able to roll back to a previous version.

![Restore roles fro Auto-backup of WordPress Capabilities](https://docs-media.publishpress.com/publishpress-capabilities/restore-selected-roles-4d73f6e171.webp)

*Backup Choices*

Whenever you choose a backup, the plugin will show all the permissions in that backup. Important changes in permissions are highlighted:

- A green role title means the role does not exist in your current set.
- A green capability is set in the role backup, but not the current role.
- A stuck-through capability is set in the current role, but not the role backup.

In the image below, several permissions are marked in red: these will be removed if you restore this backup.

![Auto-backup of WordPress Capabilities](https://docs-media.publishpress.com/publishpress-capabilities/backup-changes-60476559e0.webp)

*Changes From Current Role*

## Create a Manual Backup [#](#h-create-a-manual-backup)

You can also manually create a backup of your permissions.

- Go to Capabilities > Backup.
- Click the “Backup” tab.
- Click the “Manual Backup” button:

![Manual backup of capabilities](https://docs-media.publishpress.com/publishpress-capabilities/backup-tool-34d8b69213.webp)

*Manual Backup*

- PublishPress Capabilities will save one manual backup at a time. You can find that backups under the “Restore” tab.

![Restore manual backup of capabilities](https://docs-media.publishpress.com/publishpress-capabilities/manual-backup-capabilities-7dbc0d361c.webp)

*New Backup*
