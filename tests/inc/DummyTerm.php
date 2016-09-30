<?php
namespace bhubr;

class DummyTerm extends Term_Model {
    static $type = 'term';
    static $singular = 'dummyterm';
    static $plural = 'dummyterms';
    static $fields = [
        'type'          => 'string',
        'status'        => 'string',
        'dummy_int'     => 'integer',
        'dummy_str'     => 'string',
        'termone_id'    => 'Termone:has_one'
    ];
    static $relations = [
        'termone'        => 'Termone:has_one',
        'termanies'      => 'Termany:has_many',
        'termany2manies' => 'Termany2many:has_many'
    ];

}