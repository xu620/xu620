<?php

namespace app\common\model\general;

use think\Model;


class ReceiveBank extends Model
{

    // 表名
    protected $name = 'receive_bank';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
