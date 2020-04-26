<?php

use Phalcon\Mvc\Model;

class McParentMarketInfosModel extends Model
{
    public function initialize()
    {
        // $this->useDynamicUpdate(true); // 就是它，神奇的方法
        $this->setSource(getTbName('xs_parent_market_infos'));     //模型对应的表名
        $this->setReadConnectionService('eduwork_slave');     //从库
        $this->setWriteConnectionService('eduwork_master');   //主库
    }
}
