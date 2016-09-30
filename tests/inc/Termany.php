<?php
namespace bhubr;

class Termany extends Term_Model {
    static $type = 'term';
    static $singular = 'termany';
    static $plural = 'termanies';
    static $fields = [
        'dumb_str'  => 'string',
        'dummyterm_id'  => 'DummyTerm:belongs_to'
    ];
    static $relations = [
        'dummyterm'    => 'DummyTerm:belongs_to'
    ];
}