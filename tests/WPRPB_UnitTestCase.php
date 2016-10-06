<?php

class WPRPB_UnitTestCase extends WP_UnitTestCase {
    protected $mysqli;
    protected $rpb;

    protected function createAndTruncatePivotTable() {
        global $wpdb;
        $pivot_table = $wpdb->prefix . 'rpb_many_to_many';
        $this->rpb->create_assoc_with_meta_table('wprbp-test-suite');

        $this->mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $res = $this->mysqli->query("TRUNCATE TABLE $pivot_table");
        if (! $res) {
            throw new Exception("Could not empty pivot table $pivot_table => {$this->mysqli->error}");
        }
    }

    protected function truncatePostTable() {
        global $wpdb;
        $res = $this->mysqli->query("TRUNCATE TABLE {$wpdb->posts}");
        if (! $res) {
            throw new Exception("Could truncate table {$wpdb->posts} => {$this->mysqli->error}");
        }

    }

    public function setUp() {
        parent::setUp();
        $this->rpb = bhubr\REST\Plugin_Boilerplate::get_instance();
        $this->createAndTruncatePivotTable();
        $this->truncatePostTable();

    }

    function tearDown() {
        parent::tearDown();
        reset_singleton_instance('bhubr\REST\Plugin_Boilerplate');
        reset_singleton_instance('bhubr\REST\Model\Registry');
        // SUPER IMPORTANT sinon l'action d'enregistrement des types
        // est répétée plusieurs fois !!
        remove_action( 'init', [$this->rpb, 'register_types'] );
    }
}
