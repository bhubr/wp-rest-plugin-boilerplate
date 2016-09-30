<?php
namespace bhubr;

class Termone extends Term_Model {
    static $type = 'term';
    static $singular = 'termone';
    static $plural = 'termones';
    static $fields = [
        'dumb_str'  => 'string',
        'dummyterm_id'  => 'DummyTerm:belongs_to'
    ];
    static $relations = [
        'dummyterm'    => 'DummyTerm:belongs_to'
    ];

}