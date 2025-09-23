<?php

namespace PublishPress\Capabilities\Classes;

class PP_Capabilities_Installer
{
    /**
     * Runs methods when the plugin is running for the first time.
     *
     * @param string $currentVersion
     */
    public static function runInstallTasks($currentVersion)
    {
        self::addPluginCapabilities();

        /**
         * @param string $currentVersion
         */
        do_action('pp_capabilities_installed', $currentVersion);
    }

    /**
     * Runs methods when the plugin is being upgraded to a most recent version.
     *
     * @param string $currentVersions
     */
    public static function runUpgradeTasks($currentVersions)
    {
        if (version_compare($currentVersions, '2.8.0', '<')) {
            self::addPluginCapabilities();
        }
        if (version_compare($currentVersions, '2.9.0', '<')) {
            self::addFrontendFeaturesCapabilities();
        }
        if (version_compare($currentVersions, '2.17.0', '<')) {
            self::addRedirectsCapabilities();
        }

        /**
         * @param string $previousVersion
         */
        do_action('pp_capabilities_upgraded', $currentVersions);
    }

    private static function addPluginCapabilities()
    {
        $eligible_roles  = [];
        $pp_capabilities = apply_filters('cme_publishpress_capabilities_capabilities', []);

        /**
         * We're not saving installation version prior to 2.8.0.
         * So, we need another way to know if this is an upgrade or
         * new installs to add or upgrade role capabilities.
         */
        foreach ( wp_roles()->roles as $role_name => $role ) {
            $role_object = get_role($role_name);
            if (is_object($role_object) && $role_object->has_cap('manage_capabilities')) {
                $eligible_roles[] = $role_name;
            }
        }

        /**
         * If it's a fresh installation, we're giving 'administrator' and 'editor'
         * all capabilities
         */
        if (empty($eligible_roles)) {
            $eligible_roles = ['administrator', 'editor'];
        }

        /**
         * Add capabilities to eligible roles
         */
        foreach ($eligible_roles as $eligible_role) {
            $role = get_role($eligible_role);
            foreach ($pp_capabilities as $cap) {
                if (is_object($role) && !$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    private static function addFrontendFeaturesCapabilities()
    {

        $eligible_roles = ['administrator', 'editor'];

        /**
         * Add frontend features capabilities to admin and editor roles
         */
        foreach ($eligible_roles as $eligible_role) {
            $role = get_role($eligible_role);
            if (is_object($role) && !$role->has_cap('manage_capabilities_frontend_features')) {
                $role->add_cap('manage_capabilities_frontend_features');
            }
        }
    }

    private static function addRedirectsCapabilities()
    {

        $eligible_roles = ['administrator', 'editor'];

        /**
         * Add redirect capabilities to admin and editor roles
         */
        foreach ($eligible_roles as $eligible_role) {
            $role = get_role($eligible_role);
            if (is_object($role) && !$role->has_cap('manage_capabilities_redirects')) {
                $role->add_cap('manage_capabilities_redirects');
            }
        }

        /**
         * Migrate roles redirect setting to new option
         *
         */
        $role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];
        foreach ( wp_roles()->roles as $role_name => $role ) {
            //get role option
            $role_option = get_option("pp_capabilities_{$role_name}_role_option", []);
            if (is_array($role_option) && !empty($role_option)) {
                if (isset($role_option['login_redirect'])) {
                    $role_redirects[$role_name]['login_redirect'] = $role_option['login_redirect'];
                }
                if (isset($role_option['logout_redirect'])) {
                    $role_redirects[$role_name]['logout_redirect'] = $role_option['logout_redirect'];
                }
                if (isset($role_option['referer_redirect'])) {
                    $role_redirects[$role_name]['referer_redirect'] = $role_option['referer_redirect'];
                }
                if (isset($role_option['custom_redirect'])) {
                    $role_redirects[$role_name]['custom_redirect'] = $role_option['custom_redirect'];
                }
            }
        }
        if (!empty($role_redirects)) {
            update_option('capsman_role_redirects', $role_redirects);
        }
    }

}
