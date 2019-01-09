<?php

class Upload
{
    private static $log_path = ROOT . 'tmp' . DIRECTORY_SEPARATOR . 'logs';


    /**
     * 上传图片
     * @param $data
     * @return bool|mixed|string
     */
    public function uploadImage($data)
    {
        $file_str = getArrVal($data, 'file');

        if (empty($file_str))
        {
            return false;
        }
        if (strlen($file_str) <= 256)
        {
            return $file_str;
        }
        //上传配置
        $dir_name = 'tpjx';
        //本地生成文件
        $dir = DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR;
        $local_path = self::$log_path . sprintf($dir, date('Y'), date('m'), date('d'));
        if (!file_exists($local_path))
        {
            mkdir($local_path, 0755, true);
        }
        $file_path = $local_path . uniqid() . '.png';
        $file_str = base64_decode($file_str);
        file_put_contents($file_path, $file_str);
        //同步至文件服务器
        $params = ['app' => $dir_name, 'file' => $file_path, 'type' => 'image', 'exts' => '.png|.gif|.jpg|.jpeg|.bmp', 'size' => '8',];

        return $this->syncFileServer($params);
    }

    /**
     * 上传图片
     * @param $tmp_name
     * @return bool|mixed
     */
    public function uploadImg($tmp_name)
    {
        if (empty($tmp_name))
        {
            return false;
        }
        //上传配置
        $dir_name = 'tpjx';
        //本地生成文件
        $dir = DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR . "%s" . DIRECTORY_SEPARATOR;
        $local_path = self::$log_path . sprintf($dir, date('Y'), date('m'), date('d'));
        if (!file_exists($local_path))
        {
            mkdir($local_path, 0755, true);
        }

        $fp = fopen($tmp_name, 'rb');
        $bin = fread($fp, 2); //只读2字节
        fclose($fp);
        $strInfo = @unpack('C2chars', $bin);
        $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
        $type['255216'] = '.jpg';
        $type['7173'] = '.gif';
        $type['6677'] = '.bmp';
        $type['13780'] = '.png';
        if (empty($type[$typeCode]))
        {
            return false;
        }
        $ext = $type[$typeCode];
        $file_path = $local_path . uniqid() . $ext;
        move_uploaded_file($tmp_name, $file_path);

        //同步至文件服务器
        $params = ['app' => $dir_name, 'file' => $file_path, 'type' => 'image', 'exts' => '.png|.gif|.jpg|.jpeg|.bmp', 'size' => '8',];

        return $this->syncFileServer($params);
    }


    /**
     * 同步至文件服务器
     * @param $params
     * @return mixed
     */
    public function syncFileServer($params)
    {
        $config = getConfig('IMG');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $file = curl_file_create($params['file']);
        $data = ['method' => 'php', //标识PHP方式上传文件
                 'app'    => $params['app'], //应用名简写[jwb，zjjz，ddyx，bnh，lt，hy]
                 'file'   => $file, //文件绝对路径，注意在路径前加'@'
                 'type'   => $params['type'], //类型[image，doc，video]
                 'exts'   => $params['exts'], //扩展名，用'|'隔开
                 'size'   => $params['size'], //大小，单位是MB
                 'secret' => 'dXBsb2FkX2FwaV9zZWNyZXRfa2V5', //秘钥，base64_encode('upload_api_secret_key')
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);
        //删除本地临时生成文件
        @unlink($params['file']);

        return json_decode($result, true);
    }

}