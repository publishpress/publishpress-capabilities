<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Class PP_Capabilities_Roles_List_Table
 */
class PP_Capabilities_Roles_List_Table extends WP_List_Table
{

    /**
     * The roles manager
     *
     * @var Pp_Roles_Manager
     */
    protected $manager;
    private $default_role = '';

	/**
	 * The current view.
	 *
	 * @access public
	 * @var    string
	 */
	public $role_view = 'all';

	/**
	 * Array of role views.
	 *
	 * @access public
	 * @var    array
	 */
	public $role_views = array();

	/**
	 * Allowed role views.
	 *
	 * @access public
	 * @var    array
	 */
	public $allowed_role_views = array();

    /**
     * PP_Capabilities_Roles_List_Table constructor.
     *
     * @param array $args
     */
    function __construct($args = [])
    {
        global $status, $page;

        //Set parent defaults
        parent::__construct([
            'singular' => 'role',     //singular name of the listed records
            'plural' => 'roles',    //plural name of the listed records
            'ajax' => true        //does this table support ajax?
        ]);

        $this->manager = pp_capabilities_roles()->manager;

        $this->default_role = get_option('default_role');

		// Get the role views.
		$this->allowed_role_views = array_keys($this->get_views());

		// Get the current view.
        if (isset($_GET['view']) && in_array(sanitize_key($_GET['view']), $this->allowed_role_views)) {
            $this->role_view = sanitize_key($_GET['view']);
        }
    }

	/**
	 * Returns an array of views for the list table.
	 *
	 * @access protected
	 * @return array
	 */
	protected function get_views() {

        $views     = array();
        $current   = ' class="current"';

        $role_view_filters = [
            'all'       => _n_noop('All %s', 'All %s', 'capability-manager-enhanced'),
            'mine'      => _n_noop('Mine %s', 'Mine %s', 'capability-manager-enhanced'),
            'active'    => _n_noop('Has Users %s', 'Has Users %s', 'capability-manager-enhanced'),
            'inactive'  => _n_noop('No Users %s', 'No Users %s', 'capability-manager-enhanced'),
            'editable'  => _n_noop('Editable %s', 'Editable %s', 'capability-manager-enhanced'),
            'uneditable'=> _n_noop('Uneditable %s', 'Uneditable %s', 'capability-manager-enhanced'),
            'system'    => _n_noop('System %s', 'System %s', 'capability-manager-enhanced'),
        ];

        foreach($role_view_filters as $view => $noop){
            $view_roles = $this->manager->get_roles_for_list_table($view, true);

            //add role view
            $this->role_views[$view] = ['roles' => $view_roles];

            $count = count($view_roles);

            // Skip any views with 0 roles.
            if ((int)$count === 0) {
                continue;
            }

            // Add the view link.
            $translated_view_label = translate_nooped_plural($noop, $count, $noop['domain']);
            $count_html = sprintf('<span class="count">(%s)</span>', number_format_i18n($count));
            $view_label = preg_replace('/(?<!%)%(?:\d+\$)?s/', $count_html, (string) $translated_view_label);

            if (null === $view_label) {
                $view_label = $translated_view_label . ' ' . $count_html;
            }

            $views[ $view ] = sprintf(
                '<a%s href="%s">%s</a>',
                $view === $this->role_view ? $current : '',
                esc_url(
                    add_query_arg(
                        [
                            'page' => 'pp-capabilities-roles',
                            'view' => esc_attr($view)
                        ],
                        admin_url('admin.php')
                    )
                ),
                $view_label
            );
        }

        return $views;
    }

