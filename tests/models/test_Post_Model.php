<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

// require_once 'inc/Dummy.php';
// require 'inc/Dumbass.php';
// require 'inc/Dumbmany.php';
// require 'inc/Dumbmany2many.php';

/**
 * Sample test case.
 */
class Test_Post_Model extends WP_UnitTestCase {

    protected $rpb;

    protected function createAndTruncatePivotTable() {
        global $wpdb;
        $pivot_table = $wpdb->prefix . 'rpb_many_to_many';
        $this->rpb->create_assoc_with_meta_table('wprbp-test-suite');

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $res = $mysqli->query("TRUNCATE TABLE $pivot_table");
        if (! $res) {
            throw new Exception("Could not empty pivot table $pivot_table\n");
        }
    }

    public function setUp() {
        parent::setUp();
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance();
        // $plugin_descriptor = require 'plugin_descriptor.php';
        // $this->rpb->register_plugin('wprbp-test-suite', $plugin_descriptor);
        $this->rpb->register_plugin('wprbp-test-foo', MODELS_DIR . '/foo');
        $this->rpb->register_plugin('wprbp-test-dummy', MODELS_DIR . '/dummy');
        do_action('init');
        $this->createAndTruncatePivotTable();

    }

    public function tearDown() {
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
        $model = bhubr\Foo::create(['name' => 'Pouet 2', 'foo_cat' => $cat1['id'], 'foo_tags' => [$tag1['id'], $tag2['id']]]);

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
    function test_relationships_one_to_one() {
        $dummy = bhubr\Dummy::create(['name' => 'Test 121 Dummy #1', 'type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy_id = $dummy['id'];
        $dumbass = bhubr\Dumbass::create(['name' => 'Test 121 Dumbass #1', 'dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbass_id = $dumbass['id'];
        $this->assertEquals($dumbass['dummy_id'], $dummy_id);
        // TODO: should be done automagically
        $dummy_upd = bhubr\Dummy::update($dummy_id, ['dumbass_id' => $dumbass_id]);
        $this->assertEquals($dummy_upd['dumbass_id'], $dumbass_id);
    }

    /**
     * Test one-to-many relationship
     */
    function test_relationships_one_to_many() {
        // Create main object of type Dummy
        $dummy = bhubr\Dummy::create(['name' => 'Test 12m Dummy #1', 'type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy2 = bhubr\Dummy::create(['name' => 'Test 12m Dummy #2', 'type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!']);
        $dummy_id = $dummy['id'];

        // Create one-to-one relationship with Dumbass object
        $dumbass = bhubr\Dumbass::create(['name' => 'Test 12m Dumbass #1', 'dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbass_id = $dumbass['id'];
        $dummy_upd = bhubr\Dummy::update($dummy_id, ['dumbass_id' => $dumbass_id]);

        // Create one-to-many relationship with Dumbmany objects
        $dumbmany1 = bhubr\Dumbmany::create(['name' => 'Test 12m Dumbmany #1', 'dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbmany2 = bhubr\Dumbmany::create(['name' => 'Test 12m Dumbmany #2', 'dummy_id' => $dummy_id, 'dumb_str' => 'Howdy there!']);
        $dumbmany3 = bhubr\Dumbmany::create(['name' => 'Test 12m Dumbmany #3', 'dummy_id' => $dummy_id, 'dumb_str' => 'Hello world!']);
        $dumbmany4 = bhubr\Dumbmany::create(['name' => 'Test 12m Dumbmany #4', 'dummy_id' => $dummy2['id'], 'dumb_str' => 'Hello world!']);
        $dumbmany1_id = $dumbmany1['id'];
        $dumbmany2_id = $dumbmany2['id'];
        $dumbmany3_id = $dumbmany3['id'];
        $dumbmanies = bhubr\Dumbmany::read_all();
        // echo "\n\n#### Dumbmanies\n";
        // var_dump($dumbmanies);
        $this->assertEquals(4, count($dumbmanies));
        // $this->assertEquals($dumbass['dummy_id'], $dummy_id);
        // $this->assertEquals($dummy_upd['dumbass_id'], $dumbass_id);

        $dummy = bhubr\Dummy::read($dummy_id);
        $this->assertEquals($dummy['dumbass_id'], $dumbass_id);
        $this->assertTrue(array_key_exists('dumbmanies', $dummy), 'Dummy object has no "dumbmanies" key');
        $this->assertEquals([$dumbmany1_id, $dumbmany2_id, $dumbmany3_id], $dummy['dumbmanies']);

    }

    /**
     * Test many-to-many relationship
     */
    function test_relationships_many_to_many() {
        // Create main object of type Dummy
        $dummy = bhubr\Dummy::create(['name' => 'Test m2m Dummy #1', 'type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy2 = bhubr\Dummy::create(['name' => 'Test m2m Dummy #2', 'type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!']);
        $dummy3 = bhubr\Dummy::create(['name' => 'Test m2m Dummy #3', 'type' => 'tutu', 'status' => 'new', 'dummy_int' => 3, 'dummy_str' => 'Hi there!']);
        $dummy_id = $dummy['id'];
        $dummy2_id = $dummy2['id'];
        $dummy3_id = $dummy3['id'];

        // Create one-to-one relationship with Dumbass object
        $dumbass = bhubr\Dumbass::create(['name' => 'Test m2m Dumbass #1', 'dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbass_id = $dumbass['id'];
        $dummy_upd = bhubr\Dummy::update($dummy_id, ['dumbass_id' => $dumbass_id]);

        // Create one-to-many relationship with Dumbmany objects
        $dumbmany1 = bhubr\Dumbmany::create(['name' => 'Test m2m Dumbmany #1', 'dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $dumbmany2 = bhubr\Dumbmany::create(['name' => 'Test m2m Dumbmany #2', 'dummy_id' => $dummy_id, 'dumb_str' => 'Howdy there!']);

        // Create many-to-many relationship with Dumbmany2many objects
        $dumbm2m1 = bhubr\Dumbmany2many::create(['name' => 'Test m2m Dumb m2m #1', 'dumb_str' => 'Hello there!', 'dummies' => [$dummy_id]]);
        $dumbm2m2 = bhubr\Dumbmany2many::create(['name' => 'Test m2m Dumb m2m #2', 'dumb_str' => 'Howdy there!', 'dummies' => [$dummy2_id]]);
        $dumbm2m3 = bhubr\Dumbmany2many::create(['name' => 'Test m2m Dumb m2m #3', 'dumb_str' => 'How are you?', 'dummies' => [$dummy_id, $dummy3_id]]);
        $dumbm2m4 = bhubr\Dumbmany2many::create(['name' => 'Test m2m Dumb m2m #4', 'dumb_str' => 'Welcome home!', 'dummies' => [$dummy2_id, $dummy3_id]]);
        $dumbmany2manies = bhubr\Dumbmany2many::read_all();
        // var_dump($dumbmany2manies);

        $dummy = bhubr\Dummy::read($dummy_id);
        $dummy2 = bhubr\Dummy::read($dummy2_id);
        $dummy3 = bhubr\Dummy::read($dummy3_id);
        $dumbm2m1 = bhubr\Dumbmany2many::read($dumbm2m1['id']);
        $dumbm2m2 = bhubr\Dumbmany2many::read($dumbm2m2['id']);
        $dumbm2m3 = bhubr\Dumbmany2many::read($dumbm2m3['id']);
        $dumbm2m4 = bhubr\Dumbmany2many::read($dumbm2m4['id']);
        // var_dump($dummy);
        $this->assertEquals([$dumbm2m1['id'], $dumbm2m3['id']], $dummy['dumbmany2manies']);
        $this->assertEquals([$dumbm2m2['id'], $dumbm2m4['id']], $dummy2['dumbmany2manies']);
        $this->assertEquals([$dumbm2m3['id'], $dumbm2m4['id']], $dummy3['dumbmany2manies']);
        $this->assertEquals([$dumbm2m3['id'], $dumbm2m4['id']], $dummy3['dumbmany2manies']);

        $this->assertEquals([$dummy_id], $dumbm2m1['dummies']);
        $this->assertEquals([$dummy2_id], $dumbm2m2['dummies']);
        $this->assertEquals([$dummy_id, $dummy3_id], $dumbm2m3['dummies']);
        $this->assertEquals([$dummy2_id, $dummy3_id], $dumbm2m4['dummies']);


        // Setup last part of the test (expected relatees of Dumbmany2many #4, after update)
        $expected_relatee_ids = [$dummy_id, $dummy2_id];
        sort($expected_relatee_ids);

        // Action: update
        $dumbm2m4upd = bhubr\Dumbmany2many::update($dumbm2m4['id'], ['dummies' => [$dummy_id, $dummy2_id]]);

        // Sort relatee ids and perform assertion
        $actual_relatee_ids = $dumbm2m4upd['dummies'];
        sort($actual_relatee_ids);
        $this->assertEquals($expected_relatee_ids, $actual_relatee_ids);

        // Action: re-read
        $dumbm2m4reread = bhubr\Dumbmany2many::read($dumbm2m4['id']);

        // Sort relatee ids and perform assertion
        $actual_relatee_ids = $dumbm2m4upd['dummies'];
        sort($actual_relatee_ids);
        $this->assertEquals($expected_relatee_ids, $actual_relatee_ids);
    }

}
