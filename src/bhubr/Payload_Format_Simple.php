<?php
namespace bhubr;

class Payload_Format_Simple implements Payload_Format_Interface {
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

    public static function extract_attributes($payload, $model_attributes) {
        $attributes = [];
        foreach($model_attributes as $attribute) {
            if (array_key_exists($attribute, $payload)) {
                $attributes[$attribute] = $payload[$attribute];
                unset($payload[$attribute]);
            }
        }
        return [
            'attributes' => $attributes,
            'unknown' => $payload
        ];
    }

    public static function parse_and_validate($payload, $model_attributes, $model_relationships) {
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
    }
}