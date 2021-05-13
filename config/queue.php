<?php

return [
    'default' => 'qless',

    'connections' => [
        'qless' => [
            'driver' => 'qless',
            'connection' => 'qless',
            'queue' => 'default',
            'redis_connection' => ['qless1', 'qless2'],
        ],
    ],
];
