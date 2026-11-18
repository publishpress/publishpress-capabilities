---
title: "Hide IDs or Classes"
order: 38
doc_category: cme
section: Frontend Features
---

# Hide IDs or Classes

Table of Contents

- [How to Hide IDs or Classes](#h-how-to-hide-ids-or-classes)
- [An Example of Hiding IDs and Clases](#h-an-example-of-hiding-ids-and-clases)
- [Features in the Pro version of PublishPress Capabilities](#h-features-in-the-pro-version-of-publishpress-capabilities)

This guide will show you how to use the “Hide IDs or Classes” option in the “Frontend Features” screen in the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin. This feature allows you to modify your site’s frontend by hiding specific IDs or classes. In this example, we will hide the footer for your website. The exact choice of ID or class will vary depending on your theme.

## How to Hide IDs or Classes [#](#h-how-to-hide-ids-or-classes)

- **Title**: This is for your own reference. It is the internal name for your code change.
- **Features**: Choose “Hide IDs or Classes”. Enter the CSS into the box. In the screenshot below, we are aiming to hide the .sitefooter for all users in the Subscriber role.
- **Load on page types**: In the Free version, the default is to apply this feature for the whole site. In the Pro version, you can choose to hide the feature on specific posts and pages.
- Click the “Add New” button.

![](https://docs-media.publishpress.com/publishpress-capabilities/hide-site-footer-5cf4652087.webp)

- Click the “Save Changes” button.

![Save changes to site footer](https://docs-media.publishpress.com/publishpress-capabilities/frontend-save-changes-52c48f9fac.webp)

- After making your changes, you can use the [User Testing](https://publishpress.com/links/user-testing) option in [PublishPress Capabilities](https://publishpress.com/links/capabilities). You can test a “Subscriber” user to see if the .sitefooter is correctly hidden.

## An Example of Hiding IDs and Clases [#](#h-an-example-of-hiding-ids-and-clases)

Here’s an example of how Frontend Features can help you make quick and safe changes to your site. I created a site with a theme and a starter template. My goal is to hide the blue button in the menu area, but only when users are logged in to the site.

![Frontend Features element to hide](https://docs-media.publishpress.com/publishpress-capabilities/frontend-before-0b8cb45482.webp)

My first step is to find the CSS to hide. This does require some CSS experience to do accurately. Using the inspect tools in a browser, I click on the button and can find the right elements.

![Frontend Features find CSS](https://docs-media.publishpress.com/publishpress-capabilities/code-find-db725e3afe.webp)

The next step is to enter the CSS element into “Frontend Features”. In the screenshot below, I’ve chosen “Logged In Users” in the top left corner. I’ve also chosen “Hide IDs or Classes” and entered the CSS element into the “Features” box.

![Frontend Features created](https://docs-media.publishpress.com/publishpress-capabilities/code-enter-5f47903af8.webp)

Finally, I can test the front of the site as a logged in user. The menu button is now hidden:

![Frontend Features element hidden](https://docs-media.publishpress.com/publishpress-capabilities/frontend-after-8433af3518.webp)

## Features in the Pro version of PublishPress Capabilities [#](#h-features-in-the-pro-version-of-publishpress-capabilities)

In the Pro version of [PublishPress Capabilities](https://publishpress.com/links/capabilities), you will be able to click the “Selected Pages” button. This allows you access to two extra options:

- **Load on page types**: You can choose to implement your CSS changes only on specific page types such as “Homepage” or “Single Pages”.
- **Add post metabox**: You can load a metabox which allows CSS changes to be loaded only on individual posts.

![](https://docs-media.publishpress.com/publishpress-capabilities/pro-features-frontend-da84bed227.webp)

This screenshot below shows how the metabox will appear in the editing screen for individual posts:

![Frontend Features metabox](https://docs-media.publishpress.com/publishpress-capabilities/hide-site-footer-e561454c0d.webp)
