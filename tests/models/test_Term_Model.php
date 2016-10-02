<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

// require_once 'inc/Dummy.php';
// require 'inc/DummyTerm.php';
// require 'inc/Termone.php';
// require 'inc/Termany.php';
// require 'inc/Termany2many.php';

/**
 * Sample test case.
 */
class Test_Term_Model extends WP_UnitTestCase {

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

    function setUp() {
        $this->rpb = bhubr\REST_Plugin_Boilerplate::get_instance(realpath(__DIR__ . '/..'));
        $this->rpb->register_plugin('wprbp-test-dummy', MODELS_DIR . '/dummy');

        do_action('init');
        $this->createAndTruncatePivotTable();
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
        $dummyterm = bhubr\DummyTerm::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummyterm_id = $dummyterm['id'];
        $termone = bhubr\Termone::create(['dummy_id' => $dummyterm_id, 'dumb_str' => 'Hello there!']);
        $termone_id = $termone['id'];
        $this->assertEquals($termone['dummy_id'], $dummyterm_id);
        // TODO: should be done automagically
        $dummyterm_upd = bhubr\DummyTerm::update($dummyterm_id, ['termone_id' => $termone_id]);
        $this->assertEquals($dummyterm_upd['termone_id'], $termone_id);
    }

    /**
     * Test one-to-many relationship
     */
    function test_relationships_one_to_many() {
        // Create main object of type Dummy
        $dummyterm = bhubr\DummyTerm::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummyterm2 = bhubr\DummyTerm::create(['type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!']);
        $dummyterm_id = $dummyterm['id'];

        // Create one-to-one relationship with Dumbass object
        $termone = bhubr\Termone::create(['dummy_id' => $dummyterm_id, 'dumb_str' => 'Hello there!']);
        $termone_id = $termone['id'];
        $dummyterm_upd = bhubr\DummyTerm::update($dummyterm_id, ['termone_id' => $termone_id]);

        // Create one-to-many relationship with Dumbmany objects
        $termany1 = bhubr\Termany::create(['dummyterm_id' => $dummyterm_id, 'dumb_str' => 'Hello there!']);
        $termany2 = bhubr\Termany::create(['dummyterm_id' => $dummyterm_id, 'dumb_str' => 'Howdy there!']);
        $termany3 = bhubr\Termany::create(['dummyterm_id' => $dummyterm_id, 'dumb_str' => 'Hello world!']);
        $termany4 = bhubr\Termany::create(['dummyterm_id' => $dummyterm2['id'], 'dumb_str' => 'Hello world!']);
        $termany1_id = $termany1['id'];
        $termany2_id = $termany2['id'];
        $termany3_id = $termany3['id'];
        $termanies = bhubr\Termany::read_all();
        // echo "\n\n#### Dumbmanies\n";
        // var_dump($termanies);
        $this->assertEquals(4, count($termanies));
        // $this->assertEquals($termone['dummy_id'], $dummyterm_id);
        // $this->assertEquals($dummyterm_upd['termone_id'], $termone_id);

        $dummyterm = bhubr\DummyTerm::read($dummyterm_id);
        $this->assertEquals($dummyterm['termone_id'], $termone_id);
        $this->assertTrue(array_key_exists('termanies', $dummyterm), 'Dummy object has no "termanies" key');
        $this->assertEquals([$termany1_id, $termany2_id, $termany3_id], $dummyterm['termanies']);

    }

