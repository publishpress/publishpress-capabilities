---
title: "What’s the Difference Between PublishPress Capabilities and PublishPress Permissions?"
order: 29
doc_category: cme
section: Getting Started
---

# What’s the Difference Between PublishPress Capabilities and PublishPress Permissions?

We provide two plugins that allow you to control who can access what on your WordPress site. This is simple way to understand the difference:

- [PublishPress Capabilities](https://publishpress.com/links/capabilities) focuses on user roles.
- [PublishPress Permissions](https://publishpress.com/links/permissions) controls access for individual posts, pages, or users.

OK, that’s maybe too simple, so here’s more detail:

- [PublishPress Capabilities](https://publishpress.com/links/capabilities) allows you to customize the default WordPress permissions such as who can edit posts.
- [PublishPress Permissions](https://publishpress.com/links/permissions) allows you to customize advanced permissions that go far beyond the scope of the WordPress core.

Here’s an specific comparison using Posts:

- PublishPress Capabilities allows you to control who can edit all your Posts.
- PublishPress Permissions allows you to control who can edit Posts with a particular Category or Tag.

Here’s another specific comparison, this time using Pages:

- PublishPress Capabilities allows you to control who can edit all your Pages.
- PublishPress Permissions allows you to control who can edit Pages with a particular parent Page.

There are many more differences between the two plugins, but hopefully this gives you a general guide: PublishPress Capabilities controls general access to WordPress features and is enough for 80% of sites. PublishPress Permissions goes much deeper and has advanced features that perhaps 20% of sites will need.

| **Task** | **PublishPress Capabilities** | **PublishPress Permissions** |
| --- | --- | --- |
| Allow a user to install plugins | ✅ | ❌ |
| Allow a user to activate plugins | ✅ | ❌ |
| Allow a user to edit themes | ✅ | ❌ |
| Allow a user to manage WooCommerce settings | ✅ | ❌ |
| Allow a user to access a plugin settings page | ✅ | ❌ |
| Allow a user to publish posts | ✅ | ❌ |
| Allow a user to edit all posts | ✅ | ❌ |
| Allow a user to edit all pages | ✅ | ❌ |
| Allow a user to moderate comments | ✅ | ❌ |
| Allow a user to manage categories | ✅ | ❌ |
| Allow a user to edit one specific post | ❌ | ✅ |
| Allow a user to edit one specific page | ❌ | ✅ |
| Allow a user to edit posts in one category | ❌ | ✅ |
| Allow a user to edit posts with one tag | ❌ | ✅ |
| Allow a user to access one custom post type item | ❌ | ✅ |
