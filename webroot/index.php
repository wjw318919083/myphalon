<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;

require dirname(__DIR__) . '/vendor/autoload.php';

try
{
    $di = new FactoryDefault();

    // 常量
    define('ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

    // 公共函数文件
    require ROOT . 'vendor/autoload.php';
    require ROOT . 'common/function.php';

    // 数据库连接池
    require ROOT . 'conf/dbpool.php';

    $loader = new Loader();
    $loader->registerDirs(
        [
            ROOT . 'app/Controller/',
            ROOT . 'app/Logic/',
            ROOT . 'app/Model/',
            ROOT . 'common/',
            ROOT . 'conf',
        ]
    );
    $loader->registerNamespaces([
        'app\model' => ROOT . 'app/model/',
        'App\Common' => ROOT . 'common/',
    ]);
    $loader->register();

    if (empty($_REQUEST))
    {
        dataReturn(false, 'API_COMM_404');
    }

    //阻止应用重复请求接口
    try
    {
        $redis = connRedis();
        $redis->select(5);

        $cache_key = md5(json_encode($_REQUEST));
        if ($redis->exists($cache_key))
        {
            for ($i = 0; $i < 20; $i++)
            {
                if (!$redis->exists($cache_key))
                {
                    break;
                }

                $cacheData = $redis->get($cache_key);
                if (!empty($cacheData) && ($cacheData != 'none'))
                {
                    if ($redis->ttl($cache_key) < 0)
                    {
                        $redis->delete($cache_key);
                    }
                    exit($cacheData);
                }
                usleep(100000);
            }
        } else
        {
            $redis->setex($cache_key, 10, 'none');
        }
    } catch (\Exception $ex)
    {
        DLOG($ex->getMessage(), 'ERROR', 'redis.log');
    }

    $app = new Micro();

    $request = $app->request;
    $uri     = trim($request->getURI(), '/');
    if (empty($uri))
    {
        dataReturn(false, 'API_COMM_404');
    }
    $uri_arr    = explode("/", $uri);
    $func       = end($uri_arr);
    $action     = prev($uri_arr);
    $controller = $action . 'Controller';

    $myController   = new $controller();
    $request_method = $app->request->getMethod();
    if ($request_method == 'POST')
    {
        $app->post(
            "/$action/$func", [$myController, $func,]
        );
    } else
    {
        $app->get(
            "/$action/$func", [$myController, $func,]
        );
    }

    // 404
    $app->notFound(
        function () use ($app)
        {
            dataReturn(false, 'API_COMM_404');
        }
    );

    $app->handle();
} catch (\Exception $e)
{
    // 异常处理
    // echo "PhalconException: ", $e->getMessage();
    $err_msg = sprintf('File:%s Line:%s Info:%s', $e->getFile(), $e->getLine(), $e->getMessage());
    DLOG($err_msg, 'ERROR', 'exception.log');

    dataReturn(false, 'API_COMM_004', $e->getMessage());
}
