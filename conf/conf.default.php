<?php
/**
 * 配置文件
 *
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author gaoxiang <gaoxiang@xiaohe.com>
 */
return [
    /**
     * 是否开启监测数据库sql
     * @var false=不监测 true=监测
     * @descript 开启监测db，监测日志记录至 /tmp/log/ 目录下execute_sql.log文件
     */
    'LISTEN_DB' => false,

    /**
     * 是否开启token验证
     * @var false=不开启 true=开启
     * @descript 默认不开启
     */
    'IS_TOKEN' => false,

    /**
     * 是否开始签名验证
     * @var false=不开启 true=开启
     * @descript 默认开启
     */
    'IS_SIGN'  => false,

    /**
     * 平台授权码
     * @var 平台key => 安全码
     * @descript 开启签名验证时生效
     */
    'AUTH_PLATFORM'     => [
        //研发
        '45e26caab96f241fd0684f31fda84232' => '#3EyVbq][2QbuOFOip=(-EM]Rzr,o1)=eHXhlJg28stS~276.LEipk#9AM^2cg*4',
        //ios
        '4f38198093f02f7329b347520c07264a' => '#3EyVbq][2QbuOFOip=(-EM]Rzr,o1)=eHXhlJg28stS~276.LEipk#9AM^2cg*4',
        //安卓
        '1a010f7115400c6d7a29d21c10c6a61a' => '#3EyVbq][2QbuOFOip=(-EM]Rzr,o1)=eHXhlJg28stS~276.LEipk#9AM^2cg*4',
    ],

    'IMG' => 'http://file.baonahao.com.cn', // 文件服务器地址
];
