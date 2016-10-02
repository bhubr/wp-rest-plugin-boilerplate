<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

/**
 * Sample test case.
 */
class Test_Base_Model extends WP_UnitTestCase {

    function setUp() {
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance();
        $this->rpb->register_plugin('wprbp-test-foo', MODELS_DIR . '/foo');
        // $this->rpb->register_plugin('wprbp-test-dummy', MODELS_DIR . '/dummy');
        do_action('init');
    }

    /**
     * Check that registered post types exist
     */
    function test_registered_types() {
        $builtin_types = get_post_types(['_builtin' => true]);
        $all_types = array_merge(
            $builtin_types,
            [
              // 'dumbass'       => 'dumbass',
              // 'dumbmany'      => 'dumbmany',
              // 'dumbmany2many' => 'dumbmany2many',
              // 'dummy'         => 'dummy',
              'foo'           => 'foo',
            ]
        );
        $this->assertEquals( $all_types, get_post_types() );
    }

    /**
     * Check that registered taxonomies exist
     */
    function test_registered_taxonomies() {
        // Replace this with some actual testing code.
        $builtin_taxonomies = get_taxonomies(['_builtin' => true]);
        $all_taxos = array_merge(
            $builtin_taxonomies,
            [
                'foo_cat'      => 'foo_cat',
                'foo_tag'      => 'foo_tag',
                // 'dummyterm'    => 'dummyterm',
                // 'termany'      => 'termany',
                // 'termany2many' => 'termany2many',
                // 'termone'      => 'termone',
            ]
        );
        $this->assertEquals( $all_taxos, get_taxonomies() );
    }

}
