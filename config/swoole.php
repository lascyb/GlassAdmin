<?php

return [
    'http' => [
        'enable' => true,
        'host' => '0.0.0.0',
        'port' => 8080,
        // 'worker_num' => swoole_cpu_num(),
       'worker_num' => env('APP_DEBUG', false) ? 1 : swoole_cpu_num(),
        /**
         * 静态资源：头像等在 public/storage 下，须由 Swoole 直接读盘返回，否则只会进路由 404。
         * 生产环境若 Nginx 已 location /storage/ 反代到磁盘，可关掉 enable_static_handler，仅由 Nginx 提供静态文件。
         */
        'options' => [
            'document_root'         => root_path('public'),
            'enable_static_handler' => true,
        ],
    ],
    'websocket' => [
        'enable' => false,
        'route' => true,
        'handler' => \think\swoole\websocket\Handler::class,
        'ping_interval' => 25000,
        'ping_timeout' => 60000,
        'room' => [
            'type' => 'table',
            'table' => [
                'room_rows' => 8192,
                'room_size' => 2048,
                'client_rows' => 4096,
                'client_size' => 2048,
            ],
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_active' => 3,
                'max_wait_time' => 5,
            ],
        ],
        'listen' => [],
        'subscribe' => [],
    ],
    'rpc' => [
        'server' => [
            'enable' => false,
            'host' => '0.0.0.0',
            'port' => 9000,
            'worker_num' => swoole_cpu_num(),
            'services' => [],
        ],
        'client' => [],
    ],
    //队列
    'queue' => [
        'enable' => false,
        'workers' => [],
    ],
    'hot_update' => [
        'enable' => env('APP_DEBUG', false),
        'name' => ['*.php'],
        'include' => [root_path("app"),root_path("extend"),root_path("config"),root_path("route")],
        'exclude' => [],
    ],
    //连接池
    'pool' => [
        'db' => [
            'enable' => true,
            'max_active' => 3,
            'max_wait_time' => 5,
        ],
        'cache' => [
            'enable' => true,
            'max_active' => 3,
            'max_wait_time' => 5,
        ],
        //自定义连接池
    ],
    'ipc' => [
        'type' => 'unix_socket',
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'max_active' => 3,
            'max_wait_time' => 5,
        ],
    ],
    //锁
    'lock' => [
        'enable' => false,
        'type' => 'table',
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'max_active' => 3,
            'max_wait_time' => 5,
        ],
    ],
    'tables' => [],
    //每个worker里需要预加载以共用的实例
    'concretes' => [],
    //重置器
    'resetters' => [],
    //每次请求前需要清空的实例
    'instances' => [],
    //每次请求前需要重新执行的服务
    'services' => [],
];
