<?php
namespace bhubr;

class Dumbmany extends Post_Model {
    static $singular = 'dumbmany';
    static $plural = 'dumbmanies';
    static $fields = [
        'dumb_str'  => 'string',
        'dummy_id'  => 'Dummy:belongs_to'
    ];
    static $relations = [
        'dummy'    => 'Dummy:belongs_to'
    ];
}