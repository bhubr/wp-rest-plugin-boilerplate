<?php
namespace bhubr;

class Dummy extends Post_Model {
    static $singular = 'dummy';
    static $plural = 'dummies';
    static $fields = [
        'type'          => 'string',
        'status'        => 'string',
        'dummy_int'     => 'integer',
        'dummy_str'     => 'string',
        'dumbass_id'    => 'Dumbass:has_one'
    ];
    static $relations = [
        'dumbass'    => 'Dumbass:has_one',
        'dumbmanies' => 'Dumbmany:has_many',
        'dumbmany2manies' => 'Dumbmany2many:has_many'
    ];

}