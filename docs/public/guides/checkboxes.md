---
title: "How to Use the Checkboxes in PublishPress Capabilities"
order: 23
doc_category: cme
section: Getting Started
---

# How to Use the Checkboxes in PublishPress Capabilities

Table of Contents

- [The Capabilities screen](#h-the-capabilities-screen)
- [The Editor Features, Admin Features, Admin Menus, and Nav Menus screens](#1-toc-title)

The [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin uses checkboxes to control access to different features and screens in the WordPress admin area.

## The Capabilities screen [#](#h-the-capabilities-screen)

The “Capabilities” screen provides three types of checkbox for each option. Every time you click on an option, it will cycle between these three types.

- **Check mark**: Capability granted.
- **Blank**: Capability not granted or denied.
- **X mark**: Capability denied, even if granted by another role. This means that if a user is placed in two roles, they will more restrictive role will control their access.

![The Capabilities screen](https://docs-media.publishpress.com/publishpress-capabilities/how-to-use-capabilities-d786e01d2a.webp)

In the screenshot below, you can see all the choices.

![The Capabilities screen](https://docs-media.publishpress.com/publishpress-capabilities/three-capabilities-choices-7a81208a40.webp)

## The Editor Features, Admin Features, Admin Menus, and Nav Menus screens [#](#1-toc-title)

These four screens provide two types of checkbox for each option:

- **Blank**: No change.
- **X mark**: Capability denied.

![The Capabilities screen](https://docs-media.publishpress.com/publishpress-capabilities/how-to-use-editor-feature-e25c293385.webp)

This is a different approach from the “Capabilities” screen described above. From these four screens, it is not possible to grant access to features. It is only possible deny access.

If you want to upgrade a user account and provide access to more features, there are at least two approaches we recommend:

- Move them a user role with more capabilities.
- Go to the “Capabilities” screen and grant more capabilities to their user role.

There are other approaches available too. However, our recommendation is this:

**It is easier to downgrade a user role than to upgrade it**. **Start by granting a user role access to all the features they need**.**Then use tools such as “Editor Features” and “Admin Features” to remove unwanted access.**
