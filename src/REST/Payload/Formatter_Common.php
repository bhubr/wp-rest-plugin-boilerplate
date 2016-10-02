<?php
namespace bhubr\REST\Payload;

use Respect\Validation\Validator as v;

class Formatter_Common {
    public static function can_be_string($var) {
        return $var === null || is_scalar($var) || is_callable([$var, '__toString']);
    }

    public static function check_type($value, $type) {
        $callables = [
            'integer' => 'is_int',
            'string'  => ['bhubr\Formatter_Common', 'can_be_string']
        ];
        if (!array_key_exists($type, $callables)) {
            throw new Exception ("No type checker for type '$type'");
        }
        return call_user_func($callables[$type], $value);
    }

    public static function validate($value, $validator) {
        return v::$validator()->validate($value);
    }

}