---
title: "Add Body Class"
order: 2
doc_category: cme
---

# Add Body Class

Table of Contents

- [How to Add a Body Class](#h-how-to-add-a-body-class)
- [An Example With Adding a Body Class](#h-an-example-with-adding-a-body-class)
- [Features in the Pro version of PublishPress Capabilities](#h-features-in-the-pro-version-of-publishpress-capabilities)

This guide will show you how to use the “Add Body Class” option in the “Frontend Features” screen in the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin. This feature allows you to modify your site’s frontend by adding a specific body class to your site’s theme.

## How to Add a Body Class [#](#h-how-to-add-a-body-class)

- **Title**: This is for your own reference. It is the internal name for your code change.
- **Features**: Choose “Add Body Class”. Enter the CSS into the box.
- **Load on page types**: In the Free version, the default is to apply this feature for the whole site. In the Pro version, you can choose to hide the feature on specific posts and pages.
- Click the “Add New” button.

![Add new body class](https://docs-media.publishpress.com/publishpress-capabilities/new-body-class-1-372025f150.webp)

- Click the “Save Changes” button.

![](https://docs-media.publishpress.com/publishpress-capabilities/save-body-class-ea8b56e798.webp)

- After making your changes, you can use the [User Testing](https://publishpress.com/links/user-testing) option in [PublishPress Capabilities](https://publishpress.com/links/capabilities). You can test a “Subscriber” user to see if the body class has been added. Please note the body class by itself may not visually modify your site. You will also need to add CSS to your site that specifies what your class does.

## An Example With Adding a Body Class [#](#h-an-example-with-adding-a-body-class)

Here’s an example of how Frontend Features can help you make quick and safe changes to your site. I created a site with a theme and a starter template. My goal is to add a light green background color to the site’s body, but only when users are logged in to the site.

My first step is enter the CSS element into “Frontend Features”. In the screenshot below, I’ve chosen “Logged In Users” in the top left corner. I’ve also chosen “Add Body Class” and entered the CSS element into the “Features” box. I am using a body class of “green-background” with no period at the beginning.

![Add Body Class settings](https://docs-media.publishpress.com/publishpress-capabilities/add-body-class-d0c85662c9.webp)

Next, I can add the specific CSS I want to use. Click “Add Custom CSS” and you can add your CSS, as in this screenshot below:

![Add custom CSS for a body class](https://docs-media.publishpress.com/publishpress-capabilities/green-background-2efd0b9776.webp)

Finally, I can test the front of the site as a logged in user. The body of the site now uses the light green color that I chose:

![Light green color using Add Body Class](https://docs-media.publishpress.com/publishpress-capabilities/add-css-body-364a976a2e.webp)

## Features in the Pro version of PublishPress Capabilities [#](#h-features-in-the-pro-version-of-publishpress-capabilities)

In the Pro version of [PublishPress Capabilities](https://publishpress.com/links/capabilities), you will be able to click the “Selected Pages” button. This allows you access to two extra options:

- **Load on page types**: You can choose to implement your CSS changes only on specific page types such as “Homepage” or “Single Pages”.
- **Add post metabox**: You can load a metabox which allows CSS changes to be loaded only on individual posts.

![](https://docs-media.publishpress.com/publishpress-capabilities/pro-features-frontend-da84bed227.webp)

This screenshot below shows how the metabox will appear in the editing screen for individual posts:

![](https://docs-media.publishpress.com/publishpress-capabilities/frontend-features-metabox-723002564d.webp)
