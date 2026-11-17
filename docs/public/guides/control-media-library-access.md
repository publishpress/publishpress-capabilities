---
title: "Capabilities for the Media Library"
order: 24
doc_category: cme
---

# Capabilities for the Media Library

Table of Contents

- [Give access to the Media Library](#0-toc-title)
- [Specific permissions in the Media Library](#1-toc-title)
- [Permissions for specific media files](#2-toc-title)

The [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin enables you to decide who can upload, edit and delete files from the WordPress Media Library.

By default, only Administrators, Editors, and Authors are allowed to access your site’s Media Library. Subscribers and Contributors are not even allowed to upload files.

## Give access to the Media Library [#](#0-toc-title)

- Install the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin.
- Go to the “Capabilities” link in your WordPress admin menu.
- Choose the user role you want to edit in the top left corner.
- Click the “Media” tab.
- Check all three boxes for “edit files”, “upload files” and “unfiltered upload”.

![](https://docs-media.publishpress.com/publishpress-capabilities/contributor-media-role-7ace894ca8.webp)

## Specific permissions in the Media Library [#](#1-toc-title)

It is also possible to create more granular permissions for the Media Library. For example, by default, only Administrators are able to delete files in your Media Library.

- Go to the “Capabilities” link in your WordPress admin menu.
- In the “Type-Specific Capabilities” box in the sidebar, check the “Media” box.

![Media capabilities](https://docs-media.publishpress.com/publishpress-capabilities/unique-capabilities-for-post-types-35ad497d93.webp)

In the center of the screen you will now be able to control these permissions for the files in your site’s Media Library:

- Edit
- Edit Others

![Deleting capabilities](https://docs-media.publishpress.com/publishpress-capabilities/editing-caps-9019ea56ce.webp)

Click on the “Deletion” tab. Here you can decide who is allowed to delete images from the WordPress Media Library. If you want to block a user role from deleting files, click on these boxes until the checkmarks turn into red X icons.

![](https://docs-media.publishpress.com/publishpress-capabilities/deletion-capabilities-cec5af3ba0.webp)

## Permissions for specific media files [#](#2-toc-title)

It is possible to control who can access individual media files if you also install the [PublishPress Permissions](https://publishpress.com/links/permissions) plugin.

- Inside the Media Library, open a file for editing.
- Click the “Edit more details” link as in this next screenshot.

![Edit more details for media](https://docs-media.publishpress.com/publishpress-capabilities/edit-more-details-media-65b10cbc05.webp)

You will now see three boxes for the file: “View this Media”, “Edit this Media” and “Assign Terms to this Media”. You can use this boxes to control who can edit and view this file.

![Edit this media in Media Library](https://docs-media.publishpress.com/publishpress-capabilities/edit-this-media-1-e787240e4b.webp)

“View this Media” controls direct access to the attachment page for the image.

“Edit this Media” controls who can crop the image, plus change the ALT, caption, description, and more. If you don’t have editing access to an image, you can add the image to posts. However, when you try edit an image, all the fields will be missing or blanked out, as in this next screenshot.

![](https://docs-media.publishpress.com/publishpress-capabilities/edit-media-denied-1-927d6b25c7.webp)
