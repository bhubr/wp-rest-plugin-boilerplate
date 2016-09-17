<?php
return [
    'types' => [
        'foo' => [  // key is type name (singular, lower-case)
            'name_s'   => 'Foo',  // label (singular)
            'fields'   => ['foo_type', 'foo_number'],
            'taxonomies' => [
                'foo_cat' => [
                    'name_s' => 'Foo Cat',
                    'fields' => ['baaar', 'caaat']
                ],
                'foo_tag' => [
                    'name_s' => 'Foo Tag',
                    'fields' => ['foooo', 'taaag']
                ]
            ]
        ]
    ]
];