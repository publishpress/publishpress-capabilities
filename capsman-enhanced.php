<?php
/**
 * Plugin Name: PublishPress Capabilities
 * Plugin URI: https://publishpress.com/capability-manager/
 * Description: PublishPress Capabilities is the access control plugin for WordPress. You can manage all your WordPress user roles, from Administrators to Subscribers.
 * Version: 2.21.0
 * Author: PublishPress
 * Author URI: https://publishpress.com/
 * Text Domain: capability-manager-enhanced
 * Domain Path: /languages/
 * Requires at least: 5.5
 * Requires PHP: 7.2.5
 * License: GPLv3
 *
 * Copyright (c) 2024 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Capability Manager
 * Author: Jordi Canals
 * Copyright (c) 2009, 2010 Jordi Canals
 * ------------------------------------------------------------------------------
 *
 * @package 	capability-manager-enhanced
 * @author		PublishPress
 * @copyright   Copyright (C) 2009, 2010 Jordi Canals; modifications Copyright (C) 2024 PublishPress
 * @license		GNU General Public License version 3
 * @link		https://publishpress.com/
 */

global $wp_version;

$min_php_version = '7.2.5';
$min_wp_version  = '5.5';

$invalid_php_version = version_compare(phpversion(), $min_php_version, '<');
$invalid_wp_version = version_compare($wp_version, $min_wp_version, '<');

if ($invalid_php_version || $invalid_wp_version) {
	return;
}

if (!defined('PP_CAPABILITIES_LIB_VENDOR_PATH')) {
	define('PP_CAPABILITIES_LIB_VENDOR_PATH', __DIR__ . '/lib/vendor');
}

$instanceProtectionIncPath = PP_CAPABILITIES_LIB_VENDOR_PATH . '/publishpress/instance-protection/include.php';
if (is_file($instanceProtectionIncPath) && is_readable($instanceProtectionIncPath)) {
	require_once $instanceProtectionIncPath;
}

if (class_exists('PublishPressInstanceProtection\\Config')) {
	$pluginCheckerConfig = new PublishPressInstanceProtection\Config();
	$pluginCheckerConfig->pluginSlug    = 'capsman-enhanced';
	$pluginCheckerConfig->pluginFolder  = 'capability-manager-enhanced';
	$pluginCheckerConfig->pluginName    = 'PublishPress Capabilities';

	$pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
}

$autoloadFilePath = PP_CAPABILITIES_LIB_VENDOR_PATH . '/autoload.php';
if (
	!class_exists('ComposerAutoloaderInitPublishPressCapabilities')
	&& is_file($autoloadFilePath)
	&& is_readable($autoloadFilePath)
) {
	require_once $autoloadFilePath;
}

add_action('plugins_loaded', function () {

	if (!defined('CAPSMAN_VERSION')) {
		define('CAPSMAN_VERSION', '2.21.0');
		define('CAPSMAN_ENH_VERSION', CAPSMAN_VERSION);
		define('PUBLISHPRESS_CAPS_VERSION', CAPSMAN_VERSION);
	}

	foreach (get_option('active_plugins') as $plugin_file) {
		if (false !== strpos($plugin_file, 'capsman.php')) {
			add_action('admin_notices', function () {
				echo '<div id="message" class="error fade" style="color: black">' . sprintf(esc_html__('%1s Error: %2s  PublishPress Capabilities cannot function because another copy of the plugin is active.', 'capability-manager-enhanced'), '<strong>', '</strong>') . '</div>';
			});
			return;
		}
	}

	$pro_active = false;

	foreach ((array)get_option('active_plugins') as $plugin_file) {
		if (false !== strpos($plugin_file, 'capabilities-pro.php')) {
			$pro_active = true;
			break;
		}
	}

	if (!$pro_active && is_multisite()) {
		foreach (array_keys((array)get_site_option('active_sitewide_plugins')) as $plugin_file) {
			if (false !== strpos($plugin_file, 'capabilities-pro.php')) {
				$pro_active = true;
				break;
			}
		}
	}

	if ($pro_active) {
		add_filter(
			'plugin_row_meta',
			function ($links, $file) {
				if ($file == plugin_basename(__FILE__)) {
					$links[] = '<strong>' . esc_html__('This plugin can be deleted.', 'capability-manager-enhanced') . '</strong>';
				}

				return $links;
			},
			10,
			2
		);
	}

	if (defined('CME_FILE') || $pro_active) {
		return;
	}

	define('CME_FILE', __FILE__);
	define('PUBLISHPRESS_CAPS_ABSPATH', __DIR__);

	require_once(dirname(__FILE__) . '/includes/functions.php');

	// ============================================ START PROCEDURE ==========


	global $pagenow;

	// redirect legacy URLs
	if (!empty($_REQUEST['page'])) {
		foreach (['capsman' => 'pp-capabilities', 'capsman-tool' => 'pp-capabilities-backup'] as $find => $replace) {
			if (isset($_REQUEST['page']) && ($find == $_REQUEST['page']) && !empty($_SERVER['REQUEST_URI'])) {
				$location = str_replace("page=$find", "page=$replace", esc_url_raw($_SERVER['REQUEST_URI']));
				header("Location: $location", true);
				exit;
			}
		}
	}

	if (is_admin()) {
		// @todo: refactor
		require_once (dirname(__FILE__) . '/includes/functions-admin.php');

		global $capsman_admin;
		require_once (dirname(__FILE__) . '/includes/admin-load.php');
		$capsman_admin = new PP_Capabilities_Admin_UI();
	}

	if (is_admin() && !defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
		require_once(__DIR__ . '/includes-core/CoreAdmin.php');
		new \PublishPress\Capabilities\CoreAdmin();
	}

	add_action('init', '_cme_init');
	add_action( 'plugins_loaded', '_cme_act_pp_active', 1);

	add_action('init', '_cme_cap_helper', 49);  // Press Permit Cap Helper, registered at 50, will leave caps which we've already defined
	//add_action( 'wp_loaded', '_cme_cap_helper_late_init', 99 );	// now instead adding registered_post_type, registered_taxonomy action handlers for latecomers
	// @todo: do this in PP Core also

	if (is_multisite())
		require_once (dirname(__FILE__) . '/includes/network.php');

	// Check if Permissions is installed
	if (!cme_is_plugin_active('press-permit-core.php') && !cme_is_plugin_active('presspermit-pro.php')) {
		define('CAPSMAN_PERMISSIONS_INSTALLED', false);
	} else {
		define('CAPSMAN_PERMISSIONS_INSTALLED', true);
	}
	do_action('publishpress_capabilities_loaded');
}, -10);

register_activation_hook(
    __FILE__,
    function () {
        update_option('pp_capabilities_activated', true);
    }
);