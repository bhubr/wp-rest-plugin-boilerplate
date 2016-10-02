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
class Test_Payload_Format_Simple extends WP_UnitTestCase {

    private $attributes = [
        'dummy_int' => [
            'type'     => 'integer',
            'required' => true
        ],
        'dummy_str' => [
            'type'     => 'string',
            'required' => true
        ],
        'url' => [
            'type'      => 'string',
            'validator' => 'url'
        ]
    ];
    private $payload_ok = [
        'dummy_int'  => 1,
        'dummy_str'  => 'Hello guys',
        'url'        => 'http://www.mydomain.com/phpmyadmin/'
    ];
    private $payload_invalid = [
        'dummy_int'  => 'Yay!',
        'dummy_str'  => [],
        'url'        => 'notanurl@gmail.com'
    ];

    private $payload_missing = [
        'url'        => 'http://www.mydomain.com/phpmyadmin/'
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

    /**
     * Error: invalid payload format
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::INVALID_PAYLOAD_FORMAT
     */
    function test_nok_parse_invalid_payload_format() {
        $data = Payload_Format::parse_and_validate(-1, [], [], []);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_IS_SINGULAR
     */
    function test_nok_relationship_expects_single_item() {
        $payload = array_merge($this->payload_ok, $this->payload_rel_singular_nok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_IS_PLURAL
     */
    function test_nok_relationship_expects_several_items() {
        $payload = array_merge($this->payload_ok, $this->payload_rel_plural_nok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
    }

    /**
     * OK: valid relationships provided
     */
    function test_extract_relationships_ok() {
        $payload = array_merge($this->payload_ok, $this->payload_rel_ok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
        $this->assertEquals($this->payload_rel_ok, $data['relationships']);
        $this->assertEquals($this->payload_ok, $data['payload']);
    }

    /**
     * Error: extract attributes - missing
     */
    function test_check_extract_attrs_nok_missing() {
        $payload = array_merge($this->payload_missing, $this->payload_rel_ok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
        $attrs = Payload_Format_Simple::check_and_extract_attributes($data['payload'], $this->attributes);
        $this->assertEquals($this->payload_missing, $attrs['attributes']);
        $this->assertEquals(['dummy_int', 'dummy_str'], $attrs['missing']);
        $this->assertEquals([], $attrs['invalid']);
        $this->assertEquals([], $attrs['unknown']);
    }

    /**
     * Error: extract attributes - invalid
     */
    function test_check_extract_attrs_nok_invalid() {
        $payload = array_merge($this->payload_invalid, $this->payload_rel_ok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
        $attrs = Payload_Format_Simple::check_and_extract_attributes($data['payload'], $this->attributes);
        $this->assertEquals([], $attrs['attributes']);
        $this->assertEquals([], $attrs['missing']);
        $this->assertEquals([
            'dummy_int' => "Invalid attribute: not of type 'integer'",
            'dummy_str' => "Invalid attribute: not of type 'string'",
            'url'       => "Invalid attribute: did not pass validator 'url'"
        ], $attrs['invalid']);
        $this->assertEquals([], $attrs['unknown']);
    }

    /**
     * OK: extract attributes
     */
    function test_check_extract_attrs_ok() {
        $payload = array_merge($this->payload_ok, $this->payload_rel_ok);
        $data = Payload_Format_Simple::extract_relationships($payload, $this->relationships);
        $attrs = Payload_Format_Simple::check_and_extract_attributes($data['payload'], $this->attributes);
        $this->assertEquals($this->payload_ok, $attrs['attributes']);
        $this->assertEquals([], $attrs['unknown']);
    }

}
