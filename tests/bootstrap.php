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

define('SRC_DIR', dirname( dirname( __FILE__ ) ) . '/src/REST');
// define('VENDOR_DIR', dirname( dirname( __FILE__ ) ) . '/src/vendor');
define('MODELS_DIR', dirname( dirname( __FILE__ ) ) . '/tests-models');

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // require VENDOR_DIR . '/Inflect.php';
    require SRC_DIR . '/Plugin_Boilerplate.php';
 //    require SRC_DIR . '/Model/Exception.php';
	// require SRC_DIR . '/Model/Base.php';
 //    require SRC_DIR . '/Model/Post.php';
 //    require SRC_DIR . '/Model/Term.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
