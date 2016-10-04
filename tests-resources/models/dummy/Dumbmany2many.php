<?php
namespace bhubr;
use bhubr\REST\Model\Post;

class Dumbmany2many extends Post {
    static $type = 'post';

    static $singular = 'dumbmany2many';
    static $plural = 'dumbmany2manies';

    static $name_s = 'Dumbmany2many';
    static $name_p = 'Dumbmany2manies';

    static $fields = [
        'dumb_str'  => 'string'
    ];
    static $relations = [
        'dummies'    => 'Dummy:has_many'
    ];
}