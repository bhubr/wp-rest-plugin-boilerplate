<?php
namespace bhubr;
use bhubr\REST\Model\Post;

class Dummy extends Post {
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
        // 'dumbass_id'    => 'Dumbass:has_one'
    ];
    static $relations = [
        'mydumbass'    => 'bhubr\Dumbass:has_one',
        'dumbmanies' => 'bhubr\Dumbmany:has_many',
        'dumbmany2manies' => 'bhubr\Dumbmany2many:has_many',
        'dummyterms' => 'bhubr\DummyTerm:has_many',
    ];

}