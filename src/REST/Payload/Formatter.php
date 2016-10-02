<?php
namespace bhubr\REST\Payload;

class Formatter {
    const SIMPLE   = '_Simple';
    const JSONAPI  = '_JsonAPI';
    const JSEND    = '_JSend';

    const INVALID_PAYLOAD_FORMAT   = 100;
    const RELATIONSHIP_IS_SINGULAR = 200;
    const RELATIONSHIP_IS_PLURAL   = 201;
    const RELATIONSHIP_BAD_TYPE    = 202;
    const RELATIONSHIP_INVALID_CLEAR = 203;

    const JSONAPI_MISSING_DATA            = 99;
    const JSONAPI_RELATIONSHIPS_NOT_ARRAY = 98;

    public static function parse_and_validate($payload_format, $payload, $model_attributes, $model_relationships) {
        $accepted_payload_formats = [self::SIMPLE, self::JSONAPI]; //, self::JSEND]; JSEND not implemented
        if ( array_search($payload_format, $accepted_payload_formats ) === false) {
            $msg = "Invalid payload format $payload_format. Valid formats: ";
            throw new \Exception($msg. implode(', ', $accepted_payload_formats), self::INVALID_PAYLOAD_FORMAT);
        }
        $strategy_class = 'bhubr\Formatter' . $payload_format;
        // return $strategy_class::parse($payload, $attributes_keys, $relationships_keys);
        $result_relationships = $strategy_class::extract_relationships($payload, $model_relationships);
        $result_attributes = $strategy_class::extract_attributes($result_relationships['payload'], $model_attributes);
        return [
            'relationships' => $result_relationships['relationships'],
            'attributes'    => $result_attributes['attributes'],
            'unknown_attrs' => $result_attributes['unknown'],
        ];
    }

}