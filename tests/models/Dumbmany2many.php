<?php
namespace bhubr;

class Dumbmany2many extends Post_Model {
    static $type = 'post';
    static $post_type = 'dummy';

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