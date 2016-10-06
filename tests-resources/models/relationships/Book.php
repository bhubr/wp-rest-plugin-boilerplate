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
    static $map_fields = ['post_title' => 'title', 'post_content' => 'summary'];
    static $relations = [
        'author'    => 'rel\Person:belongs_to:mybooks',
        'owner'     => 'rel\Person:belongs_to:bookshelf'
    ];

}