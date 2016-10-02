<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

use bhubr\Payload_Format;
use bhubr\Payload_Format_Simple;

/**
 * Sample test case.
 */
class Test_Payload_Utils extends WP_UnitTestCase {

    private $attributes = [
        'dummy_int' => [
            'type' => 'integer'
        ],
        'dummy_str' => [
            'type' => 'string',
            'required' => true
        ]
    ];
    private $payload_ok = [
        'dummy_int'  => 1,
        'dummy_str'  => 'Hello guys'
    ];
    private $payload_missing = [
        'dummy_int'  => 1
    ];
    private $payload_unknown = [
        'unknown'    => 'WTF'
    ];

    private $payload_rel_ok = [
        'relatee'    => 1,
        'affiliates' => [2, 3]
    ];
    private $payload_rel_singular_nok = [
        'relatee'    => [1],
        'affiliates' => [2, 3]
    ];
    private $payload_rel_plural_nok = [
        'relatee'    => 1,
        'affiliates' => 2
    ];


    private $relationships = [
        'relatee' => [
            'type'   => 'a_post_type',
            'plural' => false
        ],
        'affiliates' => [
            'type'   => 'another_post_type',
            'plural' => true
        ]
    ];

    function setUp() {
    }

    /**
     * Error: invalid payload format
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::INVALID_PAYLOAD_FORMAT
     */
    function test_parse_invalid_payload_format() {
        $data = Payload_Format::parse_and_validate(-1, [], [], []);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_IS_SINGULAR
     */
    function test_parse_relationship_expects_singular_item() {
        $payload = array_merge($this->payload_ok, $this->payload_rel_singular_nok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_IS_PLURAL
     */
    function test_parse_relationship_expects_several_items() {
        $payload = array_merge($this->payload_ok, $this->payload_rel_plural_nok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
    }

    /**
     * Check that payload parser works
     */
    function test_parse_basic() {

        // $data = Payload_Format::parse_and_validate(Payload_Format::SIMPLE, $payload, $attributes_keys, $relationships_keys);
        // var_dump($data);
        // $this->assertEquals(['dummy_int' => '1', 'dummy_str' => 'Hello guys'], $data['attributes']);
        // $this->assertEquals(['relatee' => 1, 'affiliates' => [2, 3]], $data['relationships']);
    }

}
