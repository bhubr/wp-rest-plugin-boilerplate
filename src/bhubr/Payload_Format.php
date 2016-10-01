<?php
namespace bhubr;

class Payload_Format {
    const SIMPLE   = '_pf_simple';
    const JSONAPI  = '_pf_jsonapi';
    const JSEND    = '_pf_jsend';

    public static function parse($payload_format, $payload, $attributes_keys, $relationships_keys) {
        $accepted_payload_formats = [self::SIMPLE, self::JSONAPI, self::JSEND];
        if ( array_search($payload_format, $accepted_payload_formats ) === false) {
            $msg = "Invalid payload format $payload_format. Valid formats: ";
            throw new \Exception($msg. implode(', ', $accepted_payload_formats));
        }
    }
}