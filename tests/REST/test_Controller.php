<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

use bhubr\REST\Payload\Formatter;

/**
 * Sample test case.
 */
class Test_Controller extends WP_UnitTestCase {
    private $rbp;

    /**
     * Instantiate plugin base, and register a dummy plugin with one custom post type
     * and two custom taxonomies for this type
     */
    function setUp() {
        global $wp_rest_server;
        $this->rpb = bhubr\REST\Plugin_Boilerplate::get_instance();
        $this->rpb->register_plugin('dummy-plugin', RESOURCES_DIR, [
            'models_dir' => 'models/foo', 'models_namespace' => 'foo\\'
        ]);
        $this->server = $wp_rest_server = new WP_REST_Server;
        do_action('init');
        do_action('rest_api_init');
    }

    /**
     * Test WP_REST_Server routes
     */
    function test_wp_rest_server_routes() {
        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();
        var_dump(array_keys($routes));
    }

}
