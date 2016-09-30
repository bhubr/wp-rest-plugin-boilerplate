<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

require_once 'inc/Dummy.php';
require 'inc/DummyTerm.php';
require 'inc/Termone.php';
require 'inc/Termany.php';
require 'inc/Termany2many.php';

/**
 * Sample test case.
 */
class Test_Term_Model extends WP_UnitTestCase {

    protected $rpb;

    function setUp() {
        $plugin_descriptor = require 'plugin_descriptor.php';
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance(realpath(__DIR__ . '/..'));
        $this->rpb->register_plugin('wprbp-test-suite', $plugin_descriptor);

        bhubr\Base_Model::register_taxonomy('dummyterm', 'DummyTerm', 'dummy', ['fields' => ['type', 'status', 'dummy_int', 'dummy_str']]);
        bhubr\Base_Model::register_taxonomy('termone', 'Termone', 'dummy', ['fields' => ['dumb_str']]);
        bhubr\Base_Model::register_taxonomy('termany', 'Termany', 'dummy', ['fields' => ['dumb_str']]);
        bhubr\Base_Model::register_taxonomy('termany2many', 'Termany2many', 'dummy', ['fields' => ['dumb_str']]);
        do_action('init');
        // $this->rpb->create_term_meta_tables('wprbp-test-suite');
        global $wpdb;
        $table = $wpdb->prefix . 'rpb_many_to_many';

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $res = $mysqli->query("TRUNCATE TABLE $table");
    }

    function tearDown() {
        // $this->rpb->delete_term_meta_tables('wprbp-test-suite');
    }

    /**
     * @expectedException     Exception
     */
    public function test_create_bad_type()
    {
        $model = bhubr\Term_Model::_create('fzoo', ['name' => 'Pouet 1', 'baz' => 'poop', 'bee' => 'poy', 'boo' => 'yap']);
    }

    

    /**
     * Test one-to-one relationship
     */
    function test_relationships_one_to_one() {
        $dummy = bhubr\DummyTerm::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy_id = $dummy['id'];
        $termone = bhubr\Termone::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $termone_id = $termone['id'];
        $this->assertEquals($termone['dummy_id'], $dummy_id);
        // TODO: should be done automagically
        $dummy_upd = bhubr\DummyTerm::update($dummy_id, ['termone_id' => $termone_id]);
        $this->assertEquals($dummy_upd['termone_id'], $termone_id);
    }

