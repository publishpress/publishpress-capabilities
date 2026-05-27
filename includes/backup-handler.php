<?php
/*
 * PublishPress Capabilities [Free]
 *
 * Process update operations from Backup screen
 *
 */

class Capsman_BackupHandler
{
	var $cm;

	function __construct( $manager_obj ) {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_backup'))
			wp_die( esc_html__( 'You do not have permission to restore backup.', 'capability-manager-enhanced' ) );

		$this->cm = $manager_obj;
	}

	/**
	 * Processes backups and restores.
	 *
	 * @return void
	 */
	function processBackupTool ()
	{
		global $wpdb;

        if (isset($_POST['save_backup'])) {
			check_admin_referer('pp-capabilities-backup');

			$wp_roles = $wpdb->prefix . 'user_roles';
			$cm_roles = $this->cm->ID . '_backup';
			$cm_roles_initial = $this->cm->ID . '_backup_initial';

            $backup_sections = pp_capabilities_backup_sections();

			if ( ! get_option( $cm_roles_initial ) ) {
				if ( $current_backup = get_option( $cm_roles ) ) {
					update_option( $cm_roles_initial, $current_backup, false );

					if ( $initial_datestamp = get_option( $this->cm->ID . '_backup_datestamp' ) ) {
						update_option($this->cm->ID . '_backup_initial_datestamp', $initial_datestamp, false );
					}
				}
			}

            $active_backup = ['Roles and Capabilities'];

            //role backup
			$roles = get_option($wp_roles);
			update_option($cm_roles, $roles, false);

            //other backup
            foreach($backup_sections as $backup_section){
                $section_options = $backup_section['options'];
                if(is_array($section_options) && !empty($section_options)){
                    foreach($section_options as $section_option){
                        $active_backup[] = $backup_section['label'];
                        $current_option = get_option($section_option);
                        update_option($section_option.'_backup', $current_option, false);
                    }
                }
            }

            $active_backup = array_unique($active_backup);

            //update last backup
            update_option($this->cm->ID . '_last_backup', implode(', ', $active_backup));

            //backup datestamp and response
			update_option($this->cm->ID . '_backup_datestamp', current_time( 'timestamp' ), false );
			ak_admin_notify(__('New backup saved.', 'capability-manager-enhanced'));

        }

        if (isset($_POST['restore_backup']) && !empty($_POST['select_restore'])) {
            check_admin_referer('pp-capabilities-backup');

            $wp_roles = $wpdb->prefix . 'user_roles';
            $cm_roles = $this->cm->ID . '_backup';
            $cm_roles_initial = $this->cm->ID . '_backup_initial';

            $backup_sections = pp_capabilities_backup_sections();

            switch ($_POST['select_restore']) {
				case 'restore_initial':
					if ($roles = get_option($cm_roles_initial)) {
						update_option($wp_roles, $roles);
						ak_admin_notify(__('Roles and Capabilities restored from initial backup.', 'capability-manager-enhanced'));
					} else {
						ak_admin_error(__('Restore failed. No backup found.', 'capability-manager-enhanced'));
					}
					break;

				case 'restore':
					if ($roles = get_option($cm_roles)) {

                        $restored_backup = ['Roles and Capabilities'];

                        //restore role backup
						update_option($wp_roles, $roles);

                        //restore other backup
                        foreach($backup_sections as $backup_section){
                            $section_options = $backup_section['options'];
                            if(is_array($section_options) && !empty($section_options)){
                                foreach($section_options as $section_option){
                                    $backup_option = get_option($section_option.'_backup');
                                    if ($backup_option) {
                                        $restored_backup[] = $backup_section['label'];
                                        update_option($section_option, $backup_option);
                                    }
                                }
                            }
                        }

                        $restored_backup = array_unique($restored_backup);
						ak_admin_notify(sprintf(__('%s restored from last backup.', 'capability-manager-enhanced'), implode(', ', $restored_backup)));
					} else {
						ak_admin_error(__('Restore failed. No backup found.', 'capability-manager-enhanced'));
					}
					break;

				default:
                    if ($roles = get_option(sanitize_key($_POST['select_restore']))) {
						update_option($wp_roles, $roles);
						ak_admin_notify(__('Roles and Capabilities restored from selected auto-backup.', 'capability-manager-enhanced'));
					} else {
						ak_admin_error(__('Restore failed. No backup found.', 'capability-manager-enhanced'));
					}
			}
		}

        if (isset($_POST['import_backup'])) {

            check_admin_referer('pp-capabilities-backup');

            if (empty($_FILES['import_file']['tmp_name']) || empty($_FILES['import_file']['name'])) {
                ak_admin_error(__( 'Please upload a file to import', 'capability-manager-enhanced'));
                return;
            }

            if (pathinfo(sanitize_text_field($_FILES['import_file']['name']), PATHINFO_EXTENSION) !== 'json') {
                ak_admin_error(__( 'Please upload a valid .json file', 'capability-manager-enhanced'));
                return;
            }

            // Make sure WordPress upload support is loaded.
            if ( ! function_exists( 'wp_handle_upload' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }

            // Setup internal vars.
            $overrides   = array( 'test_form' => false, 'test_type' => false, 'mimes' => array('json' => 'application/json') );
            $file         = wp_handle_upload( $_FILES['import_file'], $overrides );

            // Make sure we have an uploaded file.
            if (isset($file['error'])) {
                ak_admin_error($file['error']);
                return;
            }

            if ( ! file_exists( $file['file'] ) ) {
                ak_admin_error(__( 'Error importing settings! Please try again.', 'capability-manager-enhanced'));
                return;
            }

            // Get the upload data.
            $raw  = file_get_contents( $file['file'] );
            $data = json_decode($raw, true);

            // Remove the uploaded file.
            wp_delete_file( $file['file'] );

            // Data checks.
            if ( 'array' != gettype( $data ) ) {
                ak_admin_error(__( 'Error importing settings! Please check that you uploaded a valid json file.', 'capability-manager-enhanced'));
                return;
            }

            $backup_sections = pp_capabilities_backup_sections();
            $allowed_import_options = $this->get_allowed_import_options($backup_sections, $wpdb->prefix . 'user_roles');
            $restored_backup = [];
            $skipped_options = array_keys(array_diff_key($data, $allowed_import_options));

            $data = $this->filter_import_data_by_allowlist($data, $allowed_import_options);

            foreach ( $data as $option_key => $option_value ) {
                if (!isset($allowed_import_options[$option_key])) {
                    $skipped_options[] = $option_key;
                    continue;
                }

                $option_section = $allowed_import_options[$option_key]['section'];
                $target_option_key = $allowed_import_options[$option_key]['target'];

                if($option_key === 'user_roles'){
                    if (!$this->is_valid_import_role_data($option_value)) {
                        $skipped_options[] = $option_key;
                        continue;
                    }

                    $restored_backup[] = 'Roles and Capabilities';
                    $section_data = $this->santize_import_role($option_value);
                    update_option($target_option_key, $section_data);
                }else{
                    if (!$this->is_valid_import_option_value($target_option_key, $option_value, $option_section)) {
                        $skipped_options[] = $option_key;
                        continue;
                    }

                    $restored_backup[] = $allowed_import_options[$option_key]['label'];
                    $section_data = $this->santize_import_data($option_value);
                    update_option($target_option_key, $section_data);
                }
			}

            $restored_backup = array_unique($restored_backup);

            if (!empty($skipped_options)) {
                ak_admin_error(sprintf(__('Skipped %d invalid or unsupported import entries.', 'capability-manager-enhanced'), count(array_unique($skipped_options))));
            }

            if (!empty($restored_backup)) {
                ak_admin_notify(sprintf(__('%s successfully imported from uploaded data.', 'capability-manager-enhanced'), implode(', ', $restored_backup)));
            } else {
                ak_admin_error(__('No valid backup data found to import.', 'capability-manager-enhanced'));
            }

		}
	}

	/**
	 * Sanitize role data before import.
	 *
	 * @return array
	 */
    function get_import_option_section($option_key, $backup_sections)
    {
        $option_section = '';

        foreach($backup_sections as $backup_section){
            $section_options = $backup_section['options'];
            if(is_array($section_options) && in_array($option_key, $section_options)){
                $option_section= $backup_section['label'];
            }
        }

        return $option_section;
    }

	/**
	 * Build import allowlist map from backup sections and role option.
	 *
	 * @param array  $backup_sections Backup sections list.
	 * @param string $user_roles_key  Prefixed user roles option key.
	 *
	 * @return array
	 */
    function get_allowed_import_options($backup_sections, $user_roles_key)
    {
        $allowed_options = [
            'user_roles' => [
                'target'  => $user_roles_key,
                'label'   => 'Roles and Capabilities',
                'section' => 'roles',
            ],
        ];

        foreach ($backup_sections as $section_key => $backup_section) {
            if (empty($backup_section['options']) || !is_array($backup_section['options'])) {
                continue;
            }

            $section_label = !empty($backup_section['label']) ? $backup_section['label'] : '';

            foreach ($backup_section['options'] as $option_key) {
                if (!is_string($option_key) || '' === $option_key) {
                    continue;
                }

                $allowed_options[$option_key] = [
                    'target'  => $option_key,
                    'label'   => $section_label,
                    'section' => $section_key,
                ];
            }
        }

        return $allowed_options;
    }

	/**
	 * Drop unsupported top-level import keys.
	 *
	 * @param array $data                  Raw import data.
	 * @param array $allowed_import_options Allowlist map.
	 *
	 * @return array
	 */
    function filter_import_data_by_allowlist($data, $allowed_import_options)
    {
        if (!is_array($data) || empty($data) || !is_array($allowed_import_options)) {
            return [];
        }

        return array_intersect_key($data, $allowed_import_options);
    }

	/**
	 * Validate expected role structure before import.
	 *
	 * @param mixed $role_data Role payload.
	 *
	 * @return bool
	 */
    function is_valid_import_role_data($role_data)
    {
        if (!is_array($role_data)) {
            return false;
        }

        foreach ($role_data as $role_entry) {
            if (!is_array($role_entry)) {
                return false;
            }

            if (!array_key_exists('name', $role_entry) || !is_scalar($role_entry['name'])) {
                return false;
            }

            if (!isset($role_entry['capabilities']) || !is_array($role_entry['capabilities'])) {
                return false;
            }

            foreach ($role_entry['capabilities'] as $capability_value) {
                if (!is_scalar($capability_value) && null !== $capability_value) {
                    return false;
                }
            }
        }

        return true;
    }

	/**
	 * Validate non-role option values before import.
	 *
	 * @param string $option_key     Option key.
	 * @param mixed  $option_value   Option value.
	 * @param string $option_section Section key.
	 *
	 * @return bool
	 */
    function is_valid_import_option_value($option_key, $option_value, $option_section)
    {
        if (!$this->is_valid_import_value_shape($option_value)) {
            return false;
        }

        if ('capsman_settings_backup' !== $option_section && !is_array($option_value)) {
            return false;
        }

        $current_value = get_option($option_key, null);
        if (null !== $current_value && !$this->is_compatible_import_shape($option_value, $current_value)) {
            return false;
        }

        return true;
    }

	/**
	 * Validate scalar/array-only shape and recursion depth.
	 *
	 * @param mixed $value Value to validate.
	 * @param int   $depth Current depth.
	 *
	 * @return bool
	 */
    function is_valid_import_value_shape($value, $depth = 0)
    {
        if ($depth > 6) {
            return false;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->is_valid_import_value_shape($item, $depth + 1)) {
                    return false;
                }
            }

            return true;
        }

