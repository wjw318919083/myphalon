<?php

use Phalcon\Mvc\Model;

class McMemberModel extends Model
{
    public function initialize()
    {
        // $this->useDynamicUpdate(true); // 就是它，神奇的方法
        $this->setSource(getTbName('hy_members'));     //模型对应的表名
        $this->setReadConnectionService('edubase_slave');     //从库
        $this->setWriteConnectionService('edubase_master');   //主库

        $this->hasOne('id', 'McEmployeeModel', 'member_id', ['alias' => 'McEmployee']);
    }
}