    /**
     * Test many-to-many relationship
     */
    function test_relationships_many_to_many() {
        // Create main object of type DummyTerm
        $dummyterm = bhubr\DummyTerm::create(['type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!']);
        $dummyterm2 = bhubr\DummyTerm::create(['type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!']);
        $dummyterm3 = bhubr\DummyTerm::create(['type' => 'tutu', 'status' => 'new', 'dummy_int' => 3, 'dummy_str' => 'Hi there!']);
        $dummyterm_id = $dummyterm['id'];
        $dummyterm2_id = $dummyterm2['id'];
        $dummyterm3_id = $dummyterm3['id'];

        // Create one-to-one relationship with Dumbass object
        $termone = bhubr\Termone::create(['dummy_id' => $dummyterm_id, 'dumb_str' => 'Hello there!']);
        $termone_id = $termone['id'];
        $dummyterm_upd = bhubr\DummyTerm::update($dummyterm_id, ['termone_id' => $termone_id]);

        // Create one-to-many relationship with Dumbmany objects
        $termany1 = bhubr\Termany::create(['dummyterm_id' => $dummyterm_id, 'dumb_str' => 'Hello there!']);
        $termany2 = bhubr\Termany::create(['dummyterm_id' => $dummyterm_id, 'dumb_str' => 'Howdy there!']);

        // Create many-to-many relationship with Dumbmany2many objects
        $dumbm2m1 = bhubr\Termany2many::create(['dumb_str' => 'Hello there!', 'dummyterms' => [$dummyterm_id]]);
        $dumbm2m2 = bhubr\Termany2many::create(['dumb_str' => 'Howdy there!', 'dummyterms' => [$dummyterm2_id]]);
        $dumbm2m3 = bhubr\Termany2many::create(['dumb_str' => 'How are you?', 'dummyterms' => [$dummyterm_id, $dummyterm3_id]]);
        $dumbm2m4 = bhubr\Termany2many::create(['dumb_str' => 'Welcome home!', 'dummyterms' => [$dummyterm2_id, $dummyterm3_id]]);
        $termany2manies = bhubr\Termany2many::read_all();

        // Create main object of type Dummy
        $dummy = bhubr\Dummy::create(['name' => 'TermTest m2m Dummy #1', 'type' => 'toto', 'status' => 'new', 'dummy_int' => 1, 'dummy_str' => 'Hello there!', 'dummyterms' => [$dummyterm_id, $dummyterm3_id]]);
        $dummy2 = bhubr\Dummy::create(['name' => 'TermTest m2m Dummy #2', 'type' => 'tata', 'status' => 'new', 'dummy_int' => 2, 'dummy_str' => 'Howdy there!', 'dummyterms' => [$dummyterm2_id, $dummyterm3_id]]);


        // var_dump($termany2manies);

        $dummyterm = bhubr\DummyTerm::read($dummyterm_id);
        $dummyterm2 = bhubr\DummyTerm::read($dummyterm2_id);
        $dummyterm3 = bhubr\DummyTerm::read($dummyterm3_id);
        $dumbm2m1 = bhubr\Termany2many::read($dumbm2m1['id']);
        $dumbm2m2 = bhubr\Termany2many::read($dumbm2m2['id']);
        $dumbm2m3 = bhubr\Termany2many::read($dumbm2m3['id']);
        $dumbm2m4 = bhubr\Termany2many::read($dumbm2m4['id']);
        // var_dump($dummyterm);
        $this->assertEquals([$dumbm2m1['id'], $dumbm2m3['id']], $dummyterm['termany2manies']);
        $this->assertEquals([$dumbm2m2['id'], $dumbm2m4['id']], $dummyterm2['termany2manies']);
        $this->assertEquals([$dumbm2m3['id'], $dumbm2m4['id']], $dummyterm3['termany2manies']);
        $this->assertEquals([$dumbm2m3['id'], $dumbm2m4['id']], $dummyterm3['termany2manies']);

        $this->assertEquals([$dummy['id']], $dummyterm['dummies']);
        $this->assertEquals([$dummy2['id']], $dummyterm2['dummies']);
        $this->assertEquals([$dummy['id'], $dummy2['id']], $dummyterm3['dummies']);

        $this->assertEquals([$dummyterm['id']], $dumbm2m1['dummyterms']);
        $this->assertEquals([$dummyterm2['id']], $dumbm2m2['dummyterms']);
        $this->assertEquals([$dummyterm['id'], $dummyterm3['id']], $dumbm2m3['dummyterms']);
        $this->assertEquals([$dummyterm2['id'], $dummyterm3['id']], $dumbm2m4['dummyterms']);

        // $this->assertEquals($dummyterm['termone_id'], $termone_id);
        // $this->assertTrue(array_key_exists('dumbmanies', $dummyterm), 'Dummy object has no "dumbmanies" key');
        // $this->assertEquals($dummyterm['dumbmanies'], [$termany1_id, $termany2_id, $termany3_id]);

    }

}
