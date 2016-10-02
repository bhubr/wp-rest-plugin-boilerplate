<?php
namespace bhubr;

class Termany extends Term_Model {
    static $type = 'term';
    static $post_type = 'dummy';

    static $singular = 'termany';
    static $plural = 'termanies';

    static $name_s = 'Termany';
    static $name_p = 'Termanies';

    static $fields = [
        'dumb_str'  => 'string',
        'dummyterm_id'  => 'DummyTerm:belongs_to'
    ];
    static $relations = [
        'dummyterm'    => 'DummyTerm:belongs_to'
    ];
}