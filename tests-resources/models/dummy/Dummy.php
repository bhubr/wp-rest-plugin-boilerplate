<?php
namespace bhubr;

class Dummy extends Post_Model {
    static $type = 'post';

    static $singular = 'dummy';
    static $plural = 'dummies';

    static $name_s = 'Dummy';
    static $name_p = 'Dummies';

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
        'dumbmany2manies' => 'Dumbmany2many:has_many',
        'dummyterms' => 'DummyTerm:has_many',
        // 'prout' => 'Kikou:has_many'
    ];

}