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
        $plugin_descriptor = require 'plugin_descriptor.php';
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance(realpath(__DIR__ . '/..'));
        $this->rpb->register_plugin('wprbp-test-suite', $plugin_descriptor);
        do_action('init');
        $this->rpb->create_term_meta_tables('wprbp-test-suite');
    }

    function tearDown() {
        // $this->rpb->delete_term_meta_tables('wprbp-test-suite');
    }

    /**
     * @expectedException     bhubr\Model_Exception
     */
    public function test_create_bad_type()
    {
        $model = bhubr\Post_Model::create('fzoo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
    }

    
    /**
     * Test creating and reading a model
     */
    function test_create_and_read() {
        $model = bhubr\Post_Model::create('foo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
        $expected_model = [
            'id' => 3, 'name' => 'Pouet 1', 'slug' => 'pouet-1',
            'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap',
            'foo_cat' => null, 'foo_tags' => []
        ];
        $this->assertEquals($expected_model, $model);
        $read_model = bhubr\Post_Model::read('foo', 3);
        $this->assertEquals($expected_model, $read_model);
    }

    function test_extract_payload_with_terms() {
        $payload = ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap', 'foo_cat' => 1, 'foo_tags' => [2, 3]];
        $fields = bhubr\Post_Model::extract_payload_taxonomies('foo', $payload);
        $expected = [
            '__meta__' => ['baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap'],
            '__terms__' => [
                'foo_cat' => 1,
                'foo_tag' => [2, 3]
            ],
            'post_title' => 'Pouet 1'
        ];
        $this->assertEquals($expected, $fields);
    }

    /**
     * Test creating and reading a model with terms
     */
    function test_create_read_update_delete_with_terms() {
        $cat1 = bhubr\Term_Model::create('foo_cat', ['name' => 'Foo cat 1', 'a' => 'A', 'b' => 'B']);
        $cat2 = bhubr\Term_Model::create('foo_cat', ['name' => 'Foo cat 2', 'a' => 'A', 'b' => 'B']);
        $tag1 = bhubr\Term_Model::create('foo_tag', ['name' => 'Foo tag 1', 'a' => 'A', 'b' => 'B']);
        $tag2 = bhubr\Term_Model::create('foo_tag', ['name' => 'Foo tag 2', 'a' => 'A', 'b' => 'B']);
        $tag3 = bhubr\Term_Model::create('foo_tag', ['name' => 'Foo tag 3', 'a' => 'A', 'b' => 'B']);
        $model = bhubr\Post_Model::create('foo', ['name' => 'Pouet 2', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag1['id'], $tag2['id']]]);

        $expected_model = [
            'id' => 4, 'name' => 'Pouet 2', 'slug' => 'pouet-2', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag1['id'], $tag2['id']]
        ];
        $this->assertEquals($expected_model, $model);
        $read_model = bhubr\Post_Model::read('foo', $model['id']);
        $this->assertEquals($expected_model, $read_model);


        $expected_tags2 = [$tag1['id'], $tag3['id']];
        $updated_model = bhubr\Post_Model::update('foo', 4, ['name' => 'Pouet 2 updated', 'foo_cat' => $cat2['id'], 'foo_tags' => $expected_tags2]);
        $expected_model2 = [
            'id' => 4, 'name' => 'Pouet 2 updated', 'slug' => 'pouet-2', 'foo_cat' => $cat2['id'], 'foo_tags' => $expected_tags2
        ];
        $this->assertEquals($expected_tags2, $updated_model['foo_tags']);
        $this->assertEquals($expected_model2, $updated_model);
    }

}
