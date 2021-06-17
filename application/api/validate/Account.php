<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: Yida
 * +----------------------------------------------------------------------
 * | DateTime: 2021/1/25 14:07
 * +----------------------------------------------------------------------
 */

namespace app\api\validate;

use think\Validate;

class Account extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'mobile'     => 'require',
        'captcha'    => 'require',
        'password'   => 'require',
        'repassword' => 'require|confirm:password',
        'old_captcha' => 'require'
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'mobile.require'     => 'Mobile_require',
        'captcha.require'    => 'Captcha_require',
        'password.require'   => 'Password_require',
        'repassword.require' => 'Repassword_require',
        'repassword.confirm' => 'Repassword_confirm',
        'old_captcha.require' => 'Old_captcha_require',
    ];


    /**
     * 验证场景
     */
    protected $scene = [
        'login' => ['mobile', 'password'],
        'register' => ['mobile', 'captcha', 'password', 'repassword'],
        'resetpwd' => ['mobile', 'captcha', 'password', 'repassword'],
        'changemobile' => ['mobile', 'captcha','old_captcha'],
    ];


}

