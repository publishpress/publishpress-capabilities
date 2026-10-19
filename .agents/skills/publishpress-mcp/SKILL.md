---
name: publishpress-mcp
description: Implement or extend Capabilities actions through the WordPress Abilities API. Use for action registration, schemas, permissions, feature operations, and integration tests.
---

# PublishPress MCP integration

Hub provides an optional connection through the separately installed WordPress MCP Adapter; it does not bundle the adapter or implement MCP transport. Users connect through a compatible external MCP client. The integration does not require a particular AI provider or integrate with WordPress.com's hosted MCP service.

## Hub connection contract

Hub owns the shared connection and discovers abilities in category `publishpress`. Connection code and generic documentation live in the Hub repository. Keep Capabilities action implementations and coverage in this repository; adding actions must not require Hub registration changes.

## Plugin ownership

- Register abilities independently of Hub on `wp_abilities_api_init`, outside admin-only bootstrap paths. Check API availability to preserve older WordPress compatibility.
- Register shared category `publishpress` on `wp_abilities_api_categories_init` if `wp_has_ability_category('publishpress')` is false. Do not probe with `wp_get_ability_category`: a missing category emits a developer notice. Every participating plugin must work alone and coexist with other category registrants.
- Use names in the form `{plugin-namespace}/{action-name}`, explicit input/output JSON schemas, clear descriptions, and an execution callback that reuses plugin services.
- Set `meta.public`, `meta.mcp.public`, and `meta.show_in_rest` to false. Custom-server registration is explicit; public default-server discovery is a different route.
- Match the operation's existing permission checks, including object-specific checks where relevant. Never replace permissions with a nonce or trust a tool annotation as authorization.
- Check the actual operation capability with `current_user_can`; do not bypass a denial just because the user has the administrator role. Respect application-password capability restrictions.
- Preserve each plugin's validation, storage formats, filters and lockout protections. Validate all bulk targets before writing, and reuse existing services instead of simulating admin forms or minting nonces.
- Gate edition-specific registrations on the active edition and network operations on their existing network permissions. Update shared dependencies through the normal release process instead of editing vendored copies.
- Do not change the authenticated MCP identity or return authentication secrets.
- Annotate read-only, destructive, and idempotent behavior truthfully. Avoid arbitrary PHP, SQL, option writes, or generic unrestricted method dispatch.
- Keep Free/Pro bootstrap and minimum PHP compatibility in mind. Do not register duplicates when Pro loads shared Free code.

## Capabilities implementation notes

These details apply when working on Capabilities; they are not requirements for every participating plugin.

- Keep `includes/mcp-abilities.php` as a thin loader. `Bootstrap::instance()` in `includes/mcp/bootstrap.php` owns hook registration and must not register duplicate hooks on repeated loading. Read-only role registration and callbacks live in `RoleReadAbilities`; other action registries and operation classes remain under `includes/mcp/`. Follow the plugin's class-based feature pattern, without adding Hub's container architecture. Preserve PHP 7.2.5 syntax compatibility (no arrow functions or typed properties).
- Preserve editable-role filters, role hierarchy, administrator/default/own-role protections and grant/deny/remove semantics. Per-application-password denials must constrain an administrator's MCP session too.
- Reuse deletion, backup validators, recovery, network queues and Admin Styles sanitizers. Manual backups use `capsman_backup`; automatic backups use `cme_backup_auto_*`; editor keys are `block_editor` / `classic_editor`. The Admin Styles API trait reuses the existing private sanitizers and CSS lifecycle.
- Network role synchronization requires main-site super-admin permissions. Pro's shared Free dependency must be updated through packaging.
- Profile role assignment and MCP reuse `includes/user-role-assignment.php`; preserve promotion/object checks, editable/disabled role rules, forum roles and self-lockout protection. Pro settings use `ProSettingsOperations` to enforce edition, dependency and configuration locks before any option writes.
- Browser DOM capture and account-switching cookies belong to browser sessions. Use the capture preparation URL and effective permission tests without changing the MCP identity.

## Verification

Verify the released adapter version, not just trunk: [adapter source](https://github.com/WordPress/mcp-adapter), [transport permissions](https://github.com/WordPress/mcp-adapter/blob/trunk/docs/guides/transport-permissions.md), and [custom/default servers](https://github.com/WordPress/mcp-adapter/blob/trunk/docs/guides/default-server.md).

Test disabled/missing-dependency states, subscriber/editor connection denial, administrator connection with denied plugin actions, successful authorized calls, malformed input, plugin deactivation, shared category registration, and default-server discovery/execution rejection. Use MCP initialization and session headers when testing HTTP. Never commit application passwords.

Shared connection and protocol tests live in the Hub repository and use synthetic abilities. Keep Capabilities domain tests in this repository.

For Capabilities, `tests/Mcp/capabilities-smoke.php` performs writes only against an isolated `mcp_smoke_` fixture and restores its options/password metadata. `MCP_SMOKE_PRO=1` verifies shared Pro data contracts, not the full Pro distribution bootstrap. Keep this repository's `docs/mcp-feature-coverage.md` aligned with Capabilities menus and actions. Document other plugins' coverage alongside their integrations without adding a plugin list to Hub's registration.

Skills here guide developers and their coding agents. They are separate from optional end-user instructions for AI clients consuming tools.

`tests/Mcp/pro-settings-smoke.php` exercises the real Pro settings helper with test-only dependency markers; it is not a full Pro/Statuses/Permissions integration test. Keep all built-in menu slugs and settings keys covered by the feature-suite assertions.
