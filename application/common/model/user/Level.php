<?php

namespace app\common\model\user;

use think\Model;


class Level extends Model
{

    protected $pk = 'level_id';
    // 表名
    protected $name = 'user_level';
    
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
        'min_amount' => 'float',
        'max_amount' => 'float',
        'commission_rate' => 'float',
    ];
    

    







}
