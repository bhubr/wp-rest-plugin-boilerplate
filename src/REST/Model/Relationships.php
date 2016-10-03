<?php
 /**
  * @package bhubr\REST
  */
namespace bhubr\REST\Model;

class Relationships {
    public static function parse_for_model($relationships) {
        return $relationships->map(['bhubr\REST\Model\Relationships', 'parse_relationship']);
    }

    public static function parse_relationship($relationship_descriptor, $relationship_attr) {
        $desc_bits = explode(':', $relationship_descriptor);
        $rel_class = $desc_bits[0];
        $rel_type = $desc_bits[1];
        return [
            'type'   => $rel_class::$plural,
            'plural' => $rel_type !== 'has_one'
        ];
    }
}