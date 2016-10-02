<?php

/**
 * Class SampleTest
 *
 * @package Sandbox_Plugin
 */

use bhubr\Payload_Format;
use bhubr\Payload_Format_JsonAPI;

/**
 * Sample test case.
 */
class Test_Payload_Format_JsonAPI extends WP_UnitTestCase {

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

    private function format_relationships($rel_descriptor, $rel_payload) {
        $output = [];
        foreach($rel_descriptor as $field_name => $desc) {
            $payload_ids = $rel_payload[$field_name];
            $rel_type = $desc['type'];
            $data = ! is_array($payload_ids) ? ['type' => $rel_type, 'id' => $payload_ids] :
                array_map(function($id) use($rel_type) {
                    return ['type' => $rel_type, 'id' => $id];
                }, $payload_ids);
            $output[$field_name] = ['data' => $data];
        }
        return $output;
    }

    private function build_payload($type, $attributes, $rel_descriptor, $rel_payload) {
        $fmt_relationships = $this->format_relationships($rel_descriptor, $rel_payload);
        return [
            'data' => [
                'type'          => $type,
                'attributes'    => $attributes,
                'relationships' => $fmt_relationships
            ]
        ];
    }

    function setUp() {
    }

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
     * @expectedExceptionCode bhubr\Payload_Format::JSONAPI_MISSING_DATA
     * @expectedExceptionMessage Payload missing: /data
     */
    function test_nok_payload_missing_data() {
        $data = Payload_Format_JsonAPI::extract_relationships(['title' => 'cool'], $this->relationships);
    }

    /**
     * No relationships but no error
     */
    function test_ok_payload_data_no_relationships() {
        $payload = ['data' => ['attributes' => ['title' => 'cool']]];
        $data = Payload_Format_JsonAPI::extract_relationships($payload, $this->relationships);
        $this->assertEquals([], $data['relationships']);
        $this->assertEquals($payload, $data['payload']);
    }

    /**
     * Relationships but no data
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::JSONAPI_MISSING_DATA
     * @expectedExceptionMessage Payload missing: /data/relationships/relatee/data
     */
    function test_ok_payload_data_relationship_nodata() {
        $payload = [
            'data' => [
                'attributes' => ['title' => 'cool'],
                'relationships' => [
                    'relatee' => []
                ]
            ]
        ];
        $data = Payload_Format_JsonAPI::extract_relationships($payload, $this->relationships);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_IS_SINGULAR
     */
    function test_nok_relationship_expects_single_item() {
        $payload = $this->build_payload('dummy_type', $this->payload_ok, $this->relationships, $this->payload_rel_singular_nok);
        // $payload = array_merge($this->payload_ok, $this->payload_rel_plural_nok);
        $data = Payload_Format_JsonAPI::extract_relationships($payload, $this->relationships);
    }

    /**
     * Error: bad relation type
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_BAD_TYPE
     * @expectedExceptionMessage Relationship type mismatch (exp: a_post_type, got: invalid_post_type)
     */
    function test_nok_relationship_single_invalid_type() {
        $payload = $this->build_payload('dummy_type', $this->payload_ok, $this->relationships, $this->payload_rel_ok);
        $payload['data']['relationships']['relatee']['data']['type'] = 'invalid_post_type';
        // $payload = array_merge($this->payload_ok, $this->payload_rel_plural_nok);
        $data = Payload_Format_JsonAPI::extract_relationships($payload, $this->relationships);
    }

    /**
     * Error: several items provided for a single relationship
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::RELATIONSHIP_IS_PLURAL
     */
    function test_nok_relationship_expects_several_items() {
        $payload = $this->build_payload('dummy_type', $this->payload_ok, $this->relationships, $this->payload_rel_plural_nok);
        var_dump($payload);
        // $payload = array_merge($this->payload_ok, $this->payload_rel_plural_nok);
        $data = Payload_Format_JsonAPI::extract_relationships($payload, $this->relationships);
    }

    /**
     * OK: valid relationships provided
     */
    function test_extract_relationships_ok() {
        $payload = $this->build_payload('dummy_type', $this->payload_ok, $this->relationships, $this->payload_rel_ok);
        $data = Payload_Format_JsonAPI::extract_relationships($payload, $this->relationships);
        $this->assertEquals($this->payload_rel_ok, $data['relationships']);
        // $this->assertEquals($this->payload_ok, $data['payload']);
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
