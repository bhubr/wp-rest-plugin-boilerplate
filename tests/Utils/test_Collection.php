<?php

/**
 * Class Test_Utils_Collection
 *
 * @package Sandbox_Plugin
 */

use bhubr\REST\Utils\Collection;

/**
 * Test homemade Collection class with get-or-fail method (Collection::get_f())
 */
class Test_Utils_Collection extends WP_UnitTestCase {
    private $collection;

    /**
     * Create test collection
     */
    public function setUp() {
        $this->collection = collect_f(['a' => 1]);
    }

    /**
     * OK, existing key
     */
    public function test_get_existing_key() {
        $a = $this->collection->get_f('a');
        $this->assertEquals(1, $a);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionMessage Key 'b' not found in collection
     */
    public function test_get_non_existing_key() {
        $b = $this->collection->get_f('b');
    }
}