<?php
namespace bhubr;

class Payload_Format {
    const SIMPLE   = '_Simple';
    const JSONAPI  = '_JsonAPI';
    const JSEND    = '_JSend';

    public static function parse($payload_format, $payload, $attributes_keys, $relationships_keys) {
        $accepted_payload_formats = [self::SIMPLE, self::JSONAPI, self::JSEND];
        if ( array_search($payload_format, $accepted_payload_formats ) === false) {
            $msg = "Invalid payload format $payload_format. Valid formats: ";
            throw new \Exception($msg. implode(', ', $accepted_payload_formats));
        }
        $strategy_class = 'bhubr\Payload_Format' . $payload_format;
        return $strategy_class::parse($payload, $attributes_keys, $relationships_keys);
    }
}