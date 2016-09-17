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
        bhubr\Base_Model::register_type('pouet', 'Pouet', ['fields' => ['foo', 'bar']]);
        bhubr\Base_Model::register_taxonomy('pouetax', 'Pouetaxonomy', 'pouet', ['baz', 'boo']);
    }

    /**
     * A single example test.
     */
    function test_registered_types() {
        // Replace this with some actual testing code.
        $builtin_types = get_post_types(['_builtin' => true]);
        $all_types = array_merge($builtin_types, ['pouet' => 'pouet']);
        $this->assertEquals( $all_types, get_post_types() );
    }

    /**
     * A single example test.
     */
    function test_registered_taxonomies() {
        // Replace this with some actual testing code.
        $builtin_taxonomies = get_taxonomies(['_builtin' => true]);
        $all_taxos = array_merge($builtin_taxonomies, ['pouetax' => 'pouetax']);
        $this->assertEquals( $all_taxos, get_taxonomies() );
    }

}
