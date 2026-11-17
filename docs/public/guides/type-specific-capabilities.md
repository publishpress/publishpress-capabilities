---
title: "What are Unique Capabilities for Post Types?"
order: 56
doc_category: cme
section: Post Types
---

# What are Unique Capabilities for Post Types?

Table of Contents

- [Why do post types require unique capabilities?](#h-the-problem-that-s-solved-by-unique-capabilities-for-post-types)
- [Using unique capabilities for post types](#1-toc-title)

“Unique Capabilities for Post Types” is a feature in PublishPress plugins that allows you more control over the custom post types.

## Why do post types require unique capabilities? [#](#h-the-problem-that-s-solved-by-unique-capabilities-for-post-types)

When a custom post type is registered on your site, WordPress automatically sets the permissions. Those permissions are borrowed from Posts.

This has some advantages. For example, it allows Authors and Editors access to the new post type without any further configuration. For most use cases, this is a good choice.

However, this also some disadvantages. For example, if you create a new post type called “Books”, anyone who can edit Posts will have access to edit this new post type. You will not be able to have different access for Posts and Books. Technically speaking, the [edit_posts](https://publishpress.com/links/edit_posts) capability will allow editing access to both post types. Technically speaking, the edit_books capability will now required to edit posts in the Book post type.

So if you need greater control, [PublishPress Capabilities](https://publishpress.com/links/capabilities) is a good option. This plugin provides a way to ensure that each capability defined for a post type can be granted or removed from any role.

## Using unique capabilities for post types [#](#1-toc-title)

To enable this feature for a post type follow these steps:

- Go to “Capabilities” in the WordPress admin menu.
- Look for the “Unique Capabilities for Post Types” box in the right sidebar.
- Checking these boxes will enable unique capability requirements for the selected post types.

- After enabling this feature, you can review each role’s Edit, Create and Delete checkbox areas. There will be new checkboxes that you can enable for each role.

![Unique Capabilities for Post Types](https://docs-media.publishpress.com/publishpress-capabilities/unique-capabilities-for-post-type-0426aee21f.webp)
