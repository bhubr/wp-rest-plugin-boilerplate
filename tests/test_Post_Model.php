<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

/**
 * Sample test case.
 */
class Test_Post_Model extends WP_UnitTestCase {

    protected $rpb;

    function setUp() {
        // bhubr\Base_Model::register_type('pouet', 'Pouet', ['fields' => ['foo', 'bar']]);
        // bhubr\Base_Model::register_taxonomy('pouetax', 'Pouetaxonomy', 'pouet', ['baz', 'boo']);
        $plugin_descriptor = require 'plugin_descriptor.php';
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance(realpath(__DIR__ . '/..'));
        $this->rpb->register_plugin('wprbp-test-suite', $plugin_descriptor);
        do_action('init');
        $this->rpb->create_term_meta_tables('wprbp-test-suite');
    }

    function tearDown() {
        $this->rpb->delete_term_meta_tables('wprbp-test-suite');
    }

    /**
     * @expectedException     bhubr\Model_Exception
     */
    public function test_create_bad_type()
    {
        $model = bhubr\Post_Model::create('fzoo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
    }

    

    function test_create_and_read() {
        // Replace this with some actual testing code.
        
        $model = bhubr\Post_Model::create('foo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
        $expected_model = [
            'id' => 3, 'name' => 'Pouet 1', 'slug' => 'pouet-1',
            'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap'
        ];
        $this->assertEquals($expected_model, $model);
        $read_model = bhubr\Post_Model::read('foo', 3);
        $this->assertEquals($expected_model, $read_model);
    }


}
