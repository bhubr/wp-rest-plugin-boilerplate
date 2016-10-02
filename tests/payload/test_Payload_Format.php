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
class Test_Payload_Format extends WP_UnitTestCase {
    /**
     * Error: invalid payload format
     * @expectedException Exception
     * @expectedExceptionCode bhubr\Payload_Format::INVALID_PAYLOAD_FORMAT
     */
    function test_nok_parse_invalid_payload_format() {
        $data = Payload_Format::parse_and_validate(-1, [], [], []);
    }
}
