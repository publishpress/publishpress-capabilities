# Capabilities MCP feature coverage

This document covers the Capabilities plugin integration. Shared connection and discovery documentation lives in the PublishPress Hub repository at `docs/mcp-integration.md`. Other plugins register their own abilities independently; this coverage table does not control Hub's discovery.

All action names use `publishpress-capabilities/`. `list-features` discovers the registered menus, states, capabilities and field schemas. Feature actions take a fixed `feature` selector and typed settings; they do not accept arbitrary WordPress option names.

The Free single-site integration registers 40 actions. Additional actions register when Pro or multisite is available; menu operations can share an action with typed feature selectors.

| Menu / area | Actions and coverage |
| --- | --- |
| Dashboard | `list-features`, `set-feature-enabled`, including existing authorized network feature synchronization |
| Roles | List/inspect/create/copy/rename/delete; role options for allowed editors, code editor, login/dashboard restrictions, disabling, WooCommerce exception, include-in-new-sites; `set-role-level` |
| Capabilities | `list-capabilities`, `compare-roles`, `update-role-capabilities` with grant/deny/remove and dry-run; post-type/taxonomy mappings, detailed taxonomies and separate create capability; discover registered custom-status capability names through the active Pro helper |
| Editor Features | Discover/read/patch/bulk block/classic restrictions per post type; Pro custom selectors and saved metabox discovery |
| Admin Features | Discover/read/patch/bulk toolbar/header/footer/dashboard restrictions; global hide-submenu preference; Pro custom CSS and URL/wildcard rules |
| Admin Styles | Discover templates/schemes; read/patch/bulk role styling; create/update/delete custom styles through existing sanitizers and generated CSS lifecycle; Free's one-style limit |
| Admin Notices | Read/patch/bulk notice-type removal/display preferences and toolbar access |
| Admin Menus | Menu/submenu restrictions; per-role renaming/icons, global order, readable presentation settings and reset; Pro custom menu/submenu/separator management, including position-after placement |
| Profile Features | Captured-element discovery; role restrictions and element order; create/update/delete selectors; prepare the existing five-minute browser capture window |
| Redirects | Login/logout/registration/first-login targets, custom/referrer flags, single-role and bulk updates; valid site-local URLs |
| Frontend Features | Create/update/delete selector/CSS/body-class elements and page/post-type scope; role/guest/logged-in restrictions; Pro per-post overrides with edit-post authorization |
| Navigation Menus | Discover menus/items and read/patch/bulk role/guest/logged-in restrictions |
| Backup | List/create/restore, export/import with existing validators/allowlist, selected export sections/roles, dry-run and recovery/reset |
| Settings | Typed settings for private post types/taxonomies, multiple roles, application-password scopes, user testing and menu restriction priority; Pro branding and dependency/lock-aware custom status and privacy controls |
| User Capabilities | Inspect effective user capabilities and object-specific permission checks; `update-user-roles` shares profile assignment rules, validates editable/disabled roles, preserves forum roles and supports dry-run |
| User Testing | `test-user-access` checks eligibility/excluded roles without changing the authenticated MCP identity |
| Application passwords | List names/denials and replace a password's denied capability list, without returning password secrets |
| Multisite roles | Queue existing role/option synchronization and configure include-in-new-sites; main-site super-admin only |

Feature menus share `get-feature-choices`, `get-feature-settings`, `update-feature-settings`, and `bulk-update-feature-settings`. List-valued settings replace that list; other roles and unmentioned settings remain unchanged. `dry_run` validates without saving, and bulk writes validate every target before starting.

`save-interface-element` / `delete-interface-element` manage profile/frontend elements. Pro `list-custom-rules`, `save-custom-rule`, `delete-custom-rule` cover editor and admin custom rules. The returned `restriction_value` identifies the rule in feature settings. Creation does not silently apply it.

Browser DOM capture requires the real browser UI. `prepare-profile-capture` returns the authorized admin page; it does not simulate browser login switching or issue authentication cookies. Toolbar/dashboard choices use metadata captured during authorized admin visits. Pro-only actions require the active Pro edition and the shared Free integration code.

## Implementation structure

`includes/mcp-abilities.php` only loads classes and initializes `Bootstrap::instance()`. The bootstrap registers category, ability and metadata-capture hooks once. `RoleReadAbilities` owns the list/inspect role actions; the existing registries and operation classes own the other typed actions. This initialization follows Capabilities' class-based feature pattern and does not depend on Hub being installed.

## Behavior and scope

The settings schema contains 14 built-in settings and five additional Pro settings. Edition availability, integration dependencies and site-configuration locks govern which settings can change.

Selected-role exports use the plugin's versioned selected-role format. Importing them merges the selected definitions into the site's existing roles and preserves unselected roles. Import previews validate the same data without saving it.

Browser-dependent operations retain their native interfaces: discovering newly rendered DOM elements, collecting newly rendered admin notices/widgets/metaboxes, and switching browser user sessions. MCP can manage saved restrictions, prepare profile capture, and check permissions; it does not impersonate a browser session. License activation/update administration remains with the existing licensing interface; these abilities do not return or accept license keys. Creating statuses themselves belongs to the status plugin; Capabilities actions manage the associated permissions.
