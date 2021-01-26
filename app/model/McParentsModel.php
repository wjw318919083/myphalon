<?php

use Phalcon\Mvc\Model;

class McParentsModel extends Model
{
    public function initialize()
    {
        // $this->useDynamicUpdate(true); // 就是它，神奇的方法
        $this->setSource(getTbName('xs_parents'));     //模型对应的表名
        $this->setReadConnectionService('eduwork_slave');     //从库
        $this->setWriteConnectionService('eduwork_master');   //主库

        $this->hasOne('id', 'McParentMarketInfosModel', 'clue_id', ['alias' => 'McParentMarketInfos']);
        
        $this->hasMany('id', 'HistoryHearModel', 'clue_id', ['alias' => 'HistoryHear']);
    }
}
