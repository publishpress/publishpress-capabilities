---
title: "Roles and Permissions on a WordPress Multisite Network"
order: 45
doc_category: cme
---

# Roles and Permissions on a WordPress Multisite Network

If you run a WordPress multisite network, it can be helpful to standardize your setup across the various sites. In this guide, I’ll show you how to control user roles and permissions across the network.

To control your roles and permissions, we recommend the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin.

- Install the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin on your main network site.
- Set the plugin to “Network Activate”. This means that the plugin will now be available for all the sites of your multisite network.

![Cap Multisite](https://docs-media.publishpress.com/publishpress-capabilities/cap-multisite-a31b242d19.webp)

*Cap Multisite*

- Still on your main site, go to “Capabilities” in your admin menu.
- On this screen, you can use the full power of the plugin to create roles, choose permissions and otherwise configure the user setup on your site.
- The key settings for multisites are at the bottom of the screen. You’ll see “include in new sites” and “sync role to all sites now”. These settings will take effect when you click “Save Changes”. If you don’t see these checkboxes, then you aren’t on your main network site.

![](https://docs-media.publishpress.com/publishpress-capabilities/multisite-permissions-4be5aa9eae.webp)

What do these two settings do?

- **include in new sites**: this will create the default settings for new sites created in the future. So if you create a new role called “Guest Author” and all new sites in your network will also have this role.
- **sync role to all sites now**: this will immediately become the default settings for all the existing sites in your network. This will overwrite any existing permissions, so be careful with this option.
