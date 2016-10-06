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
define('WPRDB_DEBUG', true);

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require SRC_DIR . '/Plugin_Boilerplate.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
require __DIR__ . '/WPRPB_UnitTestCase.php';

// Some helpers
if ( ! function_exists( 'unregister_post_type' ) ) :
function unregister_post_type( $post_type ) {
    global $wp_post_types;
    if ( isset( $wp_post_types[ $post_type ] ) ) {
        unset( $wp_post_types[ $post_type ] );
        return true;
    }
    return false;
}
endif;

if ( ! function_exists( 'unregister_taxonomy' ) ) :
function unregister_taxonomy( $taxonomy ) {
    global $wp_taxonomies;
    if ( isset( $wp_taxonomies[ $taxonomy ] ) ) {
        unset( $wp_taxonomies[ $taxonomy ] );
        return true;
    }
    return false;
}
endif;

if ( ! function_exists( 'reset_singleton_instance' ) ) :
    function reset_singleton_instance( $class_name ) {
        $obj         = $class_name::get_instance();
        $refObject   = new ReflectionObject( $obj );
        $refProperty = $refObject->getProperty( '_instance' );
        $refProperty->setAccessible( true );
        $refProperty->setValue(null);
    }
endif;