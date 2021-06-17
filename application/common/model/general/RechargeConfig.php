<?php

namespace app\common\model\general;

use think\Model;


class RechargeConfig extends Model
{

    // 表名
    protected $name = 'recharge_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
