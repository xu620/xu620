<?php

namespace app\api\service;

use app\api\service\user\UserService;
use think\facade\Env;
use think\Model;

/**
 * 短信验证码
 * Class SmsService
 * @package app\api\service
 */
class SmsService Extends Model
{
    /**
     * 验证码有效时长
     * @var int
     */
    protected $expire = 120;
    /**
     * 最大允许检测的次数
     * @var int
     */
    protected $maxCheckNums = 10;

    protected $name = 'sms';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $auto = ['ip'];
    protected function setIpAttr()
    {
        return request()->ip();
    }

    /**
     * 发送验证码
     *
     * @param $mobile
     * @param $event
     *
     * @return bool
     */
    public function sendCode($mobile, $event)
    {
        $last = self::detail($mobile, $event);
        if ($last && time() - $last['create_time'] < 60) {
            $this->error = __('Send code frequently');
            return false;
        }

        if ($event) {
            $userInfo = UserService::getByMobile($mobile);
            if(in_array($event, ['login', 'check', 'resetpwd']) && !$userInfo){
                // 未注册
                $this->error = __('Mobile not register');
                return false;
            } elseif (in_array($event, ['register', 'bind']) && $userInfo) {
                // 已被注册
                $this->error = __('Mobile already exist');
                return false;
            }
        }

        $code = mt_rand(100000, 999999);
        $data = [
            'event'   => $event,
            'mobile'  => $mobile,
            'content' => '',
            'data'    => [
                'code' => $code
            ]
        ];

        $service = new \sms\SmsService();
        $result = $service->sendCode($data);
        if ($result) {
            self::create($data);
            return true;
        }else{
            //$service->getError();
            $this->error = __('Send code failed');
            return false;
        }
    }

    /**
     * 发送通知
     *
     * @param mixed $mobile 手机号,多个以,分隔
     * @param array $msg  消息内容
     * @param null $event 消息模板事件
     *
     * @return  boolean
     */
    public function sendNotice($mobile, $msg = [], $event = null)
    {
        $data = [
            'mobile'   => $mobile,
            'msg'      => $msg,
            'event'    => $event
        ];

        $service = new \sms\SmsService();
        $result = $service->sendNotice($data);
        if ($result) {
            return true;
        }else{
            //$service->getError();
            $this->error = __('Send code failed');
            return false;
        }
    }

    /**
     * 校验验证码
     *
     * @param int $mobile   手机号
     * @param int $code     验证码
     * @param string $event 事件
     *
     * @return  boolean
     */
    public function checkCode($mobile, $code, $event = 'default')
    {
        if ($event) {
            $userInfo = UserService::getByMobile($mobile);
            if(in_array($event,['check', 'resetpwd']) && !$userInfo){
                $this->error = __('Mobile not register');
                return false;
            } elseif (in_array($event, ['register', 'bind'])  && $userInfo) {
                //已被注册
                $this->error = __('Mobile already exist');
                return false;
            }
        }

        //TODO 测试环境万能验证码
        if(Env::get('app.debug') && $code == 888888){
            return true;
        }

        $sms = self::detail($mobile, $event);
        if ($sms) {
            $time = time() - $this->expire;
            if ($sms['create_time'] > $time && $sms['times'] <= $this->maxCheckNums) {
                if ($code == $sms['code']) {
                    return true;
                }

                $sms->times = $sms->times + 1;
                $sms->save();
                $this->error = __('Captcha is incorrect');
            } else {
                // 过期则清空该手机验证码
                $this->flush($mobile, $event);
                $this->error = __('Captcha is expired');
            }
        }else{
            $this->error = __('Get captcha first');
        }

        return false;
    }

    /**
     * 清空指定手机号验证码
     *
     * @param int $mobile 手机号
     * @param string $event 事件
     *
     * @return bool
     */
    public static function flush($mobile, $event = 'default')
    {
        return self::destroy(['mobile' => $mobile, 'event' => $event]);
    }

    /**
     * 短信详情
     *
     * @param $mobile
     * @param $event
     *
     * @return SmsService
     */
    public static function detail($mobile, $event)
    {
        return self::where(['mobile' => $mobile, 'event' => $event])->order('id', 'DESC')->find();
    }
}
