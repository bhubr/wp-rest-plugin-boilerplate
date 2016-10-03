<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

use bhubr\REST\Payload\Formatter;
use bhubr\REST\Controller;
use bhubr\REST\Utils\Tracer;

/**
 * Sample test case.
 */
class Test_Controller extends WP_UnitTestCase {
    private $rbp;
    private $server;
    private $controller;

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
        $this->controller = new Controller;
        try {
            do_action('init');
            do_action('rest_api_init');
        } catch(Exception $e) {
            Tracer::show();
        }
    }

    function tearDown() {
        reset_singleton_instance('bhubr\REST\Plugin_Boilerplate');
        reset_singleton_instance('bhubr\REST\Model\Registry');

        // SUPER IMPORTANT sinon l'action d'enregistrement des types
        // est répétée plusieurs fois !!
        remove_action( 'init', [$this->rpb, 'register_types'] );
    }

    /**
     * Test WP_REST_Server routes
     */
    function test_wp_rest_server_routes() {
        try {
            do_action('init');
            do_action('rest_api_init');
        } catch(Exception $e) {
            Tracer::show();
        }

        global $wp_rest_server;
        $routes = $wp_rest_server->get_routes();
        $keys = array_keys($routes);
        
        $expected = [
            '/',
            '/oembed/1.0',
            '/oembed/1.0/embed',
            '/bhubr/v1',
            '/bhubr/v1/foos',
            '/bhubr/v1/foos/(?P<id>[\d]+)',
            '/bhubr/v1/foos/(?P<id>[\d]+)/categories',
            '/bhubr/v1/foos/(?P<id>[\d]+)/tags',
            // '/bhubr/v1/foos/schema',
            '/bhubr/v1/foo_cats',
            '/bhubr/v1/foo_cats/(?P<id>[\d]+)',
            // '/bhubr/v1/foo_cats/schema',
            '/bhubr/v1/foo_tags',
            '/bhubr/v1/foo_tags/(?P<id>[\d]+)',
            // '/bhubr/v1/foo_tags/schema',
        ];
        sort($expected);
        sort($keys);
        $this->assertEquals($expected, $keys);
    }

    /**
     * Test parse route GET ALL foos
     */
    public function test_parse_route_get_all() {

        $request = new WP_REST_Request( 'GET', '/bhubr/v1/foos');
        $parsed = $this->controller->parse_route( $request );
        $expected = [
        ];
        $this->assertEquals( '/bhubr/v1/foos', $parsed );
    }



}
