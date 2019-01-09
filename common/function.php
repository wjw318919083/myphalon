<?php
/**
 * 公共方法
 */


/**
 * @param bool $status
 * @param string $msg
 * @param null $result
 */
function dataReturn($status = true, $msg = 'API_COMM_001', $result = null)
{
    $tip = require ROOT.'conf/tip.php';

    $result = [
        'status'        => $status,
        //接口操作状态
        'type'          => 'json',
        //数据交互类型
        'code'          => $msg,
        //接口错误码
        'code_msg'      => $tip[$msg][0],
        //操作错误信息
        'code_user_msg' => $tip[$msg][1],
        //用户提示信息
        'result'        => empty($result) ? '' : $result,//接口返回结果集
    ];

    // 返回结果放到redis,防止重复请求
    $redis = connRedis();
//    $redis->select(5);

    $cache_key           = md5(json_encode($_REQUEST));
    $cache_data          = $result;
    $cache_data['cache'] = 'redis';
    $redis->setex($cache_key, 1, json_encode($cache_data));

    DLOG('return:'.json_encode($result));
    exit(json_encode($result));
}

/*
 * 获取配置文件
 *
 * @param string $keys 获取配置key
 * @param string $file_name 获取配置文件名称 列：conf.php 传递conf 不带后缀名
 *
 * @return 返回获取内容
 * */
function getConfig($keys = '', $file_name = '')
{
    if ($keys == '')
    {
        return '';
    }

    if ($file_name == '')
    {
        $path = ROOT.'conf/conf.php';
    } else
    {
        $path = ROOT.'conf/'.$file_name.'.php';
    }

    $conf = new Phalcon\Config\Adapter\Php($path);
    $conf = $conf->toArray();

    $key_arr = explode(".", $keys);
    if (count($key_arr) > 1)
    {
        $value = $conf;
        for ($i = 0; $i < count($key_arr); $i++)
        {
            $tmp = getArrVal($value, $key_arr[$i], '');
            if ($tmp == '')
            {
                break;
            } else
            {
                $value = $tmp;
            }
        }

        $result = $value;
    } else
    {
        $result = getArrVal($conf, $key_arr[0], '');
    }

    if (is_object($result))
    {
        return (array)$result;
    } else
    {
        return $result;
    }
}


/**
 * 获取数组里的值
 * @param  array $arr 数组
 * @param  mixed $key 键名
 * @param  mixed $default 默认值
 * @return mixed
 */
function getArrVal($arr, $key, $default = '')
{
    if (!isset($arr[$key]))
    {
        return $default;
    }
    $data = $arr[$key];
    switch (strtolower(getType($data)))
    {
        case 'boolean':
        case 'null':
        case 'object':
        case 'resource':
            return $data;
            break;
        case 'array':
            return (empty($data) ? $default : $data);
            break;
        default:
            $data = trim($data);

            return (strlen($data) ? addslashes($data) : $default);
            break;
    }

    return $default;
}

/**
 * 记录日志
 * @param string $log_content 要调试的数据
 * @param string $log_level 日志级别(ERROR:执行错误日志 WARN:警告日志 INFO:交互信息日志 DEBUG:调试日志)
 * @param string $file_name 记录日志文件
 */
function DLOG($log_content = '', $log_level = 'INFO', $file_name = 'debug.log')
{
    if (is_array($log_content) || is_object($log_content))
    {
        $log_content = json_encode($log_content);
    }

    $log_level_arr = [
        'ERROR',
        'WARN',
        'INFO',
        'DEBUG',
    ];
    if ($log_content == '')
    {
        return;
    }
    if (!in_array($log_level, $log_level_arr))
    {
        return;
    }

    $log_path       = ROOT.'tmp'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.date('Y').DIRECTORY_SEPARATOR;
    $log_path       .= date('m').DIRECTORY_SEPARATOR.date('d').DIRECTORY_SEPARATOR;
    $time           = sprintf("%8s.%03d", date('H:i:s'), floor(microtime() * 1000)); //请求时间精确到毫秒
    $ip             = sprintf("%15s", get_client_ip(0, true)); //获取客户端IP地址
    $request_uri    = $_SERVER['REQUEST_URI']; //请求uri
    $content_prefix = "[ ".$time." ".trim($ip)." ".$log_level." ".$request_uri." ] "; //日志前缀
    $content_suffix = "[ ".getmypid()." ]"; //日志后缀
    $file_path      = sprintf('%s%s', $log_path, $file_name); //日志写入地址

    if (!file_exists(dirname($file_path)))
    {
        mkdir(dirname($file_path), 0755, true);
    }

    $fp = fopen($file_path, 'a+');
    fwrite($fp, $content_prefix.$log_content.$content_suffix."\n");
    fclose($fp);

    return;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return string
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = null;

    if ($ip !== null)
    {
        return $ip[$type];
    }

    if ($adv)
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);

            if (false !== $pos)
            {
                unset($arr[$pos]);
            }

            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR']))
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? [
        $ip,
        $long,
    ] : [
        '0.0.0.0',
        0,
    ];

    return $ip[$type];
}

