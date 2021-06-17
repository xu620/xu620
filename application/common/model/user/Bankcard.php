<?php
/**
 * +----------------------------------------------------------------------
 * | zaihukeji [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 http://icarexm.com/ All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: MrXu
 * +----------------------------------------------------------------------
 */
namespace app\common\model\user;

use think\Model;


class Bankcard extends Model
{
    // 表名
    protected $name = 'bankcard';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = false;
    const is_default_0 = 0; //不默认
    const is_default_1 = 1; //默认

    // 追加属性
    protected $append = [

    ];
}