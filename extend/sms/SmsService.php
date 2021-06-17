<?php

namespace sms;

use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use think\Exception;
use think\facade\Env;
use think\facade\Config;

/**
 * 短信类
 * Class SmsService
 * @package sms
 */
class SmsService
{

    protected $config;
    protected $error;

    public function __construct()
    {
        $config = Config::pull('site');
        $this->config = [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,
            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,
                // 默认可用的发送网关
                'gateways' => [$config['sms_platform']],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => Env::get('runtime_path') . 'log/sms/'.date('Ym'). DIRECTORY_SEPARATOR. date('d').'.log',
                ],
                'aliyun' => [
                    'access_key_id'     => $config['sms_appid'],
                    'access_key_secret' => $config['sms_secret'],
                    'sign_name'         => $config['sms_sign'],
                ],
                'qcloud' => [
                    'sdk_app_id' => $config['sms_appid'],   // SDK APP ID
                    'app_key'    => $config['sms_secret'],  // APP KEY
                    'sign_name'  => $config['sms_sign'],    // 短信签名，如果使用默认签名，该字段可缺省（对应官方文档中的sign）
                ],
            ]
        ];
    }

    /**
     * 发送短信验证码
     *
     * @param array $params ['mobile'=>'xxx','event' => 'login','content' => '', 'data' => ['code' => 1111]]
     *
     * @return  boolean
     */
    public function sendCode($params)
    {
        $config = Config::pull('site');
        if($config['is_open_sms'] != 1){
            //没有启动短信功能
            return false;
        }

        $easySms = new EasySms($this->config);
        try {
            $templateID = $config['sms_template'][$params['event']];
            $result = $easySms->send($params['mobile'], [
                'content'  => $params['content'],
                'template' => $templateID,
                'data'     => $params['data'],
            ]);

            $rsp = $result[$config['sms_platform']];
            if ($rsp['status'] == 'success') {
                return true;
            } else {
                //记录错误信息
                $this->error = $rsp;
                return false;
            }
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
        } catch (NoGatewayAvailableException $e) {
            $this->error = $e->getResults();
        } catch (Exception $e){
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * 发送短信通知
     *
     * @param array $params ['mobile'=>'xxx,xxx', 'msg'=>'明天开会','event' => 'login']
     *
     * @return  boolean
     *
     */
    public function sendNotice($params)
    {
        $config = Config::pull('site');
        if($config['is_open_sms'] != 1){
            //没有启动短信功能
            return false;
        }

        $easySms = new EasySms($this->config);
        try {
            $templateID = $config['sms_template'][$params['event']];
            $result = $easySms->send($params['mobile'], [
                'template' => $templateID,
                'data' => $params['msg'],
            ]);
            $rsp = $result[$config['sms_platform']];
            if ($rsp['status'] == 'success') {
                return true;
            } else {
                //记录错误信息
                $this->error = $rsp;
                return false;
            }
        } catch (NoGatewayAvailableException $e) {
            $this->error = $e->getResults();
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
        } catch (Exception $e){
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->error;
    }
}
