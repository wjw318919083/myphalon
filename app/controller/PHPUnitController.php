<?php

/**
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author zhanglibo <zhanglibo@xiaohe.com>
 */
class  PHPUnitController extends \Phalcon\Mvc\Controller
{
    public function Equals()
    {
        return 'Equals';
    }

    public function HasKey()
    {
        return ['hasKey' => 'hasValue'];
    }

    public function NotHasKey()
    {
        return ['hasKey' => 'hasValue'];
    }

    public function NotEmpty(){
        return 'NotEmpty';
    }

    public function IsEmpty(){
        return '';
    }

    public function IsTrue(){
        return true;
    }
    public function isFalse(){
        return false;
    }
}