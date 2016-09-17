<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Sandbox_Plugin
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

define('SRC_DIR', dirname( dirname( __FILE__ ) ) . '/src/bhubr');
/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require SRC_DIR . '/vendor/Inflect.php';
    require SRC_DIR . '/REST_Plugin_Boilerplate.php';
    require SRC_DIR . '/Model_Exception.php';
	require SRC_DIR . '/Base_Model.php';
    require SRC_DIR . '/Post_Model.php';
    require SRC_DIR . '/Term_Model.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
