<?php
/**
 * Token Model
 *
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author haojiaxin <haojiaxin@xiaohe.com>
 */

use Phalcon\Mvc\Model;

class HistoryHearModel extends Model
{
    public function initialize()
    {
        // $this->useDynamicUpdate(true); // 就是它，神奇的方法
        $this->setSource(getTbName('xs_history_hears'));     //模型对应的表名
        $this->setReadConnectionService('eduwork_slave');     //从库
        $this->setWriteConnectionService('eduwork_master');   //主库
    }
}
