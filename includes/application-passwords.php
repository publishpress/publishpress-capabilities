<?php
/*
 * PublishPress Capabilities
 *
 * Application password capability restrictions.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('pp_capabilities_application_password_capabilities_enabled')) {
    function pp_capabilities_application_password_capabilities_enabled()
    {
        return !empty(get_option('cme_capabilities_application_password_capabilities'))
            && class_exists('WP_Application_Passwords');
    }
}

if (!function_exists('pp_capabilities_application_password_option_name')) {
    function pp_capabilities_application_password_option_name()
    {
        return 'cme_application_password_capabilities';
    }
}

if (!function_exists('pp_capabilities_application_password_subject_prefix')) {
    function pp_capabilities_application_password_subject_prefix()
    {
        return 'app_password_';
    }
}

if (!function_exists('pp_capabilities_application_password_subject_value')) {
    function pp_capabilities_application_password_subject_value($user_id, $uuid)
    {
        return pp_capabilities_application_password_subject_prefix() . absint($user_id) . '_' . sanitize_key($uuid);
    }
}

if (!function_exists('pp_capabilities_parse_application_password_subject')) {
    function pp_capabilities_parse_application_password_subject($subject)
    {
        if (!is_scalar($subject)) {
            return false;
        }

        $subject = sanitize_key((string) $subject);
        $prefix = pp_capabilities_application_password_subject_prefix();

        if (!preg_match('/^' . preg_quote($prefix, '/') . '([0-9]+)_([a-z0-9-]+)$/', $subject, $matches)) {
            return false;
        }

        return [
            'user_id' => absint($matches[1]),
            'uuid'    => sanitize_key($matches[2]),
        ];
    }
}

if (!function_exists('pp_capabilities_is_application_password_subject')) {
    function pp_capabilities_is_application_password_subject($subject)
    {
        return (bool) pp_capabilities_parse_application_password_subject($subject);
    }
}

if (!function_exists('pp_capabilities_application_password_storage_key')) {
    function pp_capabilities_application_password_storage_key($user_id, $uuid)
    {
        return absint($user_id) . ':' . sanitize_key($uuid);
    }
}

if (!function_exists('pp_capabilities_normalize_capability_names')) {
    function pp_capabilities_normalize_capability_names($capabilities)
    {
        $normalized = [];

        foreach ((array) $capabilities as $capability_key => $capability_value) {
            $capability = (is_string($capability_key) && ('' !== $capability_key) && !is_numeric($capability_key))
                ? $capability_key
                : $capability_value;

            if (!is_scalar($capability)) {
                continue;
            }

            $capability = sanitize_text_field((string) $capability);

            if ('' !== $capability) {
                $normalized[$capability] = true;
            }
        }

        return $normalized;
    }
}

if (!function_exists('pp_capabilities_get_application_password_entry')) {
    function pp_capabilities_get_application_password_entry($user_id, $uuid)
    {
        if (!class_exists('WP_Application_Passwords')) {
            return false;
        }

        $user_id = absint($user_id);
        $uuid = sanitize_key($uuid);

        if (!$user_id || !$uuid) {
            return false;
        }

        if (method_exists('WP_Application_Passwords', 'get_user_application_password')) {
            $application_password = WP_Application_Passwords::get_user_application_password($user_id, $uuid);

            if (!empty($application_password) && is_array($application_password)) {
                $application_password['uuid'] = !empty($application_password['uuid'])
                    ? sanitize_key($application_password['uuid'])
                    : $uuid;

                return $application_password;
            }
        }

        if (!method_exists('WP_Application_Passwords', 'get_user_application_passwords')) {
            return false;
        }

        foreach ((array) WP_Application_Passwords::get_user_application_passwords($user_id) as $application_password) {
            if (empty($application_password['uuid'])) {
                continue;
            }

            if (sanitize_key($application_password['uuid']) === $uuid) {
                return $application_password;
            }
        }

        return false;
    }
}

if (!function_exists('pp_capabilities_can_manage_application_password')) {
    function pp_capabilities_can_manage_application_password($user_id)
    {
        $user_id = absint($user_id);

        if (!$user_id) {
            return false;
        }

        return current_user_can('administrator')
            || (is_multisite() && is_super_admin())
            || current_user_can('edit_user', $user_id);
    }
}

if (!function_exists('pp_capabilities_application_password_subject_exists')) {
    function pp_capabilities_application_password_subject_exists($subject)
    {
        $parsed = pp_capabilities_parse_application_password_subject($subject);

        if (!$parsed) {
            return false;
        }

        return (bool) pp_capabilities_get_application_password_entry($parsed['user_id'], $parsed['uuid']);
    }
}

if (!function_exists('pp_capabilities_can_manage_application_password_subject')) {
    function pp_capabilities_can_manage_application_password_subject($subject)
    {
        if (!pp_capabilities_application_password_capabilities_enabled()) {
            return false;
        }

        $parsed = pp_capabilities_parse_application_password_subject($subject);

        if (!$parsed || !pp_capabilities_can_manage_application_password($parsed['user_id'])) {
            return false;
        }

        return pp_capabilities_application_password_subject_exists($subject);
    }
}

if (!function_exists('pp_capabilities_get_application_password_subjects')) {
    function pp_capabilities_get_application_password_subjects()
    {
        if (!pp_capabilities_application_password_capabilities_enabled() || !method_exists('WP_Application_Passwords', 'get_user_application_passwords')) {
            return [];
        }

        $subjects = [];
        $users = get_users([
            'meta_key'     => '_application_passwords',
            'meta_compare' => 'EXISTS',
            'fields'       => ['ID', 'user_login', 'display_name'],
        ]);

        foreach ((array) $users as $user) {
            $user_id = absint($user->ID);

            if (!pp_capabilities_can_manage_application_password($user_id)) {
                continue;
            }

            foreach ((array) WP_Application_Passwords::get_user_application_passwords($user_id) as $application_password) {
                if (empty($application_password['uuid'])) {
                    continue;
                }

                $uuid = sanitize_key($application_password['uuid']);
                $subject = pp_capabilities_application_password_subject_value($user_id, $uuid);
                $password_name = !empty($application_password['name'])
                    ? sanitize_text_field($application_password['name'])
                    : __('Unnamed application password', 'capability-manager-enhanced');
                $user_label = !empty($user->display_name)
                    ? $user->display_name
                    : (!empty($user->user_login) ? $user->user_login : $user_id);

                $subjects[$subject] = [
                    'user_id' => $user_id,
                    'uuid'    => $uuid,
                    'name'    => $password_name,
                    'label'   => sprintf('%1$s - %2$s', $password_name, $user_label),
                ];
            }
        }

        uasort($subjects, function ($a, $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });

        return apply_filters('pp_capabilities_application_password_subjects', $subjects);
    }
}

if (!function_exists('pp_capabilities_get_application_password_denied_capabilities')) {
    function pp_capabilities_get_application_password_denied_capabilities($subject)
    {
        $parsed = pp_capabilities_parse_application_password_subject($subject);

        if (!$parsed) {
            return [];
        }

        $option = get_option(pp_capabilities_application_password_option_name(), []);
        $storage_key = pp_capabilities_application_password_storage_key($parsed['user_id'], $parsed['uuid']);

        if (empty($option[$storage_key]['denied']) || !is_array($option[$storage_key]['denied'])) {
            return [];
        }

        return pp_capabilities_normalize_capability_names($option[$storage_key]['denied']);
    }
}

if (!function_exists('pp_capabilities_application_password_cap_is_allowed')) {
    function pp_capabilities_application_password_cap_is_allowed($subject, $capability)
    {
        $capability = is_scalar($capability) ? sanitize_text_field((string) $capability) : '';

        if ('' === $capability) {
            return true;
        }

        $denied_capabilities = pp_capabilities_get_application_password_denied_capabilities($subject);

        return empty($denied_capabilities[$capability]);
    }
}

if (!function_exists('pp_capabilities_get_displayed_post_type_capabilities')) {
    function pp_capabilities_get_displayed_post_type_capabilities()
    {
        $capabilities = [];

        if (!function_exists('get_post_types') || !function_exists('get_taxonomies')) {
            return $capabilities;
        }

        $post_types = get_post_types(['public' => true, 'show_ui' => true], 'object', 'or');

        if (get_option('cme_capabilities_show_private_post_types', 0)) {
            $post_types = array_merge(
                (array) $post_types,
                (array) get_post_types(['public' => false, 'show_ui' => true], 'object', 'or')
            );
        }

        $post_types = apply_filters('cme_filterable_post_types', $post_types);

        foreach ((array) $post_types as $post_type) {
            if (!is_object($post_type) || empty($post_type->cap)) {
                continue;
            }

            foreach ((array) $post_type->cap as $capability) {
                if (is_scalar($capability)) {
                    $capabilities[] = $capability;
                }
            }
        }

        $taxonomies = apply_filters('cme_filterable_taxonomies', get_taxonomies(['public' => true, 'show_ui' => true], 'object', 'or'));

        if (function_exists('get_taxonomy')) {
            $nav_menu_taxonomy = get_taxonomy('nav_menu');

            if ($nav_menu_taxonomy) {
                $taxonomies['nav_menu'] = $nav_menu_taxonomy;
            }
        }

        foreach ((array) $taxonomies as $taxonomy) {
            if (!is_object($taxonomy) || empty($taxonomy->cap)) {
                continue;
            }

            foreach ((array) $taxonomy->cap as $capability) {
                if (is_scalar($capability)) {
                    $capabilities[] = $capability;
                }
            }
        }

        return $capabilities;
    }
}

if (!function_exists('pp_capabilities_get_application_password_managed_capabilities')) {
    function pp_capabilities_get_application_password_managed_capabilities($base_capabilities = [])
    {
        $capabilities = pp_capabilities_normalize_capability_names($base_capabilities);
        $capabilities += pp_capabilities_normalize_capability_names(pp_capabilities_get_displayed_post_type_capabilities());

        if (function_exists('_cme_core_caps')) {
            $capabilities += pp_capabilities_normalize_capability_names(array_keys(_cme_core_caps()));
        }

        if (is_admin() && defined('CME_FILE')) {
            $plugin_capabilities_file = dirname(CME_FILE) . '/includes/plugin-capabilities.php';
            if (is_readable($plugin_capabilities_file)) {
                require_once $plugin_capabilities_file;

                if (class_exists('PublishPress\Capabilities\Plugin_Capabilities')) {
                    \PublishPress\Capabilities\Plugin_Capabilities::instance();
                }
            }

            $extractor_capabilities_file = dirname(CME_FILE) . '/includes/extractor-capabilities.php';
            if (is_readable($extractor_capabilities_file)) {
                require_once $extractor_capabilities_file;
            }
        }

        $plugin_capabilities = apply_filters('cme_plugin_capabilities', []);

        if (function_exists('pp_capabilities_plugin_capability_lookup')) {
            $capabilities += pp_capabilities_normalize_capability_names(array_keys(pp_capabilities_plugin_capability_lookup($plugin_capabilities)));
        } else {
            foreach ((array) $plugin_capabilities as $plugin_capability_payload) {
                $capabilities += pp_capabilities_normalize_capability_names((array) $plugin_capability_payload);
            }
        }

        $all_capabilities = apply_filters('capsman_get_capabilities', array_keys($capabilities), 'capsman');
        $all_capabilities = apply_filters('members_get_capabilities', $all_capabilities);
        $capabilities += pp_capabilities_normalize_capability_names($all_capabilities);

        foreach ((array) get_option(pp_capabilities_application_password_option_name(), []) as $application_password_caps) {
            if (!empty($application_password_caps['denied']) && is_array($application_password_caps['denied'])) {
                $capabilities += pp_capabilities_normalize_capability_names($application_password_caps['denied']);
            }
        }

        $is_administrator = current_user_can('administrator') || (is_multisite() && is_super_admin());

        if (!$is_administrator) {
            foreach (array_keys($capabilities) as $capability) {
                if (!current_user_can($capability)) {
                    unset($capabilities[$capability]);
                }
            }
        }

        return array_keys($capabilities);
    }
}

if (!function_exists('pp_capabilities_get_application_password_ui_capabilities')) {
    function pp_capabilities_get_application_password_ui_capabilities($subject, $known_capabilities = [])
    {
        $capabilities = pp_capabilities_normalize_capability_names(
            pp_capabilities_get_application_password_managed_capabilities($known_capabilities)
        );
        $denied_capabilities = pp_capabilities_get_application_password_denied_capabilities($subject);
        $capabilities += $denied_capabilities;
        $role_capabilities = [];

        foreach (array_keys($capabilities) as $capability) {
            $role_capabilities[$capability] = empty($denied_capabilities[$capability]);
        }

        return $role_capabilities;
    }
}

if (!function_exists('pp_capabilities_save_application_password_capabilities')) {
    function pp_capabilities_save_application_password_capabilities($subject, $posted_capabilities, $managed_capabilities)
    {
        $parsed = pp_capabilities_parse_application_password_subject($subject);

        if (!$parsed || !pp_capabilities_application_password_subject_exists($subject)) {
            return false;
        }

        $storage_key = pp_capabilities_application_password_storage_key($parsed['user_id'], $parsed['uuid']);
        $all_restrictions = get_option(pp_capabilities_application_password_option_name(), []);

        if (!is_array($all_restrictions)) {
            $all_restrictions = [];
        }

        $managed_capabilities = pp_capabilities_normalize_capability_names($managed_capabilities);
        $existing_denials = pp_capabilities_get_application_password_denied_capabilities($subject);
        $posted_capabilities = is_array($posted_capabilities) ? $posted_capabilities : [];
        $posted_allowed = [];

        foreach ($posted_capabilities as $capability => $value) {
            if (!is_scalar($capability) || !boolval($value)) {
                continue;
            }

            $capability = sanitize_text_field((string) $capability);

            if (isset($managed_capabilities[$capability])) {
                $posted_allowed[$capability] = true;
            }
        }

        $denied_capabilities = [];

        foreach ($existing_denials as $capability => $denied) {
            if (!isset($managed_capabilities[$capability])) {
                $denied_capabilities[$capability] = false;
            }
        }

        foreach (array_keys($managed_capabilities) as $capability) {
            if (empty($posted_allowed[$capability])) {
                $denied_capabilities[$capability] = false;
            }
        }

        if (empty($denied_capabilities)) {
            unset($all_restrictions[$storage_key]);
        } else {
            ksort($denied_capabilities);

            $all_restrictions[$storage_key] = [
                'user_id' => $parsed['user_id'],
                'uuid'    => $parsed['uuid'],
                'denied'  => $denied_capabilities,
            ];
        }

        update_option(pp_capabilities_application_password_option_name(), $all_restrictions, false);

        return true;
    }
}

if (!class_exists('PP_Capabilities_Application_Password_Capabilities')) {
    class PP_Capabilities_Application_Password_Capabilities
    {
        private static $instance = null;

        private $current_application_password = [];

        public static function instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            add_action('application_password_did_authenticate', [$this, 'capture_application_password'], 10, 2);
            add_filter('user_has_cap', [$this, 'filter_user_capabilities'], PHP_INT_MAX, 4);
        }

        public function capture_application_password($user, $item)
        {
            if (!pp_capabilities_application_password_capabilities_enabled() || !is_object($user) || empty($user->ID)) {
                return;
            }

            $uuid = '';

            if (is_array($item) && !empty($item['uuid'])) {
                $uuid = sanitize_key($item['uuid']);
            } elseif (is_object($item) && !empty($item->uuid)) {
                $uuid = sanitize_key($item->uuid);
            }

            if (!$uuid) {
                return;
            }

            $this->current_application_password = [
                'user_id' => absint($user->ID),
                'uuid'    => $uuid,
            ];
        }

        public function filter_user_capabilities($allcaps, $caps, $args, $user)
        {
            if (!pp_capabilities_application_password_capabilities_enabled() || empty($this->current_application_password)) {
                return $allcaps;
            }

            if (empty($user->ID) || (int) $user->ID !== (int) $this->current_application_password['user_id']) {
                return $allcaps;
            }

            $subject = pp_capabilities_application_password_subject_value(
                $this->current_application_password['user_id'],
                $this->current_application_password['uuid']
            );

            foreach (pp_capabilities_get_application_password_denied_capabilities($subject) as $capability => $denied) {
                $allcaps[$capability] = false;
            }

            return $allcaps;
        }
    }
}
