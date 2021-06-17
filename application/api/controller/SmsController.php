<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020  All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: Yida
 * +----------------------------------------------------------------------
 * | DateTime: 2021/01/18 01:16
 * +----------------------------------------------------------------------
 */

namespace app\api\controller;

use app\common\controller\Api;
use app\api\service\SmsService;
use think\facade\Env;
use think\facade\Validate;

/**
 * 手机短信接口
 * Class SmsController
 * @package app\api\controller
 */
class SmsController extends Api
{
    protected $noNeedLogin = ['sendCode', 'checkCode'];
    protected $noNeedRight = '*';

    /**
     * @api {post} /api/sms/sendCode  发送验证码
     * @apiName send
     * @apiGroup 短信管理
     * @apiVersion 0.0.0
     * @apiDescription  发送验证码
     *
     * @apiHeader {String} [token]   令牌
     *
     * @apiParam {String} mobile    手机号
     * @apiParam {String} event     事件名称 login登陆 register注册 bind绑定手机号 check验证身份 resetpwd重置密码
     *
     * @apiSuccess {Number} code  返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     */
    public function sendCode()
    {
        $mobile = $this->request->param("mobile");
        $event  = $this->request->param("event",'login');
        if (!Validate::mobile($mobile)) {
            $this->error(__('Mobile is incorrect'));
        }

        //TODO 测试环境假发送
        if(Env::get('app.debug')){
            $this->success( '发送成功，验证码：888888');
        }

        $sms = new SmsService();
        $result = $sms->sendCode($mobile, $event);
        if($result)
            $this->success( __('Send code successful'));

        $error = $sms->getError() ?: __('Send code failed');
        $this->error($error);
    }

    /**
     * @api {post} /api/sms/checkCode 验证验证码
     * @apiName checkCode
     * @apiGroup 短信管理
     * @apiVersion 0.0.0
     * @apiDescription 验证验证码
     *
     * @apiParam {Number} mobile  手机号
     * @apiParam {String} event   事件名称 check验证身份 resetpwd重置密码
     * @apiParam {String} captcha 验证码
     *
     * @apiSuccess {Number} code  返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     *
     */
    public function checkCode()
    {
        $mobile  = $this->request->param("mobile");
        $event   = $this->request->param("event");
        $captcha = $this->request->param("captcha");
        if(!Validate::mobile($mobile)){
            $this->error(__('Mobile is incorrect'));
        }

        if(!$captcha){
            $this->error(__('Captcha can not be empty'));
        }

        $sms = new SmsService();
        $result = $sms->checkCode($mobile, $captcha, $event);
        if($result){
            $this->success(__('Check successful'));
        }

        $error = $sms->getError() ?: __('Captcha is incorrect');
        $this->error($error);
    }

}
