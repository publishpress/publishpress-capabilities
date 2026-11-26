---
title: "Hide Any Element in the WordPress Admin (Pro)"
order: 36
doc_category: cme
section: Admin Features
---

# Hide Any Element in the WordPress Admin (Pro)

Table of Contents

- [Sample Uses for Hide CSS Elements](#0-toc-title)
- [Creating a New Item with Hide CSS Elements](#1-toc-title)
- [Video Guide to Hide CSS Elements](#2-toc-title)

This feature requires the Pro version of the PublishPress Capabilities plugin.

The Pro version of the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin allows to hide any element inside the WordPress admin area. This is possible with the “Hide CSS Elements” feature.

If you can find the CSS class or ID, then you can hide that element using the “Hide CSS Elements” feature.

Let’s show you some examples of what “Hide CSS Elements” can do.

## Sample Uses for Hide CSS Elements [#](#0-toc-title)

- Go to “Capabilities” then “Admin Features” in your WordPress menu.
- Click the “Hide CSS Elements” tab.
- You can see at the examples that the plugin provides by default. These examples show you how to hide plugins or key links.

![](https://docs-media.publishpress.com/publishpress-capabilities/hide-css-elements-38813936e1.webp)

To enable any of these sample restrictions for a user role, click the box next to the item. Then click “Save for [Role Name]” as in this screenshot below:

![Hide CSS elements](https://docs-media.publishpress.com/publishpress-capabilities/hide-css-elements-enable-7f4807be7b.webp)

## Creating a New Item with Hide CSS Elements [#](#1-toc-title)

Let’s also see an example of a new restriction with “Hide CSS Elements”. We will hide the search box on post screen. In the screenshot below, we’re looking at the code for this search box. The code has a class of `search-box`.

![Search Box](https://docs-media.publishpress.com/publishpress-capabilities/search-box-a47e923c8c.webp)

*Search Box*

- Go to “Capabilities” and then “Admin Features”.
- Click the “Hide CSS Elements” tab.
- Choose the user role for which you want to hide the search box.
- Give a Label to this setting.
- Enter the class as `.search-box`. This configuration is shown in the screenshot below.
- Click “Add”.

![Hide search box](https://docs-media.publishpress.com/publishpress-capabilities/hide-search-box-d90c54ad55.webp)

*Screen Shot 2022 04 15 At 1.55.03 Pm*

- When a user in this role you selected now visits the Posts screen, the search box will be hidden for them, as in this screenshot below.

![Hide Posts](https://docs-media.publishpress.com/publishpress-capabilities/hide-posts-3af5482e91.webp)

*Hide Posts*

## Video Guide to Hide CSS Elements [#](#2-toc-title)

[iframe: https://www.youtube.com/embed/f5MUMOFSG2I?feature=oembed]
