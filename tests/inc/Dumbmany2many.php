<?php
namespace bhubr;

class Dumbmany2many extends Post_Model {
    static $type = 'post';
    static $singular = 'dumbmany2many';
    static $plural = 'dumbmany2manies';
    static $fields = [
        'dumb_str'  => 'string'
    ];
    static $relations = [
        'dummies'    => 'Dummy:has_many'
    ];
}