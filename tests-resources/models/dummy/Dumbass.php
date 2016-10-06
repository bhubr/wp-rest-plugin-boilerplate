<?php
namespace bhubr;
use bhubr\REST\Model\Post;

class Dumbass extends Post {
    static $type = 'post';

    static $singular = 'dumbass';
    static $plural = 'dumbasses';

    static $name_s = 'Dumbass';
    static $name_p = 'Dumbasses';

    static $fields = [
        'dumb_str'  => 'string'
    ];
    static $relations = [
        'mydummy'    => 'bhubr\Dummy:belongs_to'
    ];

}