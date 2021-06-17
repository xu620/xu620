<?php

namespace app\common\model;

use think\Model;


class Navigation extends Model
{

    // 表名
    protected $name = 'navigation';
    
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

    public function getJumpTypeList()
    {
        return ['1' => __('Jump_type 1'), '2' => __('Jump_type 2')];
    }
    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }

}
