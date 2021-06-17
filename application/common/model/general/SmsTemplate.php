<?php

namespace app\common\model\general;

use think\Model;


class SmsTemplate extends Model
{

    // 表名
    protected $name = 'sms_template';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


    /**
     * 获取模板详情
     */
    public static function detail($country_id, $type)
    {
        return self::get(['country_id' => $country_id, 'type' => $type]);
    }







}
