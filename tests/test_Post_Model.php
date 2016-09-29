<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

require 'inc/Dummy.php';
require 'inc/Dumbass.php';
require 'inc/Dumbmany.php';

/**
 * Sample test case.
 */
class Test_Post_Model extends WP_UnitTestCase {

    protected $rpb;

    function setUp() {
        $plugin_descriptor = require 'plugin_descriptor.php';
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance(realpath(__DIR__ . '/..'));
        $this->rpb->register_plugin('wprbp-test-suite', $plugin_descriptor);
        bhubr\Base_Model::register_type('dummy', 'Dummy', ['fields' => ['type', 'status', 'dummy_int', 'dummy_str']]);
        bhubr\Base_Model::register_type('dumbass', 'Dumbass', ['fields' => ['dumb_str']]);
        bhubr\Base_Model::register_type('dumbmany', 'Dumbmany', ['fields' => ['dumb_str']]);
        do_action('init');
        // $this->rpb->create_term_meta_tables('wprbp-test-suite');
    }

    function tearDown() {
        // $this->rpb->delete_term_meta_tables('wprbp-test-suite');
    }

    /**
     * @expectedException     bhubr\Model_Exception
     */
    public function test_create_bad_type()
    {
        $model = bhubr\Post_Model::_create('fzoo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
    }

    
    /**
     * Test creating and reading a model
     */
    function test_create_and_read() {
        $model = bhubr\Post_Model::_create('foo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
        $expected_model = [
            'id' => 3, 'name' => 'Pouet 1', 'slug' => 'pouet-1',
            'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap',
            'foo_cat' => null, 'foo_tags' => []
        ];
        $this->assertEquals($expected_model, $model);
        $read_model = bhubr\Post_Model::_read('foo', 3);
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
        $cat1 = bhubr\Term_Model::_create('foo_cat', ['name' => 'Foo cat 1', 'a' => 'A', 'b' => 'B']);
        $cat2 = bhubr\Term_Model::_create('foo_cat', ['name' => 'Foo cat 2', 'a' => 'A', 'b' => 'B']);
        $tag1 = bhubr\Term_Model::_create('foo_tag', ['name' => 'Foo tag 1', 'a' => 'A', 'b' => 'B']);
        $tag2 = bhubr\Term_Model::_create('foo_tag', ['name' => 'Foo tag 2', 'a' => 'A', 'b' => 'B']);
        $tag3 = bhubr\Term_Model::_create('foo_tag', ['name' => 'Foo tag 3', 'a' => 'A', 'b' => 'B']);
        $model = bhubr\Post_Model::_create('foo', ['name' => 'Pouet 2', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag1['id'], $tag2['id']]]);

        $expected_model = [
            'id' => 4, 'name' => 'Pouet 2', 'slug' => 'pouet-2', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag1['id'], $tag2['id']]
        ];
        $this->assertEquals($expected_model, $model);
        $read_model = bhubr\Post_Model::_read('foo', $model['id']);
        $this->assertEquals($expected_model, $read_model);


        $expected_tags2 = [$tag1['id'], $tag3['id']];
        $updated_model = bhubr\Post_Model::_update('foo', 4, ['name' => 'Pouet 2 updated', 'foo_cat' => $cat2['id'], 'foo_tags' => $expected_tags2]);
        $expected_model2 = [
            'id' => 4, 'name' => 'Pouet 2 updated', 'slug' => 'pouet-2', 'foo_cat' => $cat2['id'], 'foo_tags' => $expected_tags2
        ];
        $this->assertEquals($expected_tags2, $updated_model['foo_tags']);
        $this->assertEquals($expected_model2, $updated_model);

        bhubr\Post_Model::_delete('foo', 4);
        $this->assertEquals(null, get_post(4));
    }

    /**
     * Test creating and reading a model with terms
     */
    function test_read_all_with_terms() {
        $cat1 = bhubr\Term_Model::_create('foo_cat', ['name' => 'Foo cat A', 'a' => 'A', 'b' => 'B']);
        $cat2 = bhubr\Term_Model::_create('foo_cat', ['name' => 'Foo cat B', 'a' => 'A', 'b' => 'B']);
        $tag1 = bhubr\Term_Model::_create('foo_tag', ['name' => 'Foo tag C', 'a' => 'A', 'b' => 'B']);
        $tag2 = bhubr\Term_Model::_create('foo_tag', ['name' => 'Foo tag A', 'a' => 'A', 'b' => 'B']);
        $tag3 = bhubr\Term_Model::_create('foo_tag', ['name' => 'Foo tag B', 'a' => 'A', 'b' => 'B']);
        $model1 = bhubr\Post_Model::_create('foo', ['name' => 'Foo Biz', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag1['id'], $tag2['id']]]);
        $model2 = bhubr\Post_Model::_create('foo', ['name' => 'Foo Bar', 'foo_cat' => $cat2['id'], 'foo_tags' => [$tag2['id']]]);
        $model3 = bhubr\Post_Model::_create('foo', ['name' => 'Foo Woo', 'foo_cat' => $cat2['id'], 'foo_tags' => [$tag3['id']]]);

