<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WP_REST_Plugin_Boilerplate
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

define('SRC_DIR', dirname( dirname( __FILE__ ) ) . '/src/REST');
define('MODELS_DIR', dirname( dirname( __FILE__ ) ) . '/tests-resources/models');
define('RESOURCES_DIR', dirname( dirname( __FILE__ ) ) . '/tests-resources');

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require SRC_DIR . '/Plugin_Boilerplate.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
