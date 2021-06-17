<?php

namespace wechat;

use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\DecryptException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use EasyWeChat\Kernel\Http\StreamResponse;
use GuzzleHttp\Exception\GuzzleException;
use Overtrue\Socialite\Exceptions\AuthorizeFailedException;
use think\Exception;
use think\facade\Config;
use think\facade\Env;

/**
 * 微信管理类
 * Class Wechat
 * @package wechat
 */
class WechatService
{
    private $app;
    private $error;

    /**
     * 构造方法
     *
     * @param string $type - 类型 officialAccount公众号 miniProgram小程序 payment支付
     */
    public function __construct(string $type = 'miniProgram')
    {
        $config = [
            // 使用 ThinkPHP 的缓存系统
            'use_tp_cache'  => true,
            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file'  => env('runtime_path') . '/log/wechat/' . date('Ym') . '/' . date('d') . '.log',
            ],
        ];
        switch ($type){
            case 'payment':
                // 微信支付
                $config['app_id'] = Config::get('site.wechat_payment_app_id');
                $config['mch_id'] = Config::get('site.wechat_payment_mch_id');
                $config['key']    = Config::get('site.wechat_payment_key');
                // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
                $config['cert_path'] = Env::get('root_path') . '/public' . Config::get('site.wechat_cert_path');
                $config['key_path']  = Env::get('root_path') . '/public' . Config::get('site.wechat_key_path');
                $config['notify_url'] = Config::get('site.wechat_notify_url');
                $this->app = Factory::payment($config);
                break;
            case 'miniProgram':
                // 小程序
                $config['app_id'] = Config::get('site.wechat_mini_program_app_id');
                $config['secret'] = Config::get('site.wechat_mini_program_secret');
                $this->app = Factory::miniProgram($config);
                break;
            case 'officialAccount':
                // 公众号
                $config['app_id'] = Config::get('site.wechat_official_account_app_id');
                $config['secret'] = Config::get('site.wechat_official_account_secret');
                $this->app = Factory::officialAccount($config);
                break;
        }
    }

    /**
     * 获取用户信息 (公众号和APP端)
     *
     * @param string $code
     *
     * @return array
     */
    public function getUserInfo(string $code)
    {
        try {
            $user = $this->app->oauth->userFromCode($code);
            $userInfo = $user->getRaw();

            return [
                'openid'   => $userInfo['openid'],
                'nickname' => $this->filter_emoji($userInfo['nickname']),
                'gender'   => $userInfo['sex'],
                'avatar'   => $userInfo['headimgurl'],
                'unionid'  => isset($userInfo['unionid']) ? $userInfo['unionid'] : ''
            ];

        } catch (AuthorizeFailedException $e) {
            // $e->getMessage();
        }

        return [];
    }

    /**
     * 获取公众号授权回调地址
     *
     * @param string $redirect 回调地址
     *
     * @return string
     */
    public function getRedirect(string $redirect)
    {
        return $this->app->oauth->scopes(['snsapi_userinfo'])->redirect($redirect);
    }

    /**
     * 获取JSSDK的配置数组
     *
     * @param string $url 设置当前URL
     *
     * @return array
     */
    public function jssdk(string $url = '')
    {
        try {
            return $this->app->jssdk->setUrl($url)->buildConfig(['getLocation', 'scanQRCode', 'chooseWXPay'], false, false, false);
        } catch (InvalidArgumentException $e) {
        } catch (InvalidConfigException $e) {
        } catch (RuntimeException $e) {
        } catch (GuzzleException $e) {
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
        }
        return [];
    }

    /**
     * 获取小程序用户信息
     *
     * @param $sessionKey
     * @param $iv
     * @param $encryptedData
     *
     * @return array
     */
    public function getMiniProgramUserInfo($sessionKey, $iv, $encryptedData)
    {
        $userInfo = $this->decryptData($sessionKey, $iv, $encryptedData);
        if($userInfo){
            return [
                'nickname' => $this->filter_emoji($userInfo['nickName']),
                'avatar'   => htmlspecialchars(strip_tags($userInfo['avatarUrl'])),
                'gender'   => $userInfo['gender'],
                'openid'   => $userInfo['openId'],
                'unionid'  => isset($userInfo['unionId']) ? $userInfo['unionId'] : ''
            ];
        }
        return [];
    }

    /**
     * 获取小程序 session_key
     *
     * @param $code
     *
     * @return array|mixed
     */
    public function sessionKey(string $code)
    {
        /**
         * code 换取 session_key
         * ​这是一个 HTTPS 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。
         * 其中 session_key 是对用户数据进行加密签名的密钥。为了自身应用安全，session_key 不应该在网络上传输。
         */
        try{
            $result = $this->app->auth->session($code);
            if (isset($result['openid']) && isset($result['session_key'])) {
                cache($result['openid'], $result['session_key']);
                return ['openid' => $result['openid']];
            }
        } catch (InvalidConfigException $e){
            $this->error = $e->getMessage();
            return false;
        }

        return false;
    }

    /**
     * 小程序消息解密
     *
     * @param $session
     * @param $iv
     * @param $encryptedData
     *
     * @return array|bool
     */
    public function decryptData($session, $iv, $encryptedData)
    {
        try{
            return $this->app->encryptor->decryptData($session, $iv, $encryptedData);
        }catch (DecryptException $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 获取小程序码 （适用于需要的码数量较少的业务场景）
     *
     * @param string $path - 小程序路径
     * @param string $dir -  保存本地路径
     * @param string $file_name - 文件名
     * @param array $optional 可选参数
     * @description optional 列表:
     *      width Int - 默认 430 二维码的宽度
     *      auto_color 默认 false 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
     *      line_color 数组，auto_color 为 false 时生效，使用 rgb 设置颜色 例如 ，示例：["r" => 0,"g" => 0,"b" => 0]
     *
     *  @return bool|int|string
     */
    public function getAppCode(string $path, string $dir, string $file_name, array $optional = [])
    {
        $response = $this->app->app_code->get($path, $optional);
        if ($response instanceof StreamResponse) {
            try {
                return $response->saveAs($dir, $file_name);
            } catch (InvalidArgumentException $e) {
            } catch (RuntimeException $e) {
            }
        }
        return false;
    }

    /**
     * 获取小程序码 （适用于需要的码数量极多，或仅临时使用的业务场景）
     *
     * @param string $scene - 场景（参数）
     * @param string $dir -  保存本地路径
     * @param string $file_name - 文件名
     * @param array $optional 可选参数
     * @description optional 列表:
     *      page 已经发布的小程序存在的页面
     *      width 二维码的宽度，单位 px，最小 280px，最大 1280px
     *      auto_color 默认 false 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
     *      line_color 数组，auto_color 为 false 时生效，使用 rgb 设置颜色 例如 ，示例：["r" => 0,"g" => 0,"b" => 0]
     *      is_hyaline 是否需要透明底色，为 true 时，生成透明底色的小程序
     *
     * @return false|string
     */
    public function getUnAppCode(string $scene, string $dir, string $file_name, array $optional = [])
    {
        $response = $this->app->app_code->getUnlimit($scene, $optional);
        // 保存小程序码到文件
        if ($response instanceof StreamResponse) {
            try {
                return $response->saveAs($dir, $file_name);
            } catch (InvalidArgumentException $e) {
            } catch (RuntimeException $e) {
            }
        }
        return false;
    }

    /**
     * 获取小程序二维码
     *
     * @param string $path - 已经发布的小程序存在的页面
     * @param string $dir - 保存本地路径
     * @param string $file_name - 文件名
     * @param int|null $width - 二维码的宽度
     *
     * @return bool|int|string
     */
    public function getQrCode(string $path, string $dir, string $file_name, int $width = null)
    {
        $response = $this->app->app_code->getQrCode($path, $width);
        // 保存小程序码到文件
        if ($response instanceof StreamResponse) {
            try {
                return $response->saveAs($dir, $file_name);
            } catch (InvalidArgumentException $e) {
            } catch (RuntimeException $e) {
            }
        }
        return false;
    }

    /**
     * 统一下单支付
     *
     * @param float $amount - 订单金额
     * @param string $order_no - 订单号
     * @param string $body - 内容
     * @param string $trade_type - 支付类型 JSAPI -小程序支付 APP -APP支付 NATIVE -Native支付 MWEB -H5支付
     * @param string $notify_url - 回调地址
     * @param string $openid - openid
     * @param string $attach - 附加数据
     *
     * @return array|false|string
     */
    public function unify($amount, $order_no, $body = '支付', $trade_type = 'JSAPI', $notify_url = null, $openid = null, $attach = null)
    {
        $params = [
            'out_trade_no'     => $order_no,        //你的订单号
            'body'             => $body,
            'total_fee'        => $amount * 100,    //单位分
            'notify_url'       => $notify_url,
            'attach'           => $attach,
            'openid'           => $openid,
            'spbill_create_ip' => request()->ip(),
            'trade_type'       => $trade_type,
        ];
        try {
            $result = $this->app->order->unify($params);
            if($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS"){
                // 根据支付方式不同，返回不同参数
                switch ($trade_type){
                    case 'JSAPI':
                        // 小程序支付
                        return $this->app->jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组
                        break;
                    case 'APP':
                        // APP支付
                        return $this->app->jssdk->appConfig($result['prepay_id']);  // 返回数组
                        break;
                }

            }else{
                $this->error = $result['return_msg'];
                return false;
            }
        }catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();
        } catch (InvalidConfigException $e) {
            $this->error = $e->getMessage();
        } catch (GuzzleException $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }

    /**
     * 支付回调通知处理
     *
     * @param $OrderModel
     *
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function handlePaidNotify($OrderModel)
    {
        $response = $this->app->handlePaidNotify(function($message, $fail) use ($OrderModel) {
            //查询订单
            $order = $OrderModel->payDetail($message['out_trade_no']);
            if (empty($order)) {
                // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            // return_code 表示通信状态，不代表支付状态
            if ($message['return_code'] === 'SUCCESS') {
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {
                    // 更新订单状态
                    $result = $order->updatePayStatus(20, $message['transaction_id']);
                    if(!$result)
                        return $fail('处理订单失败，请稍后再通知我');

                } elseif ($message['result_code'] === 'FAIL') {
                    // 用户支付失败

                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            return true; //返回处理完成
        });
        $response->send();
    }

    /**
     * 企业付款   企业付款到用户零钱/企业付款到银行卡
     * @param number $amount     金额
     * @param null $type         类型
     * @param null $account      账户
     * @param null $realname     真是姓名
     * @param null $orderid      订单id
     * @param null $title        说明
     * @param null $code         编号
     *
     * @return array|bool
     */
    public function transfer($amount, $type = null, $account = null, $realname = null, $orderid = null, $title = null, $code = null)
    {
        try {
            //转账到银行卡
            if ($type == 'bank') {
                $result = $this->app->transfer->toBankCard([
                    'partner_trade_no' => $orderid,
                    'enc_bank_no' => $account,      // 银行卡号
                    'enc_true_name' => $realname,   // 银行卡对应的用户真实姓名
                    'bank_code' => $code,           // 银行编号
                    'amount' => $amount * 100,      // 单位：分
                    'desc' => $title,
                ]);
            } else {
                //创建支付对象
                $result = $this->app->transfer->toBalance([
                      'partner_trade_no' => $orderid,   // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
                      'openid'           => $account,
                      'check_name'       => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
                      // 're_user_name' => $realname,   // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
                      'amount'           => $amount * 100, // 企业付款金额，单位为分
                      'desc'             => $title,     // 企业付款操作说明信息。必填
                ]);
            }
            return $result;

        } catch (Exception $e){
            $data = $e->raw;
            return ['msg'=> isset($data['return_msg']) ? $data['return_msg'] : $e->getMessage()];
        } catch (InvalidArgumentException $e) {

        } catch (InvalidConfigException $e) {
            //捕获缺少配置参数
            trace('转账缺少配置：' . var_export_short($e->getMessage(), true), 'error');
            return ['msg' => '缺少配置参数'];
        } catch (GuzzleException $e) {
        } catch (RuntimeException $e) {
        }

        return false;
    }

    /**
     * 申请退款   原路退款
     *
     * @param $refund_fee
     * @param $total_fee
     * @param $order_no
     * @param $remark
     * @param $out_refund_no
     *
     * @return array|bool
     */
    public function refund($refund_fee, $total_fee, $order_no, $remark, $out_refund_no){

        try {
            return $this->app->refund->byOutTradeNumber($order_no, $out_refund_no, $total_fee * 100, $refund_fee * 100, $remark);
        } catch (InvalidConfigException $e) {
            //捕获缺少配置参数
            trace('微信退款缺少配置：'.var_export_short($e->getMessage(),true),'error');
            return false;
        }
    }

    /**
     * 过滤微信昵称特殊字符
     *
     * @param $str
     *
     * @return string|string[]|null
     */
    private function filter_emoji($str)
    {
        return preg_replace_callback( '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            }, $str);
    }


    public function getError()
    {
        return $this->error;
    }
}