<?php

namespace app\common\model\user;

use think\Model;


class LevelLog extends Model
{

    protected $pk = 'log_id';
    // 表名
    protected $name = 'user_level_log';
    
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
