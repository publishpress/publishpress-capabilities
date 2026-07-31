<?php
/*
 * PublishPress Capabilities [Free]
 *
 * Multisite-related functions / filter handlers
 *
 */

/**
 * Copies roles marked "Include in new sites" after WordPress initializes a site.
 *
 * This hook is used by dashboard-created sites and by `wp site create`.
 * Priority 200 ensures WordPress has created the new site's role tables first.
 */
add_action('wp_initialize_site', '_cme_initialize_site_roles', 200, 2);

/**
 * Handles the modern WordPress new-site hook.
 *
 * @param WP_Site $new_site New site object.
 * @param array   $args     Site initialization arguments.
 * @return void
 */
function _cme_initialize_site_roles($new_site, $args = [])
{
    if (!is_object($new_site)) {
        return;
    }

    $new_blog_id = !empty($new_site->blog_id)
        ? (int) $new_site->blog_id
        : (!empty($new_site->id) ? (int) $new_site->id : 0);
    $network_id = !empty($new_site->network_id)
        ? (int) $new_site->network_id
        : (!empty($new_site->site_id) ? (int) $new_site->site_id : 0);

    if ($new_blog_id) {
        _cme_new_blog($new_blog_id, $network_id);
    }
}

/**
 * Copies selected roles from a network's main site to a new site.
 *
 * The function name is retained for compatibility with integrations that call
 * the previous wpmu_new_blog callback directly.
 *
 * @param int $new_blog_id New site ID.
 * @param int $network_id  Network containing the new site.
 * @return void
 */
function _cme_new_blog($new_blog_id, $network_id = 0)
{
    global $wp_roles;

    $new_blog_id = (int) $new_blog_id;
    $network_id = $network_id ? (int) $network_id : (int) get_current_network_id();

    if (!$new_blog_id) {
        return;
    }

    $autocreate_roles = (array) get_network_option($network_id, 'cme_autocreate_roles', []);

    if (empty($autocreate_roles)) {
        return;
    }

    $main_site_id = (int) get_main_site_id($network_id);
    $wp_roles = wp_roles();
    $main_site_caps = [];
    $role_captions = [];
    $main_admin_caps = [];
    $main_pp_only = [];

    switch_to_blog($main_site_id);

    try {
        _cme_set_roles_site($main_site_id);

        $admin_role = get_role('administrator');
        if ($admin_role && is_array($admin_role->capabilities)) {
            $main_admin_caps = $admin_role->capabilities;
        }

        if (defined('PRESSPERMIT_ACTIVE')) {
            $main_pp_only = (array) pp_capabilities_get_permissions_option('supplemental_role_defs');
        }

        foreach ($autocreate_roles as $role_name) {
            $role_name = sanitize_key($role_name);
            $role = get_role($role_name);

            if ($role) {
                $main_site_caps[$role_name] = $role->capabilities;
                $role_captions[$role_name] = isset($wp_roles->role_names[$role_name])
                    ? $wp_roles->role_names[$role_name]
                    : $role_name;
            }
        }
    } finally {
        restore_current_blog();
    }

    if (empty($main_site_caps)) {
        _cme_set_roles_site(get_current_blog_id());
        return;
    }

    switch_to_blog($new_blog_id);

    try {
        _cme_set_roles_site($new_blog_id);

        if (defined('PRESSPERMIT_ACTIVE')) {
            pp_refresh_options();
            $blog_pp_only = (array) pp_capabilities_get_permissions_option('supplemental_role_defs');
        }

        foreach ($main_site_caps as $role_name => $caps) {
            if ($blog_role = $wp_roles->get_role($role_name)) {
                $stored_role_caps = (!empty($blog_role->capabilities) && is_array($blog_role->capabilities))
                    ? array_intersect($blog_role->capabilities, [true, 1])
                    : [];

                // Find caps to add and remove.
                $add_caps = array_diff_key($caps, $stored_role_caps);
                $del_caps = array_intersect_key(
                    array_diff_key($stored_role_caps, $caps),
                    $main_admin_caps
                );

                foreach ($add_caps as $cap => $grant) {
                    $blog_role->add_cap($cap, $grant);
                }

                foreach ($del_caps as $cap => $grant) {
                    $blog_role->remove_cap($cap);
                }
            } else {
                $wp_roles->add_role($role_name, $role_captions[$role_name], $caps);
            }

            if (defined('PRESSPERMIT_ACTIVE')) {
                if (in_array($role_name, $main_pp_only, true)) {
                    _cme_pp_default_pattern_role($role_name);
                    $blog_pp_only[] = $role_name;
                } else {
                    $blog_pp_only = array_diff($blog_pp_only, [$role_name]);
                }
            }
        }

        if (defined('PRESSPERMIT_ACTIVE')) {
            pp_capabilities_update_permissions_option(
                'supplemental_role_defs',
                array_values(array_unique($blog_pp_only))
            );
        }
    } finally {
        restore_current_blog();
        _cme_set_roles_site(get_current_blog_id());

        if (defined('PRESSPERMIT_ACTIVE')) {
            pp_refresh_options();
        }
    }
}

/**
 * Points the global roles object at a site.
 *
 * @param int $site_id Site ID.
 * @return void
 */
function _cme_set_roles_site($site_id)
{
    global $wp_roles;

    if (method_exists($wp_roles, 'for_site')) {
        $wp_roles->for_site($site_id);
    } else {
        $wp_roles->reinit();
    }
}
