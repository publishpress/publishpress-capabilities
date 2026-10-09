<?php

namespace PublishPress\Capabilities;

/**
 * Applies role-based restrictions to post list table columns.
 */
class PP_Capabilities_Admin_Columns
{
    const OPTION_NAME = 'capsman_admin_columns';

    const AVAILABLE_COLUMNS_OPTION = 'capsman_admin_columns_available';

    private static $instance;

    private $registered_post_types = [];

    private $collecting_columns = false;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        if (!is_admin()) {
            return;
        }

        add_action('registered_post_type', [$this, 'registerPostType'], 20, 2);
        add_action('admin_init', [$this, 'registerPostTypeFilters'], 20);
    }

    /**
     * Registers column filters for post types that were registered before this class loaded.
     *
     * @return void
     */
    public function registerPostTypeFilters()
    {
        foreach ($this->getPostTypes() as $post_type => $post_type_object) {
            $this->registerPostType($post_type, $post_type_object);
        }
    }

    /**
     * Registers the column restriction callback for a post type.
     *
     * @param string        $post_type        Post type slug.
     * @param \WP_Post_Type $post_type_object Post type object.
     * @return void
     */
    public function registerPostType($post_type, $post_type_object)
    {
        if (
            isset($this->registered_post_types[$post_type])
            || !is_object($post_type_object)
            || empty($post_type_object->show_ui)
            || 'attachment' === $post_type
        ) {
            return;
        }

        $this->registered_post_types[$post_type] = true;

        add_filter(
            "manage_{$post_type}_posts_columns",
            function ($columns) use ($post_type) {
                return $this->filterColumns($columns, $post_type);
            },
            PHP_INT_MAX
        );
    }

    /**
     * Removes columns hidden for any role assigned to the current user.
     *
     * @param array  $columns   List table columns.
     * @param string $post_type Post type slug.
     * @return array
     */
    public function filterColumns($columns, $post_type)
    {
        if (!is_array($columns)) {
            return $columns;
        }

        if (!$this->collecting_columns && $this->isPostListScreen($post_type)) {
            $this->recordAvailableColumns($post_type, $columns);
        }

        if (
            $this->collecting_columns
            || !pp_capabilities_feature_enabled('admin-columns')
            || (is_multisite() && is_super_admin() && !defined('PP_CAPABILITIES_RESTRICT_SUPER_ADMIN'))
        ) {
            return $columns;
        }

        $user = wp_get_current_user();
        $roles = is_object($user) && isset($user->roles) ? (array) $user->roles : [];
        $roles = apply_filters('pp_capabilities_admin_columns_apply_role_restrictions', $roles, $post_type, $user);

        if (empty($roles)) {
            return $columns;
        }

        $settings = (array) get_option(self::OPTION_NAME, []);
        $hidden_columns = [];

        foreach ($roles as $role) {
            $role = sanitize_key($role);
            if (isset($settings[$role][$post_type]) && is_array($settings[$role][$post_type])) {
                $hidden_columns = array_merge($hidden_columns, $settings[$role][$post_type]);
            }
        }

        $hidden_columns = self::sanitizeColumnKeys($hidden_columns);
        $hidden_columns = apply_filters(
            'pp_capabilities_admin_columns_hidden_columns',
            $hidden_columns,
            $post_type,
            $roles,
            $user
        );
        $hidden_columns = array_diff(self::sanitizeColumnKeys($hidden_columns), ['cb', 'title']);

        foreach ($hidden_columns as $column_name) {
            unset($columns[$column_name]);
        }

        return $columns;
    }

    /**
     * Returns post types supported by the Admin Columns screen.
     *
     * @return \WP_Post_Type[]
     */
    public function getPostTypes()
    {
        $post_types = get_post_types(['show_ui' => true], 'objects');
        unset($post_types['attachment']);

        $post_types = (array) apply_filters('pp_capabilities_admin_columns_post_types', $post_types);

        foreach ($post_types as $post_type => $post_type_object) {
            if (!is_object($post_type_object) || empty($post_type_object->show_ui)) {
                unset($post_types[$post_type]);
            }
        }

        uasort(
            $post_types,
            function ($first, $second) {
                return strcasecmp($first->labels->singular_name, $second->labels->singular_name);
            }
        );

        return $post_types;
    }

    /**
     * Builds the columns for a post type using the same filters as WordPress core.
     *
     * @param string $post_type Post type slug.
     * @return array
     */
    public function getColumns($post_type)
    {
        $post_type_object = get_post_type_object($post_type);
        if (!is_object($post_type_object)) {
            return [];
        }

        $columns = [
            'cb'    => '<input type="checkbox" />',
            'title' => _x('Title', 'column name'),
        ];

        if (post_type_supports($post_type, 'author')) {
            $columns['author'] = __('Author');
        }

        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $taxonomies = wp_filter_object_list($taxonomies, ['show_admin_column' => true], 'and', 'name');
        $taxonomies = apply_filters("manage_taxonomies_for_{$post_type}_columns", $taxonomies, $post_type);
        $taxonomies = array_filter($taxonomies, 'taxonomy_exists');

        foreach ($taxonomies as $taxonomy) {
            if ('category' === $taxonomy) {
                $column_key = 'categories';
            } elseif ('post_tag' === $taxonomy) {
                $column_key = 'tags';
            } else {
                $column_key = 'taxonomy-' . $taxonomy;
            }

            $taxonomy_object = get_taxonomy($taxonomy);
            if (is_object($taxonomy_object)) {
                $columns[$column_key] = $taxonomy_object->labels->name;
            }
        }

        if (post_type_supports($post_type, 'comments')) {
            $columns['comments'] = __('Comments');
        }

        $columns['date'] = __('Date');

        $this->collecting_columns = true;

        if ('page' === $post_type) {
            $columns = apply_filters('manage_pages_columns', $columns);
        } else {
            $columns = apply_filters('manage_posts_columns', $columns, $post_type);
        }

        $columns = apply_filters("manage_{$post_type}_posts_columns", $columns);
        $this->collecting_columns = false;

        unset($columns['cb'], $columns['title']);

        if (!is_array($columns)) {
            return [];
        }

        $observed_columns = $this->getObservedColumns($post_type);

        return $columns + $observed_columns;
    }

    /**
     * Records columns discovered on an actual post list screen.
     *
     * Some plugins only register columns when their list screen is active, so
     * those columns cannot be discovered directly from the settings screen.
     *
     * @param string $post_type Post type slug.
     * @param array  $columns   Unrestricted list table columns.
     * @return void
     */
    public function recordAvailableColumns($post_type, $columns)
    {
        $post_type = sanitize_key($post_type);
        $available_columns = [];

        foreach ((array) $columns as $column_name => $column_label) {
            if (in_array($column_name, ['cb', 'title'], true)) {
                continue;
            }

            $column_name = sanitize_text_field((string) $column_name);
            if ('' === $column_name) {
                continue;
            }

            $plain_label = is_scalar($column_label)
                ? trim(wp_strip_all_tags((string) $column_label))
                : '';

            if ('' === $plain_label) {
                $plain_label = ucwords(str_replace(['-', '_'], ' ', $column_name));
            }

            $available_columns[$column_name] = $plain_label;
        }

        $observed_columns = (array) get_option(self::AVAILABLE_COLUMNS_OPTION, []);
        $current_columns = isset($observed_columns[$post_type]) && is_array($observed_columns[$post_type])
            ? $observed_columns[$post_type]
            : [];

        if ($current_columns === $available_columns) {
            return;
        }

        $observed_columns[$post_type] = $available_columns;
        update_option(self::AVAILABLE_COLUMNS_OPTION, $observed_columns, false);
    }

    /**
     * Returns columns previously observed on a post list screen.
     *
     * @param string $post_type Post type slug.
     * @return array
     */
    private function getObservedColumns($post_type)
    {
        $observed_columns = (array) get_option(self::AVAILABLE_COLUMNS_OPTION, []);

        return isset($observed_columns[$post_type]) && is_array($observed_columns[$post_type])
            ? $observed_columns[$post_type]
            : [];
    }

    /**
     * Checks whether columns are being filtered for their real list screen.
     *
     * @param string $post_type Post type slug.
     * @return boolean
     */
    private function isPostListScreen($post_type)
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return is_object($screen)
            && isset($screen->base, $screen->post_type)
            && 'edit' === $screen->base
            && $post_type === $screen->post_type;
    }

    /**
     * Sanitizes a submitted list of column keys.
     *
     * @param array $column_keys Column keys.
     * @return array
     */
    public static function sanitizeColumnKeys($column_keys)
    {
        $sanitized = [];

        foreach ((array) $column_keys as $column_key) {
            if (!is_scalar($column_key)) {
                continue;
            }

            $column_key = sanitize_text_field((string) $column_key);
            if ('' !== $column_key) {
                $sanitized[] = $column_key;
            }
        }

        return array_values(array_unique($sanitized));
    }
}
