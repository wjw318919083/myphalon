<?php

class JPushUtil
{

    protected $app_key;

    protected $master_secret;

    public function __construct($merchant_id)
    {
        $config = $this->getConf($merchant_id);
        if (empty($config))
        {
            dataReturn(false, 'API_PUSH_001');
        }
        $this->app_key       = getArrVal($config, 'app_key');
        $this->master_secret = getArrVal($config, 'master_secret');

    }

    /**
     * 获取机构推送配置
     * @param $merchant_id
     * @return array|mixed
     */
    protected function getConf($merchant_id)
    {
        $config = getConfig("MERCHANT_PUSH", "pushConfig");
        $config = getArrVal($config, $merchant_id);
        if (empty($config))
        {
            $base_model = new \BcAppMerchantBaseConfModel();
            $base       = $base_model->getList(['merchant_id' => $merchant_id])['data'];
            if ($base)
            {
                foreach ($base as &$val)
                {
                    if ($val['conf_key'] == 'app_key')
                    {
                        $app_key = $val['conf_val'];
                    }
                    if ($val['conf_key'] == 'master_secret')
                    {
                        $master_secret = $val['conf_val'];
                    }
                }
                if (!empty($app_key) && !empty($master_secret))
                {
                    $config = [
                        'app_key'       => $app_key,
                        'master_secret' => $master_secret,
                    ];
                }
            }
        }

        return $config;
    }

    /**
     * 推送
     * @param $data
     */
    public function push($data)
    {
        //参数
        $title           = getArrVal($data, 'title');
        $content         = getArrVal($data, 'content');
        $alert           = getArrVal($data, 'alert');
        $all_powerful_id = getArrVal($data, 'merchant_id');
        $push_type       = getArrVal($data, 'push_type'); // 推送类型(1:课程 2:考勤 3:课次评价)
        $app_type        = getArrVal($data, 'app_type');  // 应用类型(1:家长端 2:机构端)
        $alias           = getArrVal($data, 'alias');     // 准备发送的人员member_id 或parent_id
        $extras_jg       = getArrVal($data, 'extras_jiguang');
        $extras          = getArrVal($data, 'extras');
        $creator_id      = getArrVal($data, 'creator_id'); // 会员或家长ID
        $created         = date('Y-m-d H:i:s');

        $data    = [
            'title'   => $title,
            'content' => $content,
            'alert'   => $alert,
            'alias'   => $alias,
            'extras'  => $extras_jg,
        ];
        $sendall = $this->sendAllDevice($data);

        //记录消息内容
        $push_msg_log_model = new PushMessageLogModel();
        $status             = $sendall['http_code'] == 200 ? 1 : 2;

        $apml['app_type']        = $app_type;
        $apml['push_type']       = $push_type;
        $apml['push_title']      = $title;
        $apml['push_content']    = $content;
        $apml['push_alert']      = $alert;
        $apml['push_extras']     = $extras;
        $apml['user_id']         = $alias[0];
        $apml['status']          = $status;
        $apml['created']         = $created;
        $apml['creator_id']      = $creator_id;
        $apml['app_key']         = $this->app_key;
        $apml['master_secret']   = $this->master_secret;
        $apml['creator_id']      = $creator_id;
        $apml['all_powerful_id'] = $all_powerful_id;

        $push_msg_log_model->add($apml);
    }


    /**
     * 向所有设备推送消息
     * @param $message
     * @return array|\JPush\PushPayload
     */
    public function sendNotifyAll($message)
    {
        $client   = new \JPush\Client($this->app_key, $this->master_secret);
        $push     = $client->push();
        $response = $push->setPlatform('all')->addAllAudience()->setNotificationAlert($message);

        try
        {
            $response = $response->send();
        } catch (\JPush\Exceptions\APIConnectionException $e)
        {
            return $data = ['http_code' => 'error'];
        } catch (\JPush\Exceptions\APIRequestException $e)
        {
            return $data = ['http_code' => 'error'];
        }

        return $response;

    }


    /**
     * 特定设备推送相同消息
     * @param $regid 特定设备的设备标识
     * @param $message 需要推送的消息
     * @return array|\JPush\PushPayload
     */
    function sendNotifySpecial($regid, $message)
    {
        $client   = new \JPush\Client($this->app_key, $this->master_secret);
        $push     = $client->push();
        $response = $push->setPlatform('all')->addRegistrationId($regid)->setNotificationAlert($message);

        try
        {
            $response = $response->send();
        } catch (\JPush\Exceptions\APIConnectionException $e)
        {
            return $data = ['http_code' => 'error'];
        } catch (\JPush\Exceptions\APIRequestException $e)
        {
            return $data = ['http_code' => 'error'];
        }

        return $response;
    }

    /**
     * 所有设备推送，多条信息
     * @param $data
     * @return array|\JPush\PushPayload
     */
    public function sendAllDevice($data)
    {
        $base64 = base64_encode("$this->app_key:$this->master_secret");
        $header = ["Authorization:Basic $base64", "Content-Type:application/json"];

        $device  = getArrVal($data, 'device', 'all');//设备 ios Android WinPhone
        $title   = getArrVal($data, 'title', '');//推送标题
        $content = getArrVal($data, 'content', '');//推送内容
        $alert   = getArrVal($data, 'alert', '');//表示通知内容
        $m_time  = getArrVal($data, 'm_time', 86400);
        $alias   = getArrVal($data, 'alias', []);//数组多个用户UID
        $extras  = getArrVal($data, 'extras', []);//内容数组形式


        $client           = new \JPush\Client($this->app_key, $this->master_secret);
        $push             = $client->push();

        $ios_notification = [
            'sound'             => '',//表示通知提示声音，默认填充为空字符串
            'badge'             => '+1',//应用角标 为 0 表示清除，支持 '+1','-1'
            'content-available' => true,//表示推送唤醒
            'mutable-content'   => true,
            'category'          => '',
            'extras'            => $extras,
        ];

        $android_notification = [
            'title'      => $title,//表示通知标题
            'builder_id' => 2,//表示通知栏样式 ID
            'style'      => 1,
            'extras'     => $extras,
        ];

        $message   = [
            'title'        => $title,
            'content_type' => 'text',
            'extras'       => $extras,
        ];
        $options   = [
            'time_to_live'    => $m_time,//表示离线消息保留时长(秒)
            'apns_production' => false  //true 表示推送生产环境，False 表示要推送开发环境
        ];
        $alert_ios = [
            'body'  => $alert,
            'title' => $title,
        ];//ios消息体

        $alert_android = $alert;
        $response      = $push->setPlatform($device)
                              ->addAlias($alias)
                              ->iosNotification($alert_ios, $ios_notification)
                              ->androidNotification($alert_android, $android_notification)
                              ->message($content, $message)
                              ->options($options);
        try
        {
            $response = $response->send();
            var_dump($response);
        } catch (\JPush\Exceptions\APIConnectionException $e)
        {
            var_dump($e->getMessage());
            return $data = ['http_code' => 'error'];
        } catch (\JPush\Exceptions\APIRequestException $e)
        {
            var_dump($e->getMessage());

            return $data = ['http_code' => 'error'];
        }

        return $response;
    }

    /**
     * 各类统计数据
     * @param $msgIds 推送消息返回的msg_id列表
     * @return array
     */
    function reportNotify($msgIds)
    {
        $client   = new \JPush\Client($this->app_key, $this->master_secret);
        $response = $client->report()->getReceived($msgIds);

        return $response;
    }

}