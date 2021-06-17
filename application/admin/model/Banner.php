<?php

namespace app\admin\model;

use app\common\model\Banner as Model;

class Banner extends Model
{
    // 追加属性
    protected $append = [
        'status_text'
    ];
    
    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            if(isset($row['weigh']) && $row['weigh'] == 0)
                $row->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getTypeList()
    {
        return ['1' => __('Type 1'), '2' => __('Type 2')];
    }

    public function getJumpTypeList()
    {
        return ['0' => __('Jump_type 0'), '1' => __('Jump_type 1'), '2' => __('Jump_type 2')];
    }


}
