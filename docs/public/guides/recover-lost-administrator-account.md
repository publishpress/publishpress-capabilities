---
title: "How to Recover a Lost Administrator Account"
order: 48
doc_category: cme
---

# How to Recover a Lost Administrator Account

Have you locked yourself out of the WordPress admin area by using the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin?

Don’t worry, the [PublishPress Capabilities](https://publishpress.com/links/capabilities) plugin stores a backup of WordPress role definitions. You can use this recover access to your site.

Those who are comfortable using phpMyAdmin or another database query tool can do the following:

- Find the `_options` table.
- Search for option_name which is likely to be named `capsman_backup%`
- Use `capsman_backup` if it is available, otherwise you can use `capsman_backup_initial`. Ensure that you are seeing the entire option_value of this row (not just a snippet of it ending in “…”. In phpMyAdmin, click in to “Edit” the row to see the entire option_value.
- In another window, search for the option_name `%user_roles`
- Update this `_user_roles` record with the option_value from the `capsman_backup` row. In phpMyAdmin, this can be done with the “edit” link. Be sure to clear the entire `option_value `entry box before pasting in the `capsman_backup` value.
