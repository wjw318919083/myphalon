<?php
/**
 * 数据库连接池
 *
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author gaoxiang <gaoxiang@xiaohe.com>
 */
use Phalcon\Config\Adapter\Ini as ConfigIni;
$config = new ConfigIni(ROOT . 'conf' . DIRECTORY_SEPARATOR . 'db.ini');
// 爱校自己的业务数据库
$di->setShared('ix_eduwork_master', function() use ($config) {
    return connectDb($config, 'eduwork', 'master');
});
$di->setShared('ix_eduwork_slave', function() use ($config) {
    return connectDb($config, 'eduwork', 'slave');
});
// 配置私有化部署的机构的数据库连接信息
// $GLOBALS['merchant_id'] = 'aj2389sdfjkdlsfn589dsfjsdjf898u3294n';
if (isset($GLOBALS['merchant_id']) && !empty($GLOBALS['merchant_id'])) {
    $redis = connRedis();
    $value = $redis->get('privatecloud:' . $GLOBALS['merchant_id']);
    if (!empty($value) && ($value != 'Array')) {
        $value = json_decode($value, true);
        if (is_array($value)) {
            $GLOBALS['privatecloud_dbname']   = $value['master']['dbname'];
            // 配置文件中原eduwork_master更新成机构的配置
            $config->eduwork_master->host     = $value['master']['host'];
            $config->eduwork_master->port     = $value['master']['port'];
            $config->eduwork_master->dbname   = $value['master']['dbname'];
            $config->eduwork_master->username = $value['master']['username'];
            $config->eduwork_master->password = $value['master']['password'];
            // 配置文件中原eduwork_slave更新成机构的配置
            $config->eduwork_slave->host      = $value['master']['host'];
            $config->eduwork_slave->port      = $value['master']['port'];
            $config->eduwork_slave->dbname    = $value['master']['dbname'];
            $config->eduwork_slave->username  = $value['master']['username'];
            $config->eduwork_slave->password  = $value['master']['password'];
        }
    }
}
// 基础数据库
$di->setShared('edubase_master', function() use ($config) {
    return connectDb($config, 'edubase', 'master');
});
$di->setShared('edubase_slave', function() use ($config) {
    return connectDb($config, 'edubase', 'slave');
});
// 业务数据库【如果重新设置了eduwork配置信息，那么就是连接的机构自己的数据库】
$di->setShared('eduwork_master', function() use ($config) {
    return connectDb($config, 'eduwork', 'master');
});
$di->setShared('eduwork_slave', function() use ($config) {
    return connectDb($config, 'eduwork', 'slave');
});
// 日志数据库
$di->setShared('edulogs_master', function() use ($config) {
    return connectDb($config, 'edulogs', 'master');
});
$di->setShared('edulogs_slave', function() use ($config) {
    return connectDb($config, 'edulogs', 'slave');
});
// 表映射关系注入 Di 容器。
$di->setShared('dbmap', function(){
    $config = require ROOT . 'conf/dbmap.php';
    if (isset($GLOBALS['privatecloud_dbname']) && !empty($GLOBALS['privatecloud_dbname'])) {
        function reset_dbname(&$conf_val, $conf_key){
            if ($conf_key == 'eduwork') {
                foreach ($conf_val as $key => $value) {
                    $conf_val[$key]['database'] = $GLOBALS['privatecloud_dbname'];
                }
            }
        }
        array_walk($config, "reset_dbname");
    }
    $config = array_merge($config['edubase'], $config['eduwork'], $config['edulogs']);
    $config = new \Phalcon\Config($config);
    return $config;
});
// 数据库密码解密
function connectDbDecrypt($data, $key) {
    $key  = sha1(md5($key));
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $x    = 0;
    $char = $str = '';
    $l    = strlen($key);
    $len  = strlen($data);
    for ($i=0; $i<$len; $i++) {
        if ($x == $l) {
            $x = 0;
        }
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i=0; $i<$len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    $data   = base64_decode($str);
    $expire = substr($data, 0, 10);
    if($expire > 0 && $expire < time()) {
        return '';
    }
    return json_decode(substr($data, 10), true);
}
// 连接数据库
function connectDb($db_config, $db_name, $db_type)
{
    if ($db_type == 'master') {
        $db_node   = $db_name . '_master';
        $db_config = $db_config->$db_node;
    } else {
        $db_node   = $db_name . '_slave';
        $db_config = $db_config->$db_node;
    }
    $events_manager = new Phalcon\Events\Manager();
    // 监听数据库事件
    if (getConfig('LISTEN_DB')) {
        $profiler = new Phalcon\Db\Profiler();
        $events_manager->attach('db', function($event, $connection) use ($profiler) {
            //一条语句查询之前事件，profiler开始记录sql语句
            if ($event->getType() == 'beforeQuery') {
                $profiler->startProfile($connection->getSQLStatement());
            }
            //一条语句查询结束，结束本次记录，记录结果会保存在profiler对象中
            if ($event->getType() == 'afterQuery') {
                $profiler->stopProfile();
                $profile     = $profiler -> getLastProfile();
                $sql         = $profile->getSQLStatement();
                $executeTime = $profile->getTotalElapsedSeconds();
                DLOG('执行时间:' . $executeTime . ' SQL语句:' . $sql, 'INFO', 'execute_sql.log');
            }
        });
    }
    // 数据库连接处修改
    $password = $db_config->password;
    $pwd_key  = substr($password, -32);
    $pwd_val  = substr($password, 0, strlen($password) - 32);
    $connection = new Phalcon\Db\Adapter\Pdo\Mysql([
        'host'     => $db_config->host,
        'port'     => $db_config->port,
        'username' => $db_config->username,
        'password' => connectDbDecrypt($pwd_val, $pwd_key),
        'dbname'   => $db_config->dbname,
    ]);
    $connection->setEventsManager($events_manager);
    return $connection;
}