        return is_scalar($value) || null === $value;
    }

	/**
	 * Check imported value shape compatibility with existing option shape.
	 *
	 * @param mixed $import_value  Imported value.
	 * @param mixed $current_value Existing option value.
	 * @param int   $depth         Current depth.
	 *
	 * @return bool
	 */
    function is_compatible_import_shape($import_value, $current_value, $depth = 0)
    {
        if ($depth > 6) {
            return false;
        }

        if (is_array($current_value)) {
            if (!is_array($import_value)) {
                return false;
            }

            if (!empty($current_value)) {
                $current_is_list = $this->is_numeric_array($current_value);
                $import_is_list = $this->is_numeric_array($import_value);

                if ($current_is_list !== $import_is_list) {
                    return false;
                }

                foreach ($import_value as $item_key => $item_value) {
                    if (array_key_exists($item_key, $current_value)
                        && !$this->is_compatible_import_shape($item_value, $current_value[$item_key], $depth + 1)
                    ) {
                        return false;
                    }
                }
            }

            return true;
        }

        return !is_array($import_value);
    }

	/**
	 * Determine if an array is numerically indexed.
	 *
	 * @param array $value Candidate array.
	 *
	 * @return bool
	 */
    function is_numeric_array($value)
    {
        if (!is_array($value)) {
            return false;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

	/**
	 * Sanitize role data before import.
	 *
	 * @return array
	 */
    function santize_import_role($role){

        $sanitized_role = [];

        if (!is_array($role)) {
            return $sanitized_role;
        }

        foreach($role as $role_key => $role_data){
            if (!is_array($role_data) || !isset($role_data['name']) || !isset($role_data['capabilities']) || !is_array($role_data['capabilities'])) {
                continue;
            }

            $role_key           = sanitize_key($role_key);
            $role_name          = sanitize_text_field($role_data['name']);
            $capabilities       = $role_data['capabilities'];
            $role_capabilities  = array_combine(
                                    array_map('sanitize_key', array_keys($capabilities)),
                                    array_map('sanitize_text_field', array_values($capabilities))
                                );

            //return sanitized data
            $sanitized_role[$role_key] = ['name' => $role_name, 'capabilities' => $role_capabilities];
        }

        return $sanitized_role;
    }

	/**
	 * Sanitize other data before import.
	 *
	 * @return mixed
	 */
    function santize_import_data($data){

        $sanitized_data = map_deep($data, 'sanitize_text_field');

        return $sanitized_data;
    }

	/**
	 * Resets roles to WordPress defaults.
	 *
	 * @return void
	 */
	function backupToolReset ()
	{
        global $current_user;

		check_admin_referer('capsman-reset-defaults');

		require_once(ABSPATH . 'wp-admin/includes/schema.php');

		if ( ! function_exists('populate_roles') ) {
			ak_admin_error(__('Needed function to create default roles not found!', 'capability-manager-enhanced'));
			return;
		}

		$roles = array_keys( ak_get_roles(true) );

		foreach ( $roles as $role) {
			remove_role($role);
		}

		populate_roles();

        $pp_capabilities = apply_filters('cme_publishpress_capabilities_capabilities', []);
        $role = get_role('administrator');
        foreach ($pp_capabilities as $cap) {
            $role->add_cap($cap);
            $current_user->allcaps[$cap] = true;
        }

		$msg = esc_html__('Roles and Capabilities reset to WordPress defaults', 'capability-manager-enhanced');

		if ( function_exists( 'pp_populate_roles' ) ) {
			pp_populate_roles();
		} else {
			// force PP to repopulate roles
			$pp_ver = get_option( 'pp_c_version', true );
			if ( $pp_ver && is_array($pp_ver) ) {
				$pp_ver['version'] = ( preg_match( "/dev|alpha|beta|rc/i", $pp_ver['version'] ) ) ? '0.1-beta' : 0.1;
			} else {
				$pp_ver = array( 'version' => '0.1', 'db_version' => '1.0' );
			}

			update_option( 'pp_c_version', $pp_ver );
			delete_option( 'ppperm_added_role_caps_10beta' );
		}

		ak_admin_notify($msg);
	}
}
