<?php
namespace bhubr;

class Termany2many extends Term_Model {
    static $type = 'term';
    static $post_type = 'dummy';

    static $singular = 'termany2many';
    static $plural = 'termany2manies';

    static $name_s = 'Termany2many';
    static $name_p = 'Termany2manies';

    static $fields = [
        'dumb_str'  => 'string'
    ];
    static $relations = [
        'dummyterms'    => 'DummyTerm:has_many'
    ];
}