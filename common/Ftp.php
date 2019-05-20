<?php
/**
 * +---------------------------------------------------------------------------
 * | [Ftp]组件
 * +---------------------------------------------------------------------------
 * | FtpComponent.php
 * +---------------------------------------------------------------------------
 * | @package /at.cms/Controller/Component
 * | @author Yun <majinyun@juren.com>
 * +---------------------------------------------------------------------------
 */
class Ftp  {

    public $linkTime; // 链接时间

    private $link; // 链接标识
    private $errorCode = 0; // 错误代码

    /**
     * 链接服务器
     *
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @param string $host 服务器地址
    +----------------------------------------------------------
     * @param string $user 用户名
    +----------------------------------------------------------
     * @param string $password 密码
    +----------------------------------------------------------
     * @param string $port 端口，默认为21
    +----------------------------------------------------------
     * @param int $timeout 超时时间
    +----------------------------------------------------------
     * @return boolean
    +----------------------------------------------------------
     */
    public function connect($host, $user = '', $password = '', $port = '21', $timeout = 30) {
        $start = time();
        try{
            if(!($this->link = @ftp_connect($host, $port, $timeout))){
                $this->errorCode = 1;
                return false;
            }
            if(ftp_login($this->link, $user, $password)){
                ftp_pasv($this->link, true);
                $this->linkTime = date('s', time() - $start);
                return true;
            }
        }catch(Exception $e){
            $this->errorCode = 1;
            return false;
        }
        //打开被动模式，数据的传送由客户机启动，而不是由服务器开始
        // ftp_pasv($this->link, true);
        // register_shutdown_function(array(&$this, 'close'));
    }

    /**
     * 获取错误信息
     *
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return string
    +----------------------------------------------------------
     */
    public function getError() {
        if (!$this->errorCode) return false;
        $errorMsg = array(
            '1' => 'Server can not connect',
            '2' => 'Not connect to server',
            '3' => 'Can not delete non-empty folder',
            '4' => 'Can not delete file',
            '5' => 'Can not get file list',
            '6' => 'Can not change the current directory on the server',
            '7' => 'Can not upload files'
        );
        return $errorMsg[$this->errorCode];
    }

    /**
     * 创建目录并将目录定位到当请目录
     *
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @param string $dirPath 目录路径
    +----------------------------------------------------------
     * @return mixed
    +----------------------------------------------------------
     */
    public function makeDir($dirPath){
        //连接标识
        $connect = $this->link;
        //目录处理
        $dirPath = str_replace('\\', '/', $dirPath);
        $dirPath = '/' . trim($dirPath, '/');
        //如果目录存在返回true
        if(@ftp_chdir($connect, $dirPath)) return true;
        //创建不存在目录
        $dirPath = explode('/', $dirPath);
        if(!is_array($dirPath) || empty($dirPath)) return false;
        $tmpPath = '';
        foreach ($dirPath as $dir){
            if(empty($dir)) continue;
            $tmpPath .= '/' . $dir;
            //创建目录 更改权限 切换目录
            if(!@ftp_chdir($connect, $tmpPath)){
                if(!@ftp_mkdir($connect, $tmpPath)) return false;
                if(!@ftp_chmod($connect, 0777, $tmpPath)) return false;
            }
            if(!@ftp_chdir($connect, $tmpPath)) return false;
        }
        return true;
    }

    /**
     * 上传文件
     *
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @param resource $connect 链接标示
    +----------------------------------------------------------
     * @param string $local 本地路径
    +----------------------------------------------------------
     * @param string $remote 远程路径
    +----------------------------------------------------------
     * @return int
     *       1、目录创建失败
     *       2：文件上传失败
     *       3：上传成功
    +----------------------------------------------------------
     */
    public function upload($local, $remote){
        $dirPath = dirname($remote);
        if(!$this->makeDir($dirPath)) return 1;
        $result = ftp_put($this->link, basename($remote), $local, FTP_BINARY);
        //返回结果
        return (!$result) ? 2 : 3;
    }

    /**
     * 关闭链接
     *
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * @return void
    +----------------------------------------------------------
     */
    public function close() {
        return @ftp_close($this->link);
    }
}
