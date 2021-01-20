<?php
/**
 * Redis 集群配置
 */
return [];
return [
    // Redis 服务器地址。
    'parameter' => [
        'tcp://192.168.1.10:6380',
        'tcp://192.168.1.10:6381',
        'tcp://192.168.1.10:6382',
    ],

    // Redis 配置。
    'options' => [
        'cluster' => 'redis',
        'parameters' => [
            'password' => NULL,
        ],
    ],
];