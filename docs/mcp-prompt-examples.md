# Capabilities MCP request examples

These examples describe expected tool behavior for ordinary user wording. They can be replayed in a fresh AI conversation after connecting the site. A tool unavailable in the client is different from an execution denied by WordPress permissions.

| Request | Expected behavior |
| --- | --- |
| Which users can publish posts? | Use Hub WordPress user lookup with effective `publish_posts` checks. List individual users, not just role names. Follow every candidate page. |
| What can the user bob do? | Resolve the exact username with Hub user lookup. Never assume an ID. |
| On the Admin Menus screen, block Editors from accessing the Posts screen. | Discover Admin Menus, read the Editor settings and save the discovered Posts restriction value through `update-feature-settings`. Preserve unrelated restrictions and read back the result. |
| Allow users in the Subscribers role to edit posts. | Inspect the role and use `update-role-capabilities` to grant `edit_posts`. Do not silently grant publishing or editing other users' posts. |
| Use Redirects to send Authors to the Hello World post after login. | Find the actual content and permalink through Hub. Read the Author redirects, change `login_redirect` and read it back. Ask which post if titles are ambiguous. |
| Give me an export of all Capabilities settings. | Use `export-configuration` and retain the native restorable format. Do not describe a partial reconstruction as a native export. |
| Enable Multiples roles on User Edit under Capabilities Settings Roles. | Read settings, update only `cme_capabilities_edit_user_multi_roles`, and read it back. |
| Enable multiple roles on Add New User. | Update only `cme_capabilities_add_user_multi_roles`. This is separate from Edit User. |
| Enable multiple roles for users in Capabilities settings. | Ask whether the request applies to Add New User, Edit User or both. |
| Enable Show private post types under Capabilities Settings Editor Features. | Update only `cme_editor_features_private_post_type`. Do not change `cme_capabilities_show_private_post_types`, which belongs to the Capabilities screen. |

Settings changes use `update-plugin-settings`. `set-feature-enabled` controls whole screens. Read supported fields with `get-plugin-settings` and use their descriptions to distinguish similarly named settings.

The connected account needs the capability for the relevant screen. Disabled features and application-password restrictions still apply. Write tools also need to be selected and permitted in Hub. If a tool is missing, check exposure and refresh the connection before assuming the operation is unsupported. Verify the target site and read back any saved change.

Automated integration checks verify tool schemas, permissions and saved values. They do not guarantee that every AI model will choose the correct tool for every wording.
