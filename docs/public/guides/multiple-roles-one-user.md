---
title: "Add a WordPress User to Multiple Roles"
order: 44
doc_category: cme
section: Roles
---

# Add a WordPress User to Multiple Roles

Table of Contents

- [Why Use Multiple Roles for One User?](#h-why-use-multiple-roles-for-one-user)
- [Method #1. Add an extra role for the whole site](#h-method-1-add-an-extra-role-for-the-whole-site)
- [Method #2. Add an extra role for certain situations](#h-method-2-add-an-extra-role-for-certain-situations)
- [How do permissions work with multiple roles?](#h-how-do-permissions-work-with-multiple-roles)

By default, there are five users roles in WordPress: Subscriber, Contributor, Author, Editor, Administrator. However, it is possible to create new roles and you can also place users into more than one role.

In this tutorial, I’ll explain how and why you can place WordPress users into multiple roles.

## Why Use Multiple Roles for One User? [#](#h-why-use-multiple-roles-for-one-user)

A good example for multiple roles is WooCommerce. In the image below, you can see two new roles: “[Shop manager](https://publishpress.com/links/shop-manager)” and “Customer”.

![](https://docs-media.publishpress.com/publishpress-capabilities/woocommerce-user-roles-bb83d9e895.webp)

**

The “Customer” role in WooCommerce has almost no permissions. Users in this role can buy products, but normally they can’t even access the WordPress admin area.

What happens if you want to give some customers extra permissions? One approach is to create a whole new user role. However, you can also give users an *additional* role.

For example, you can make some users a “Customer” but also give them the “Author” role so they can create content.

I’m going to show you two ways to add extra roles:

## Method #1. Add an extra role for the whole site [#](#h-method-1-add-an-extra-role-for-the-whole-site)

This first method allows you to give users an extra role across all areas of your WordPress site.

- Install the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin.
- Go to Capabilities > Settings > Roles

![PublishPress Capabilities settings](https://docs-media.publishpress.com/publishpress-capabilities/image-1-65b0667148.webp)

*Enable multiples roles*

- Check both boxes and click “Save Changes”.
- Go to the “Users” link in your WordPress admin menu.
- Either edit an existing users or create new user.
- The “Role” field will now be able to accept more than one choice:

![Multiple Roles in WordPress](https://docs-media.publishpress.com/publishpress-capabilities/multiple-roles-c7fded3fe4.webp)

*Multiple Roles*

## Method #2. Add an extra role for certain situations [#](#h-method-2-add-an-extra-role-for-certain-situations)

This second method allows you to give users an extra role in only some restricted areas of your WordPress site. For example, they could be an “Author” only for one post type.

- Install the [PublishPress Permissions](https://publishpress.com/links/permissions) plugin.
- Go to the “Users” menu link.
- Click to edit a customer account:

![Edit a customer account](https://docs-media.publishpress.com/publishpress-capabilities/edit-custom-role-43c683bf6f.webp)

- Scroll down and click the “customize this user directly” link:

![Custom user permissions](https://docs-media.publishpress.com/publishpress-capabilities/customize-user-directly-83383282c2.webp)

On this page you can add supplemental roles for this user. For example:

- Choose “Post” from the “Post Type” dropdown.
- Choose “Author” from the “Role” dropdown.
- Click “Add Role” and “Save Role” to finish.

This “customer” will now also be able to act as as Author for all the Posts on your site.

![User acting as Author](https://docs-media.publishpress.com/publishpress-capabilities/image-2-46a3a7b0f3.webp)

Go back to the “Users” screen and you will be able to see the multiple roles listed next to the user account:

![Multiple roles listed for account](https://docs-media.publishpress.com/publishpress-capabilities/multiple-roles-listed-0640c62f05.webp)

## How do permissions work with multiple roles? [#](#h-how-do-permissions-work-with-multiple-roles)

If you give multiple roles to a user, this does add some complexity. Please [test the user account carefully](https://publishpress.com/knowledge-base/user-testing/) after giving them access to multiple roles.

In most cases, the permissions are *additional*. Let’s see a practical example of this:

- Subscriber: This role DOES NOT have the ability to create new Posts.
- Author: This role DOES have the ability to create new Posts.

In this case, a user with both the “Subscriber” and “Author” roles DOES have the ability to create posts. The user gets the permissions of both roles.

The only exception to this if a role has permissions that are explicitly denied. This screenshot below shows an example from the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin. In this image, the permissions for Posts are all explicitly denied with the red X.

![](https://docs-media.publishpress.com/publishpress-capabilities/image-70bfd9417c.webp)

*Author Denied*
