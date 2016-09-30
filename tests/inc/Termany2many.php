<?php
namespace bhubr;

class Termany2many extends Term_Model {
    static $type = 'term';
    static $singular = 'termany2many';
    static $plural = 'termany2manies';
    static $fields = [
        'dumb_str'  => 'string'
    ];
    static $relations = [
        'dummyterms'    => 'DummyTerm:has_many'
    ];
}