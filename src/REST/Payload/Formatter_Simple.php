<?php
namespace bhubr\REST\Payload;

class Formatter_Simple extends Formatter_Common implements Formatter_Interface {
    public static function extract_relationships($payload, $model_relationships) {
        $relationships = [];
        foreach($model_relationships as $relationship => $descriptor) {
            if (! array_key_exists($relationship, $payload)) continue;
            $values = $payload[$relationship];
            if(! $descriptor['plural'] && is_array($values)) {
                $msg = "A singular relatee id is expected for singular relationship with " . $descriptor['type'];
                throw new \Exception($msg, Formatter::RELATIONSHIP_IS_SINGULAR);
            }
            else if($descriptor['plural'] && ! is_array($values)) {
                $msg = "An array of relatee ids is expected for plural relationship with " . $descriptor['type'];
                throw new \Exception($msg, Formatter::RELATIONSHIP_IS_PLURAL);
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
        var_dump($model_attributes);
        foreach($model_attributes as $attribute_name => $descriptor) {
            echo "###  #1 Attribute name: $attribute_name\n"; // var_dump($descriptor);
            if (! array_key_exists($attribute_name, $payload) ) {
                if( array_key_exists('required', $descriptor) && $descriptor['required'] ) {
                    $missing[] = $attribute_name;
                }
                continue;
            }
            $attribute_value = $payload[$attribute_name];
            echo "###  #2 Attribute name: $attribute_name => $attribute_value\n"; // var_dump($descriptor);
            unset($payload[$attribute_name]);
            if (! self::check_type($attribute_value, $descriptor['type'])) {
            echo "###  #3a Attribute name: $attribute_name => $attribute_value\n"; // var_dump($descriptor);
                $invalid[$attribute_name] = "Invalid attribute: not of type '" . $descriptor['type'] . "'";
            }
            else if( array_key_exists('validator', $descriptor) &&
                ! self::validate($attribute_value, $descriptor['validator'])
            ) {
                echo "###  #3b Attribute name: $attribute_name => $attribute_value\n"; // var_dump($descriptor);
                $validator = $descriptor['validator'];
                $invalid[$attribute_name] = "Invalid attribute: did not pass validator '$validator'";
            }
            else {
                echo "###  #3c Attribute name: $attribute_name => $attribute_value\n"; // var_dump($descriptor);

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