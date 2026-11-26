---
title: "Capabilities Will Not Save For Some Plugins"
order: 55
doc_category: cme
section: Troubleshooting Capabilities
---

# Capabilities Will Not Save For Some Plugins

Occasionally, you may find that permissions are not stored when you click “Save for [Role]” in PublishPress Capabilities. This will not happen for all the permissions on screen. Only the settings for some plugins will fail to save.

![](https://docs-media.publishpress.com/publishpress-capabilities/save-change-a5f079ad0f.webp)

*Save Changes Wont Save*

This is because a few plugins do not use the correct approach for WordPress permissions.

By default, WordPress stores permissions in the database of each site. 99% of plugins follow this approach. However, a few plugins try to avoid the default system. This can result in changes not saving.

These plugins currently have this issue:

- [bbPress](https://publishpress.com/blog/permissions/bbpress-roles-capabilities/)
- WooCommerce Support Ticket