/**
 * 返回UUID
 * @return mixed
 * @throws \Phalcon\Security\Exception
 */
function getUuid()
{
    $random = new Phalcon\Security\Random();

    return str_replace('-', '', $random->uuid());
}

/*
 * 添加缓存
 * @param string $key 缓存key
 * @param string $value 缓存value
 * @return void
 * */
function addCache($key = '', $value = '')
{
    if ($key == '' || $value == '')
    {
        return;
    }

    $cacheKey = $key.'.cache';

    $frontCache = new Phalcon\Cache\Frontend\Data(["lifetime" => 3600,]);
    $cache      = new Phalcon\Cache\Backend\File($frontCache, ["cacheDir" => ROOT."tmp/cache/",]);

    $cache->save($cacheKey, $value);
}

/*
 * 获取缓存
 * @param string $key 缓存key
 * @return cache obj
 * */
function getCache($key = '')
{
    if ($key == '')
    {
        return;
    }

    $cacheKey = $key.'.cache';

    $frontCache = new Phalcon\Cache\Frontend\Data(["lifetime" => 3600,]);
    $cache      = new Phalcon\Cache\Backend\File($frontCache, ["cacheDir" => ROOT."tmp/cache/",]);

    $value = $cache->get($cacheKey);

    if ($value === null)
    {
        return '';
    }

    return $value;
}

/*
 * 删除缓存
 * @param string $key 缓存key
 * @return bool
 * */
function delCache($key = '')
{
    if ($key == '')
    {
        return false;
    }

    $cacheKey = $key.'.cache';

    $frontCache = new Phalcon\Cache\Frontend\Data(["lifetime" => 3600,]);
    $cache      = new Phalcon\Cache\Backend\File($frontCache, ["cacheDir" => ROOT."tmp/cache/",]);

    if ($cache->exists($cacheKey))
    {
        $cache->delete($cacheKey);
    }

    return true;
}

/*
 * 连接redis
 * @param void
 * @return conn
 * */
function connRedis()
{
    $object = new \Phalcon\Config\Adapter\Php(ROOT.'conf'.DIRECTORY_SEPARATOR.'redis.php');
    $redis  = $object->toArray();
    $client = new \Predis\Client($redis['parameter'], $redis['options']);
    return $client;
}

/**
 * 名称首字母排序
 * @param $string
 * @return string
 */
function getFirstCharter($string)
{
    if (empty($string))
    {
        return '';
    }
    $fchar = ord($string{0});
    if ($fchar >= ord('A') && $fchar <= ord('z'))
    {
        return strtoupper($string{0});
    }
    $s1  = iconv('UTF-8', 'gb2312', $string);
    $s2  = iconv('gb2312', 'UTF-8', $s1);
    $s   = $s2 == $string ? $s1 : $string;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284)
    {
        return 'A';
    }
    if ($asc >= -20283 && $asc <= -19776)
    {
        return 'B';
    }
    if ($asc >= -19775 && $asc <= -19219)
    {
        return 'C';
    }
    if ($asc >= -19218 && $asc <= -18711)
    {
        return 'D';
    }
    if ($asc >= -18710 && $asc <= -18527)
    {
        return 'E';
    }
    if ($asc >= -18526 && $asc <= -18240)
    {
        return 'F';
    }
    if ($asc >= -18239 && $asc <= -17923)
    {
        return 'G';
    }
    if ($asc >= -17922 && $asc <= -17418)
    {
        return 'H';
    }
    if ($asc >= -17417 && $asc <= -16475)
    {
        return 'J';
    }
    if ($asc >= -16474 && $asc <= -16213)
    {
        return 'K';
    }
    if ($asc >= -16212 && $asc <= -15641)
    {
        return 'L';
    }
    if ($asc >= -15640 && $asc <= -15166)
    {
        return 'M';
    }
    if ($asc >= -15165 && $asc <= -14923)
    {
        return 'N';
    }
    if ($asc >= -14922 && $asc <= -14915)
    {
        return 'O';
    }
    if ($asc >= -14914 && $asc <= -14631)
    {
        return 'P';
    }
    if ($asc >= -14630 && $asc <= -14150)
    {
        return 'Q';
    }
    if ($asc >= -14149 && $asc <= -14091)
    {
        return 'R';
    }
    if ($asc >= -14090 && $asc <= -13319)
    {
        return 'S';
    }
    if ($asc >= -13318 && $asc <= -12839)
    {
        return 'T';
    }
    if ($asc >= -12838 && $asc <= -12557)
    {
        return 'W';
    }
    if ($asc >= -12556 && $asc <= -11848 || $asc == -6704)
    {
        return 'X';
    }
    if ($asc >= -11847 && $asc <= -11056)
    {
        return 'Y';
    }
    if ($asc >= -11055 && $asc <= -10247)
    {
        return 'Z';
    }

    return "#";
}


/**
 * 替换unicode转义
 * @param $match
 * @return string
 */
function replace_unicode_escape_sequence($match)
{
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}

/**
 * 将Unicode编码转换成可以浏览的utf-8编码
 * @param $str
 * @return mixed
 * @author zhanglibo <zhanglibo@xiaohe.com>
 */