    /**
     * Test one-to-many relationship
     */
    function test_relationships_one_to_many() {
        // Create main object of type Dummy
        $dummy = bhubr\DummyTerm::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy2 = bhubr\DummyTerm::create(['type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!']);
        $dummy_id = $dummy['id'];

        // Create one-to-one relationship with Dumbass object
        $termone = bhubr\Termone::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $termone_id = $termone['id'];
        $dummy_upd = bhubr\DummyTerm::update($dummy_id, ['termone_id' => $termone_id]);

        // Create one-to-many relationship with Dumbmany objects
        $termany1 = bhubr\Termany::create(['dummyterm_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $termany2 = bhubr\Termany::create(['dummyterm_id' => $dummy_id, 'dumb_str' => 'Howdy there!']);
        $termany3 = bhubr\Termany::create(['dummyterm_id' => $dummy_id, 'dumb_str' => 'Hello world!']);
        $termany4 = bhubr\Termany::create(['dummyterm_id' => $dummy2['id'], 'dumb_str' => 'Hello world!']);
        $termany1_id = $termany1['id'];
        $termany2_id = $termany2['id'];
        $termany3_id = $termany3['id'];
        $termanies = bhubr\Termany::read_all();
        // echo "\n\n#### Dumbmanies\n";
        // var_dump($termanies);
        $this->assertEquals(4, count($termanies));
        // $this->assertEquals($termone['dummy_id'], $dummy_id);
        // $this->assertEquals($dummy_upd['termone_id'], $termone_id);

        $dummy = bhubr\DummyTerm::read($dummy_id);
        $this->assertEquals($dummy['termone_id'], $termone_id);
        $this->assertTrue(array_key_exists('termanies', $dummy), 'Dummy object has no "termanies" key');
        $this->assertEquals([$termany1_id, $termany2_id, $termany3_id], $dummy['termanies']);

    }

    /**
     * Test many-to-many relationship
     */
    function test_relationships_many_to_many() {
        // Create main object of type Dummy
        $dummy = bhubr\DummyTerm::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummy2 = bhubr\DummyTerm::create(['type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!']);
        $dummy3 = bhubr\DummyTerm::create(['type' => 'tutu', 'status' => 'new', 'dummy_int' => 3, 'dummy_str' => 'Hi there!']);
        $dummy_id = $dummy['id'];
        $dummy2_id = $dummy2['id'];
        $dummy3_id = $dummy3['id'];

        // Create one-to-one relationship with Dumbass object
        $termone = bhubr\Termone::create(['dummy_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $termone_id = $termone['id'];
        $dummy_upd = bhubr\DummyTerm::update($dummy_id, ['termone_id' => $termone_id]);

        // Create one-to-many relationship with Dumbmany objects
        $termany1 = bhubr\Termany::create(['dummyterm_id' => $dummy_id, 'dumb_str' => 'Hello there!']);
        $termany2 = bhubr\Termany::create(['dummyterm_id' => $dummy_id, 'dumb_str' => 'Howdy there!']);

        // Create many-to-many relationship with Dumbmany2many objects
        $dumbm2m1 = bhubr\Termany2many::create(['dumb_str' => 'Hello there!', 'dummyterms' => [$dummy_id]]);
        $dumbm2m2 = bhubr\Termany2many::create(['dumb_str' => 'Howdy there!', 'dummyterms' => [$dummy2_id]]);
        $dumbm2m3 = bhubr\Termany2many::create(['dumb_str' => 'How are you?', 'dummyterms' => [$dummy_id, $dummy3_id]]);
        $dumbm2m4 = bhubr\Termany2many::create(['dumb_str' => 'Welcome home!', 'dummyterms' => [$dummy2_id, $dummy3_id]]);
        $termany2manies = bhubr\Termany2many::read_all();
        // var_dump($termany2manies);

        $dummy = bhubr\DummyTerm::read($dummy_id);
        $dummy2 = bhubr\DummyTerm::read($dummy2_id);
        $dummy3 = bhubr\DummyTerm::read($dummy3_id);
        $dumbm2m1 = bhubr\Termany2many::read($dumbm2m1['id']);
        $dumbm2m2 = bhubr\Termany2many::read($dumbm2m2['id']);
        $dumbm2m3 = bhubr\Termany2many::read($dumbm2m3['id']);
        $dumbm2m4 = bhubr\Termany2many::read($dumbm2m4['id']);
        // var_dump($dummy);
        $this->assertEquals([$dumbm2m1['id'], $dumbm2m3['id']], $dummy['termany2manies']);
        $this->assertEquals([$dumbm2m2['id'], $dumbm2m4['id']], $dummy2['termany2manies']);
        $this->assertEquals([$dumbm2m3['id'], $dumbm2m4['id']], $dummy3['termany2manies']);
        $this->assertEquals([$dumbm2m3['id'], $dumbm2m4['id']], $dummy3['termany2manies']);

        $this->assertEquals([$dummy['id']], $dumbm2m1['dummyterms']);
        $this->assertEquals([$dummy2['id']], $dumbm2m2['dummyterms']);
        $this->assertEquals([$dummy['id'], $dummy3['id']], $dumbm2m3['dummyterms']);
        $this->assertEquals([$dummy2['id'], $dummy3['id']], $dumbm2m4['dummyterms']);

        // $this->assertEquals($dummy['termone_id'], $termone_id);
        // $this->assertTrue(array_key_exists('dumbmanies', $dummy), 'Dummy object has no "dumbmanies" key');
        // $this->assertEquals($dummy['dumbmanies'], [$termany1_id, $termany2_id, $termany3_id]);

    }

}
