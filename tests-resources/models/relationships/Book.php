<?php
namespace rel;
use bhubr\REST\Model\Post;

class Book extends Post {
    static $type = 'post';

    static $singular = 'book';
    static $plural = 'books';

    static $name_s = 'Book';
    static $name_p = 'Books';

    static $fields = [
        'title'        => [
            'type'     => 'string',
            'required' => 'true'
        ],
        'summary'      => [
            'type'     => 'string'
        ],
        'pub_year'     => [
            'type'     => 'integer'
        ]
    ];
    static $relations = [
        'author'    => 'rel\Person:belongs_to:mybooks',
    ];

}