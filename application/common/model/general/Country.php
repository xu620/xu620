<?php

namespace app\common\model\general;

use think\Model;


class Country extends Model
{

    // 表名
    protected $name = 'country';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function receive()
    {
        return $this->belongsTo(ReceiveBank::class, 'receive_bank_id','id')->setEagerlyType(0);
    }

    public function recharge()
    {
        return $this->belongsTo(RechargeConfig::class, 'recharge_config_id','id')->setEagerlyType(0);
    }

    public function withdraw()
    {
        return $this->belongsTo(WithdrawConfig::class, 'withdraw_config_id','id')->setEagerlyType(0);
    }


}
