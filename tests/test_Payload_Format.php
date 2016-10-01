<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

use bhubr\Payload_Format;

/**
 * Sample test case.
 */
class Test_Payload_Utils extends WP_UnitTestCase {

    function setUp() {
    }

    /**
     * Check that payload parser works
     * @expectedException Exception
     */
    function test_parse_invalid_payload_type() {
        $data = Payload_Format::parse(-1, [], [], []);
    }

    /**
     * Check that payload parser works
     */
    function test_parse_basic() {
        $payload = [
            'dummy_int'  => 1,
            'dummy_str'  => 'Hello guys',
            'unknown'    => 'WTF',
            'relatee'    => 1,
            'affiliates' => [2, 3]
        ];
        $attributes_keys = [
            'dummy_int', 'dummy_str'
        ];
        $relationships_keys = ['relatee', 'affiliates'];
        $data = Payload_Format::parse(Payload_Format::SIMPLE, $payload, $attributes_keys, $relationships_keys);
        $this->assertEquals(['dummy_int' => '1', 'dummy_str' => 'Hello guys'], $data['attributes']);
    }

}
