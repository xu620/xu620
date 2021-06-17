<?php

namespace app\admin\model;

use app\common\model\User as Model;

class User extends Model
{
    // 追加属性
    protected $append = [
        'prev_time_text',
        'login_time_text',
    ];

    protected static function init()
    {
        self::beforeUpdate(function ($row) {
            $changed = $row->getChangedData();
            //如果有修改密码
            if (isset($changed['password'])) {
                if ($changed['password']) {
                    $salt = \fast\Random::alnum();
                    $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changed['password'], $salt);
                    $row->salt = $salt;
                } else {
                    unset($row->password);
                }
            }
        });
    }

    public function getGenderList()
    {
        return ['1' => __('Gender 1'), '2' => __('Gender 2'), '0' => __('Gender 0')];
    }

    public function getStatusList()
    {
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }

    public function getPrevTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['prev_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    public function getLoginTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['login_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPrevTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setLoginTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

}
