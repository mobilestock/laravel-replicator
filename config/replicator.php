<?php

return [
    [
        'columns' => [
            'COLUMN_PRIMARY_NODE' => 'COLUMN_SECONDARY_NODE',
        ],
        'node_primary' => [
            'database' => env('DB_DATABASE'),
            'table' => 'TABLE_NAME_PRIMARY_NODE',
            'reference_key' => 'REFERENCE_KEY_PRIMARY_NODE',
        ],
        'node_secondary' => [
            'database' => env('DB_NAME_NODE_SECONDARY'),
            'table' => 'TABLE_NAME_SECONDARY_NODE',
            'reference_key' => 'REFERENCE_KEY_SECONDARY_NODE',
        ],
    ],
];