function unicode_decode($str)
{
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为true 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param integer $flags htmlspecialchars flags
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
{
    $label = (null === $label) ? '' : rtrim($label).':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (!extension_loaded('xdebug'))
    {
        $output = htmlspecialchars($output, $flags);
    }
    $output = '<pre>'.$label.$output.'</pre>';
    if ($echo)
    {
        echo($output);

        return;
    } else
    {
        return $output;
    }
}

function array2string($data, $symbol = "'")
{
    if (empty($data))
    {
        return '';
    }
    foreach ($data as &$value)
    {
        $value = "{$symbol}{$value}{$symbol}";
    }

    return join(',', $data);
}


/**
 * 日志
 * phalcon
 * @param $log_content
 * @param string $level
 * @param string $file_name
 */
function ilog($log_content, $level = 'info', $file_name = "logger.log")
{
    $log_level = strtolower($level);
    if (is_array($log_content) || is_object($log_content))
    {
        $log_content = print_r($log_content, true);
    }
    $log_content = (string)$log_content;
    if ($log_content == '')
    {
        return;
    }
    $log_path  = ROOT.'tmp'.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.date('Y').DIRECTORY_SEPARATOR;
    $log_path  .= date('m').DIRECTORY_SEPARATOR.date('d').DIRECTORY_SEPARATOR;
    $file_path = sprintf('%s%s', $log_path, $file_name); //日志写入地址

    if (!file_exists(dirname($file_path)))
    {
        mkdir(dirname($file_path), 0755, true);
    }
    $logger = new \Phalcon\Logger\Adapter\File($file_path);  //初始化文件地址
    $format = new Phalcon\Logger\Formatter\Line();

    $trace    = debug_backtrace();
    $line     = current($trace)['line'];
    $trace    = next($trace);
    $function = $trace['class'].":".$trace['function'];
    $format->setDateFormat('Y-m-d H:i:s');
    $micro = sprintf("%.3d", floor(microtime() * 1000));
    $format->setFormat("[%date%.{$micro}][{$function}][line:{$line}][%type%]-[%message%]");

    $logger->setFormatter($format);
    $logger->$log_level($log_content);

}
/*
 * 读取表映射。
 *
 * @param string $key 待读取的键名
 */
function rdbmap($key)
{
    $di    = \Phalcon\DI::getDefault();
    $dbmap = $di->getShared('dbmap');
    if (empty($key)) {
        return $dbmap->toArray();
    }
    if (strpos($key, '.') === false) {
        return $dbmap->get($key);
    }
    list($prev, $next) = explode('.', $key);
    return $dbmap->get($prev)
                 ->get($next);
}

/**
 * 获取数据库名
 *
 * @param  string $key dbmap key
 * @return string
 */
function getDbName($key)
{
    return rdbmap($key.'.database');
}

/**
 * 获取数据库表名
 *
 * @param  string $key dbmap key
 * @return string
 */
function getTbName($key)
{
    return rdbmap($key.'.string');
}

/**
 * 获取模型对象
 *
 * @param  string $key dbmap key
 * @return object
 */
function getModel($key)
{
    $class_name = rdbmap($key.'.object');
    return  new $class_name;
}

function getDbTbName($key)
{
    $db_name = getDbName($key);
    if (empty($db_name)) {
        return '';
    }
    $tb_name = getTbName($key);
    if (empty($tb_name)) {
        return '';
    }
    return "{$db_name}.{$tb_name}";
}

function cache_write($filename,$values,$var='rows',$format=false){
    $cachefile=$filename;
    $cachetext="<?php\r\n".$var.' '.arrayeval($values,$format).";";
    return writefile($cachefile,$cachetext);
}

function arrayeval($array,$format=false,$level=0){
    $space=$line='';
    if(!$format){
        for($i=0;$i<=$level;$i++){
            $space.="\t";
        }
        $line="\n";
    }
    $evaluate='['.$space.$line;
    $comma=$space;
    foreach($array as $key=> $val){
        $key=is_string($key)?'\''.addcslashes($key,'\'\\').'\'':$key;
        $val=!is_array($val)&&(!preg_match('/^\-?\d+$/',$val)||strlen($val) > 12)?'\''.addcslashes($val,'\'\\').'\'':$val;
        if(is_array($val)){
            $evaluate.=$comma.$key.'=>'.arrayeval($val,$format,$level+1);
        }else{
            if(trim($key,'\'')=='object'&&trim($val,'\'')){
                $evaluate.=$comma.$key.'=>'.trim($val,'\'')."::class";
            }
            else{
            $evaluate.=$comma.$key.'=>'.$val;
            }
        }
        $comma=','.$line.$space;
    }
    $evaluate.=$line.$space.']';
    return $evaluate;
}

//写入文件
function writefile($filename,$writetext,$openmod='w'){
    if(false!==$fp=fopen($filename,$openmod)){
        flock($fp,2);
        fwrite($fp,$writetext);
        fclose($fp);
        return true;
    }else{
        return false;
    }
}

function humpWord($word){
    $array = explode('_', $word);
    array_walk($array, function (&$value)
    {
        $value = ucfirst($value);
    });
    return  join('', $array);
}

