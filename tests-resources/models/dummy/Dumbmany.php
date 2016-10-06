<?php
namespace bhubr;
use bhubr\REST\Model\Post;

class Dumbmany extends Post {
    static $type = 'post';

    static $singular = 'dumbmany';
    static $plural = 'dumbmanies';

    static $name_s = 'Dumbmany';
    static $name_p = 'Dumbmanies';

    static $fields = [
        'dumb_str'  => 'string',
        // 'dummy_id'  => 'Dummy:belongs_to'
    ];
    static $relations = [
        'dummy'    => 'bhubr\Dummy:belongs_to'
    ];
}