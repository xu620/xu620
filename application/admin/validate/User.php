<?php

namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'nickname' => 'require',
        'password' => 'regex:\S{6,32}',
        'mobile'   => 'unique:user'
    ];

    /**
     * 字段描述
     */
    protected $field = [
    ];
    /**
     * 提示消息
     */
    protected $message = [
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => ['nickname', 'password', 'mobile'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'nickname' => __('Nickname'),
            'password' => __('Password'),
            'mobile'   => __('Mobile')
        ];
        parent::__construct($rules, $message, $field);
    }

}