    /**
     * Get a list of CSS classes for the WP_List_Table table tag.
     *
     * @return array List of CSS classes for the table tag.
     */
    protected function get_table_classes()
    {

        return parent::get_table_classes();
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['roles-tr'];

        echo sprintf('<tr id="%s" class="%s">', 'role-' . esc_attr(md5($item['role'])), esc_attr(implode(' ', $class)));
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     * Get list table columns
     *
     * @return array
     */
    public function get_columns()
    {
        /**
         * Note, the table is currently using column data from
         * initRolesAdmin() in manager.php to display the
         * column.
         */
        $columns = [
            'cb'              => '<input type="checkbox"/>', //Render a checkbox instead of text
            'name'            => esc_html__('Role Name', 'capability-manager-enhanced'),
            'count'           => esc_html__('Users'),
            'role_type'       => esc_html__('Role Type', 'capability-manager-enhanced'),
            'default_role'    => esc_html__('Default Role', 'capability-manager-enhanced'),
            'admin_access'    => esc_html__('Admin Access', 'capability-manager-enhanced'),
        ];

        return $columns;
    }

    /**
     * Get a list of sortable columns.
     *
     * @return array
     *
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'name'          => ['name', true],
            'count'         => ['count', true],
        ];

        return $sortable_columns;
    }

    /**
     * Generates and display row actions links for the list table.
     *
     * @param object $item The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary Primary column name.
     *
     * @return string The row actions HTML, or an empty string if the current column is the primary column.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        static $pp_only;

        //Build row actions
        if (pp_capabilities_is_editable_role($item['role'])) {

            $actions = [];

            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        ['page' => 'pp-capabilities-roles', 'add' => 'new_item', 'role_action' => 'edit', 'role' => esc_attr($item['role'])],
                        admin_url('admin.php')
                    )
                ),
                esc_html__('Edit')
            );

            $actions['copy'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        ['page' => 'pp-capabilities-roles', 'add' => 'new_item', 'role_action' => 'copy', 'role' => esc_attr($item['role'])],
                        admin_url('admin.php')
                    )
                ),
                esc_html__('Copy', 'capability-manager-enhanced')
            );

        } else {
            $actions = [
                'capabilities' => '<span class="pp-caps-action-note">' . esc_html__('(non-editable role)', 'capability-manager-enhanced') . '</span>',
            ];

            if (defined("PRESSPERMIT_ACTIVE")) {
                if (!isset($pp_only)) {
                    $pp_only = (array) pp_capabilities_get_permissions_option('supplemental_role_defs');
                }

                if (in_array($item['role'], $pp_only)) {
                    $actions['unhide'] = sprintf(
                        '<a href="%s" class="hide-role">%s</a>',
                        add_query_arg([
                            'page' => 'pp-capabilities-roles',
                            'action' => 'pp-roles-unhide-role',
                            'role' => esc_attr($item['role']),
                            '_wpnonce' => wp_create_nonce('bulk-roles')
                        ],
                        admin_url('admin.php')),
                        esc_html__('Unhide', 'capability-manager-enhanced')
                    );
                }
            }
        }

        if (!$this->manager->is_system_role($item['role']) && ($this->default_role != $item['role']) && pp_capabilities_is_editable_role($item['role'])) {
            //Dont these actions if it's a system role
            $actions = array_merge($actions, [
                'delete' => sprintf(
                    '<a href="%s" class="delete-role">%s</a>',
                    add_query_arg([
                        'page' => 'pp-capabilities-roles',
                        'action' => 'pp-roles-delete-role',
                        'role' => esc_attr($item['role']),
                        '_wpnonce' => wp_create_nonce('bulk-roles')
                    ],
                    admin_url('admin.php')),
                    esc_html__('Delete')
                ),
            ]);

            if (defined("PRESSPERMIT_ACTIVE")) {
                if (!isset($pp_only)) {
                    $pp_only = (array) pp_capabilities_get_permissions_option('supplemental_role_defs');
                }

                if (!in_array($item['role'], $pp_only)) {
                    $actions['hide'] = sprintf(
                        '<a href="%s" class="hide-role">%s</a>',
                        add_query_arg([
                            'page' => 'pp-capabilities-roles',
                            'action' => 'pp-roles-hide-role',
                            'role' => esc_attr($item['role']),
                            '_wpnonce' => wp_create_nonce('bulk-roles')
                        ],
                        admin_url('admin.php')),
                        esc_html__('Hide')
                    );
                }
            }
        }

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Add default
     *
     * @param object $item
     * @param string $column_name
     *
     * @return mixed|string|void
     */
    protected function column_default($item, $column_name)
    {
        return !empty($item[$column_name]) ? $item[$column_name] : '&mdash;';
    }

    /**
     * The checkbox column
     *
     * @param object $item
     *
     * @return string|void
     */
    protected function column_cb($item)
    {
        $disabled = ($this->manager->is_system_role($item['role']) || ($this->default_role == $item['role']) || !pp_capabilities_is_editable_role($item['role'])) ? ' disabled=disabled' : '';
        $out = sprintf('<input type="checkbox" name="%1$s[]" value="%2$s"' . $disabled .  ' />', 'role', $item['role']);

        return $out;
    }

    /**
     * The role column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_name($item)
    {
        $states = [];
        $role_states = '';

        // If the current user has this role.
        if (pp_roles_current_user_has_role($item['role'])) {
            $states['mine'] = esc_html__('Your Role', 'capability-manager-enhanced');
        }

        // If we have states, string them together.
        if (!empty($states)) {

            foreach ($states as $state => $label)
                $states[$state] = sprintf('<span class="role-state">%s</span>', $label);

            $role_states = '<span class="row-title-divider"> &ndash; </span>' . join(', ', $states);
        }

        if (pp_capabilities_is_editable_role($item['role'])) {
            $out = sprintf(
                '<a href="%1$s"><strong><span class="row-title">%2$s</span>%3$s</strong></a>',
                add_query_arg(
                    ['page' => 'pp-capabilities-roles', 'add' => 'new_item', 'role_action' => 'edit', 'role' => esc_attr($item['role'])],
                    admin_url('admin.php')
                ),
                esc_html(translate_user_role($item['name'])),
                $role_states
            );
        } else {
            $out = esc_html(translate_user_role($item['name']));
        }

        return $out;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_text($item)
    {

        return !empty($item['name']) ? translate_user_role($item['name']) : '&mdash;';
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_role_type($item)
    {
        if (!empty($item['is_system'])) {
            $out = esc_html__('WordPress Core', 'capability-manager-enhanced');
        } else {
            $out = esc_html__('Custom', 'capability-manager-enhanced');
        }

        return $out;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_default_role($item)
    {
        if ($item['role'] == get_option('default_role')) {
            $out = '<span class="dashicons dashicons-yes-alt green-check"></span>';
        } else {
            $out = '';
        }

        return $out;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_admin_access($item)
    {
        if (array_key_exists('read', $item['capabilities'])) {
            $admin_access = true;
        } else {
            $admin_access = false;
        }

        //get PublishPress capabilities role option
        $role_option = get_option("pp_capabilities_{$item['role']}_role_option", []);
        if (is_array($role_option) && !empty($role_option)
            && !empty($role_option['block_dashboard_access'])
            && (int)$role_option['block_dashboard_access'] > 0
        ) {
            // role access blocked by capabilities
            $admin_access = false;
        } elseif ($item['role'] == 'customer' && (!array_key_exists('view_admin_dashboard', $item['capabilities']) || !array_key_exists('read', $item['capabilities']))) {
            // role access blocked by woocommerce for customer unless removed by publishpress capabilities
            if (is_array($role_option) && !empty($role_option) && !empty($role_option['disable_woocommerce_admin_restrictions'])) {
                $admin_access = true;
            } else {
                $admin_access = false;
            }
        }


        if ($admin_access) {
            $out = '<span class="dashicons dashicons-yes-alt green-check"></span>';
        } else {
            $out = '<span class="dashicons dashicons-no red-check"></span>';
        }

        return $out;
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_capabilities($item)
    {

        return sprintf(
            '<a href="%s">%s</a>',
            esc_url(
                add_query_arg(
                    [
                        'page' => 'pp-capabilities',
                        'role' => esc_attr($item['role'])
                    ],
                    admin_url('admin.php')
                )
            ),
            number_format_i18n(count((array)$item['capabilities']))
        );
    }

    /**
     * The action column
     *
     * @param $item
     *
     * @return string
     */
    protected function column_count($item)
    {
        return sprintf('<a href="%s" class="">%s</a>', add_query_arg('role', esc_attr($item['role']), admin_url('users.php')), number_format_i18n($item['count']));
    }

    /**
     * Get the bulk actions to show in the top page dropdown
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $actions = [
            'pp-roles-delete-role' => esc_html__('Delete')
        ];

        return $actions;
    }

    /**
     * Process bulk actions
     */
    protected function process_bulk_action()
    {

        $query_arg = '_wpnonce';
        $action = 'bulk-' . $this->_args['plural'];
        $checked = $result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce(sanitize_key($_REQUEST[$query_arg]), $action) : false;

        if (!$checked) {
            return;
        }

        $current_action = $this->current_action();
        //Detect when a bulk action is being triggered...
        switch ($current_action) {
            case 'delete':
                break;
            default:

        }
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @param bool $sensitive Use case sensitive search
     *
     * @return bool
     */
    public function str_contains($haystack, $needles, $sensitive = true)
    {
        foreach ((array)$needles as $needle) {
            $function = $sensitive ? 'mb_strpos' : 'mb_stripos';
            if ($needle !== '' && $function($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button label.
     * @param string $input_id ID attribute value for the search input field.
     *
     *
     */
    public function search_box($text, $input_id)
    {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr(sanitize_text_field($_REQUEST['orderby'])) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr(sanitize_key($_REQUEST['order'])) . '" />';
        }
        if (!empty($_REQUEST['page'])) {
            echo '<input type="hidden" name="page" value="' . esc_attr(sanitize_key($_REQUEST['page'])) . '" />';
        }
        if (!empty($_REQUEST['view'])) {
            echo '<input type="hidden" name="view" value="' . esc_attr(sanitize_key($_REQUEST['view'])) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>"/>
            <?php submit_button($text, '', '', false, ['id' => 'search-submit']); ?>
        </p>
        <?php
    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page(str_replace('-', '_', $this->screen->id . '_per_page'), 999);

        /**
         * handle bulk actions.
         */
        $this->process_bulk_action();

        /**
         * Fetch the data
         */
        if (!empty($this->role_views[$this->role_view]['roles'])) {
            $data = $this->role_views[$this->role_view]['roles'];
        } else {
            $data = [];
        }

        /**
         * Handle search
         */
        if ((!empty($_REQUEST['s'])) && $search = sanitize_text_field($_REQUEST['s'])) {
            $data_filtered = [];
            foreach ($data as $item) {
                if ($this->str_contains($item['role'], $search, false) || $this->str_contains(translate_user_role($item['name']), $search, false)) {
                    $data_filtered[] = $item;
                }
            }
            $data = $data_filtered;
        }

        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a, $b)
        {
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'role'; //If no sort, default to role
            $order = (!empty($_REQUEST['order'])) ? sanitize_key($_REQUEST['order']) : 'asc'; //If no order, default to asc
            $result = strnatcasecmp(is_array($a[$orderby]) ? count($a[$orderby]) : $a[$orderby], is_array($b[$orderby]) ? count($b[$orderby]) : $b[$orderby]); //Determine sort order, case insensitive, natural order

            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        usort($data, 'usort_reorder');

        /**
         * Pagination.
         */
        $current_page = $this->get_pagenum();
        $total_items = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                      //calculate the total number of items
            'per_page' => $per_page,                         //determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //calculate the total number of pages
        ]);
    }

	/**
	 * Display the list table.
	 *
	 * @access public
	 * @return void
	 */
	public function display() {

		$this->views();

        ?>
        <form class="search-form wp-clearfix" method="get">
        <?php $this->search_box(esc_html__('Search Roles', 'capability-manager-enhanced'), 'roles'); ?>
        </form>
        <?php

		parent::display();
	}
}