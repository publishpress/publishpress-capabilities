<?php

/**
 * PublishPress Capabilities [Free]
 *
 * Capabilities filters for the plugin PublishPress Permissions.
 */

if (!defined('ABSPATH')) {
    exit('No direct script access allowed');
}

/**
 * Register PublishPress Permissions capabilities
 */
add_filter('cme_plugin_capabilities', function ($pluginCaps) {
    // Respect external integrations only when they provide the grouped (latest) payload structure.
    if (!empty($pluginCaps['PublishPress Permissions']) && is_array($pluginCaps['PublishPress Permissions'])) {
        $external_payload = (array) $pluginCaps['PublishPress Permissions'];

        $first_payload_entry = reset($external_payload);
        if (is_array($first_payload_entry)) {
            return $pluginCaps;
        }
    }

    $pluginCaps['PublishPress Permissions'] = [
        __('Permissions & Access', 'capability-manager-enhanced') => [
            'pp_administer_content' => __('Administer advanced content permissions and visibility rules.', 'capability-manager-enhanced'),
            'pp_manage_settings' => __('Manage plugin permission settings.', 'capability-manager-enhanced'),
            'pp_unfiltered' => __('Bypass content restrictions for editorial workflows.', 'capability-manager-enhanced'),
            'set_posts_status' => __('Set custom post statuses for content items.', 'capability-manager-enhanced'),
            'pp_force_quick_edit' => __('Force Quick Edit access when restrictions would otherwise block it.', 'capability-manager-enhanced'),
        ],
        __('Roles & Groups', 'capability-manager-enhanced') => [
            'pp_assign_roles' => __('Assign or change role assignments for users.', 'capability-manager-enhanced'),
            'pp_create_groups' => __('Create new permission groups.', 'capability-manager-enhanced'),
            'pp_edit_groups' => __('Edit existing permission groups.', 'capability-manager-enhanced'),
            'pp_delete_groups' => __('Delete permission groups.', 'capability-manager-enhanced'),
            'pp_manage_members' => __('Add or remove members from permission groups.', 'capability-manager-enhanced'),
            'pp_create_network_groups' => __('Create network-wide permission groups in multisite.', 'capability-manager-enhanced'),
            'pp_manage_network_members' => __('Manage members of network-wide permission groups.', 'capability-manager-enhanced'),
        ],
        __('Exception Management', 'capability-manager-enhanced') => [
            'pp_set_associate_exceptions' => __('Set per-item association exceptions.', 'capability-manager-enhanced'),
            'pp_set_edit_exceptions' => __('Set per-item edit exceptions.', 'capability-manager-enhanced'),
            'pp_set_read_exceptions' => __('Set per-item read exceptions.', 'capability-manager-enhanced'),
            'pp_set_revise_exceptions' => __('Set per-item revision exceptions.', 'capability-manager-enhanced'),
            'pp_set_term_assign_exceptions' => __('Set taxonomy term assignment exceptions.', 'capability-manager-enhanced'),
            'pp_set_term_associate_exceptions' => __('Set taxonomy term association exceptions.', 'capability-manager-enhanced'),
            'pp_set_term_manage_exceptions' => __('Set taxonomy term management exceptions.', 'capability-manager-enhanced'),
        ],
        __('Content Associations', 'capability-manager-enhanced') => [
            'pp_associate_any_page' => __('Associate users or groups with any page.', 'capability-manager-enhanced'),
            'pp_define_post_status' => __('Define and apply custom post statuses.', 'capability-manager-enhanced'),
            'pp_define_privacy' => __('Define custom privacy policies for content.', 'capability-manager-enhanced'),
            'pp_moderate_any' => __('Moderate any item regardless of authorship.', 'capability-manager-enhanced'),
        ],
        __('Media & Misc', 'capability-manager-enhanced') => [
            'edit_own_attachments' => __('Edit attachments uploaded by the current user.', 'capability-manager-enhanced'),
            'list_others_unattached_files' => __('List unattached files uploaded by other users.', 'capability-manager-enhanced'),
            'pp_list_all_files' => __('List all media library files.', 'capability-manager-enhanced'),
            'pp_exempt_edit_circle' => __('Exempt user from edit-circle restrictions.', 'capability-manager-enhanced'),
            'pp_exempt_read_circle' => __('Exempt user from read-circle restrictions.', 'capability-manager-enhanced'),
        ],
    ];

    return $pluginCaps;
});
