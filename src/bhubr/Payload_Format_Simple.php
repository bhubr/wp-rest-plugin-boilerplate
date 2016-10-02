<?php
namespace bhubr;

class Payload_Format_Simple extends Payload_Format_Common implements Payload_Format_Interface {
    public static function extract_relationships($payload, $model_relationships) {
        $relationships = [];
        foreach($model_relationships as $relationship => $descriptor) {
            if (! array_key_exists($relationship, $payload)) continue;
            $values = $payload[$relationship];
            if(! $descriptor['plural'] && is_array($values)) {
                $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
                throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_SINGULAR);
            }
            else if($descriptor['plural'] && ! is_array($values)) {
                $msg = "An array of relatee ids is expected for plural relationship with " . $descriptor['type'];
                throw new \Exception($msg, Payload_Format::RELATIONSHIP_IS_PLURAL);
            }
            $relationships[$relationship] = $payload[$relationship];
            unset($payload[$relationship]);
        }
        return [
            'relationships' => $relationships,
            'payload' => $payload
        ];
    }

    public static function check_and_extract_attributes($payload, $model_attributes) {
        $attributes = [];
        $missing = [];
        $invalid = [];
        foreach($model_attributes as $attribute_name => $descriptor) {
            if (! array_key_exists($attribute_name, $payload) && $descriptor['required']) {
                $missing[] = $attribute_name;
                continue;
            }
            $attribute_value = $payload[$attribute_name];
            unset($payload[$attribute_name]);
            if (! self::check_type($attribute_value, $descriptor['type'])) {
                $invalid[$attribute_name] = "Invalid attribute: not of type '" . $descriptor['type'] . "'";
            }
            else if( array_key_exists('validator', $descriptor) &&
                ! self::validate($attribute_value, $descriptor['validator'])
            ) {
                $validator = $descriptor['validator'];
                $invalid[$attribute_name] = "Invalid attribute: did not pass validator '$validator'";
            }
            else {
                $attributes[$attribute_name] = $attribute_value;    
            }
        }
        return [
            'attributes' => $attributes,
            'missing'    => $missing,
            'invalid'    => $invalid,
            'unknown'    => $payload
        ];
    }

    // public static function parse_and_validate($payload, $model_attributes, $model_relationships) {
        // $attributes = [];
        // $relationships = [];
        // $unknown = [];
        // foreach ($payload as $key => $value) {
        //     if (array_search($key, $attributes_keys) !== false) {
        //         $attributes[$key] = $value;
        //     }
        //     else if (array_search($key, $relationships_keys) !== false) {
        //         $relationships[$key] = $value;
        //     }
        //     else $unknown[] = $value;
        // }
        // return  [
        //     'attributes' => $attributes,
        //     'relationships' => $relationships,
        //     'unknown' => $unknown
        // ];
    // }
}