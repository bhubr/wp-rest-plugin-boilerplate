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
        $this->rpb->register_plugin('wprbp-test-suite', __DIR__);
        do_action('init');
        // $this->rpb->create_assoc_with_meta_table();
        // bhubr\Base_Model::register_type('pouet', 'Pouet', ['fields' => ['foo', 'bar']]);
        // bhubr\Base_Model::register_type('pouet', 'Foo', ['fields' => ['foo', 'bar']]);
        // bhubr\Base_Model::register_taxonomy('pouetax', 'Pouetaxonomy', 'pouet', ['baz', 'boo']);
    }

    /**
     * A single example test.
     */
    function test_registered_types() {
        // Replace this with some actual testing code.
        $builtin_types = get_post_types(['_builtin' => true]);
        $all_types = array_merge(
            $builtin_types,
            [
              'dumbass'       => 'dumbass',
              'dumbmany'      => 'dumbmany',
              'dumbmany2many' => 'dumbmany2many',
              'dummy'         => 'dummy',
              'foo'           => 'foo',
            ]
        );
        $this->assertEquals( $all_types, get_post_types() );
    }

    /**
     * A single example test.
     */
    function test_registered_taxonomies() {
        // Replace this with some actual testing code.
        $builtin_taxonomies = get_taxonomies(['_builtin' => true]);
        $all_taxos = array_merge(
            $builtin_taxonomies,
            [
                'dummyterm'    => 'dummyterm',
                'foo_cat'      => 'foo_cat',
                'foo_tag'      => 'foo_tag',
                'termany'      => 'termany',
                'termany2many' => 'termany2many',
                'termone'      => 'termone',

            ]
        );
        $this->assertEquals( $all_taxos, get_taxonomies() );
    }

}
