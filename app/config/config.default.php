<?php
/**
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author zhanglibo <zhanglibo@xiaohe.com>
 */
return new \Phalcon\Config(array(
    'database' => array(
        'adapter'     => 'Mysql',
        'host'        => '127.0.0.1',
        'username'    => 'root',
        'password'    => 'root',
        'dbname'      => 'test',
        'charset'     => 'utf8',
    ),
    'application' => array(
        'controllersDir' => __DIR__ . '/../../app/controller/',
        'modelsDir'      => __DIR__ . '/../../app/model/',
        'migrationsDir'  => __DIR__ . '/../../app/migrations/',
        'viewsDir'       => __DIR__ . '/../../app/views/',
        'pluginsDir'     => __DIR__ . '/../../app/plugins/',
        'libraryDir'     => __DIR__ . '/../../app/library/',
        'cacheDir'       => __DIR__ . '/../../app/cache/',
        'baseUri'        => '/my_phalcon/',
    )
));
