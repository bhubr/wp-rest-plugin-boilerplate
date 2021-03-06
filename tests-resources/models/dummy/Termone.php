<?php
namespace bhubr;

class Termone extends Term_Model {
    static $type = 'term';
    static $post_type = 'dummy';

    static $singular = 'termone';
    static $plural = 'termones';

    static $name_s = 'Termone';
    static $name_p = 'Termones';

    static $fields = [
        'dumb_str'  => 'string',
        'dummyterm_id'  => 'DummyTerm:belongs_to'
    ];
    static $relations = [
        'dummyterm'    => 'DummyTerm:belongs_to'
    ];

}