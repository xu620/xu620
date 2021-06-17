<?php

namespace app\common\model\general;

use think\Model;


class Avatar extends Model
{
    // 表名
    protected $name = 'system_avatar';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            if(isset($row['weigh']) && $row['weigh'] == 0){
                $row->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
            }
        });
    }

    







}