        $all_models = bhubr\Post_Model::_read_all('foo');
        $this->assertEquals([
            ['id' => 3, 'name' => 'Pouet 1', 'slug' => 'pouet-1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap', 'foo_cat' => null, 'foo_tags' => []],
            ['id' => 5, 'name' => 'Foo Biz', 'slug' => 'foo-biz', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag2['id'], $tag1['id']]],
            ['id' => 6, 'name' => 'Foo Bar', 'slug' => 'foo-bar', 'foo_cat' => $cat2['id'], 'foo_tags' => [$tag2['id']]],
            ['id' => 7, 'name' => 'Foo Woo', 'slug' => 'foo-woo', 'foo_cat' => $cat2['id'], 'foo_tags' => [$tag3['id']]],
        ], $all_models);
    }

    /**
     * Test creating model through class
     */
    function test_crud_model_class() {
        $dummy1 = bhubr\Dummy::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $this->assertEquals($dummy1['type'], 'toto');
        $this->assertEquals($dummy1['status'], 'new');
        $this->assertEquals($dummy1['dummy_int'], 1);
        $this->assertEquals($dummy1['dummy_str'], 'Hello there!');
        $dummy1id = $dummy1['id'];

        $dummy1upd = bhubr\Dummy::update($dummy1id, ['status' => 'confirmed']);
        $this->assertEquals($dummy1upd['status'], 'confirmed');

        $dummy1read = bhubr\Dummy::read($dummy1id);
        $this->assertEquals($dummy1read['type'], 'toto');
        $this->assertEquals($dummy1read['status'], 'confirmed');
        $this->assertEquals($dummy1read['dummy_int'], 1);
        $this->assertEquals($dummy1read['dummy_str'], 'Hello there!');

        $dummy1deleted = bhubr\Dummy::delete($dummy1id);
        // var_dump($dummy1deleted);
        $this->assertEquals($dummy1deleted['ID'], $dummy1id);
    }

    /**
     * Test one-to-one relationship
     */
    // function test_relationships_one_to_one() {
    //     $dummy = bhubr\Dummy::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
    //     $dummy_id = $dummy['id'];
    //     $dumbass = bhubr\Dumbass::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
    //     $dumbass_id = $dumbass['id'];
    //     $this->assertEquals($dumbass['dummy_id'], $dummy_id);
    //     // TODO: should be done automagically
    //     $dummy_upd = bhubr\Dummy::update($dummy_id, ['dumbass_id' => $dumbass_id]);
    //     $this->assertEquals($dummy_upd['dumbass_id'], $dumbass_id);
    // }

    /**
     * Test one-to-many relationship
     */
    function test_relationships_one_to_many() {
        // Create main object of type Dummy
        $dummy = bhubr\Dummy::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy_id = $dummy['id'];

        // Create one-to-one relationship with Dumbass object
        $dumbass = bhubr\Dumbass::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbass_id = $dumbass['id'];
        $dummy_upd = bhubr\Dummy::update($dummy_id, ['dumbass_id' => $dumbass_id]);

        // Create one-to-many relationship with Dumbmany objects
        $dumbmany1 = bhubr\Dumbmany::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbmany2 = bhubr\Dumbmany::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Howdy there!']);
        $dumbmany3 = bhubr\Dumbmany::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello world!']);
        $dumbmany1_id = $dumbmany1['id'];
        $dumbmany2_id = $dumbmany2['id'];
        $dumbmany3_id = $dumbmany3['id'];
        $dumbmanies = bhubr\Dumbmany::read_all();
        echo "\n\n#### Dumbmanies\n";
        var_dump($dumbmanies);
        $this->assertEquals(3, count($dumbmanies));
        // $this->assertEquals($dumbass['dummy_id'], $dummy_id);
        // $this->assertEquals($dummy_upd['dumbass_id'], $dumbass_id);

        $dummy = bhubr\Dummy::read($dummy_id);
        $this->assertEquals($dummy['dumbass_id'], $dumbass_id);
        $this->assertTrue(array_key_exists('dumbmanies', $dummy), 'Dummy object has no "dumbmanies" key');
        $this->assertEquals($dummy['dumbmanies'], [$dumbmany1_id, $dumbmany2_id, $dumbmany3_id]);
    }

}
