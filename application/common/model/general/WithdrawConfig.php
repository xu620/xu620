<?php

namespace app\common\model\general;

use think\Model;


class WithdrawConfig extends Model
{
    // 表名
    protected $name = 'withdraw_config';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    protected $type = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
        'min_money'  => 'float',
        'handling_fee_rate' => 'float',
        'service_fee_rate'  => 'float',
    ];





}
