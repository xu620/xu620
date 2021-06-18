<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: MrXu
 * +----------------------------------------------------------------------
 * | DateTime: 2021/1/22 16:47
 * +----------------------------------------------------------------------
 */

namespace app\api\controller;


use app\api\service\user\UserService;
use app\common\controller\Api;
use app\api\service\SmsService;
use AppleSignIn\ASDecoder;
use fast\Random;
use think\facade\Validate;
use wechat\WechatService;

/**
 * 账号类接口
 * Class AccountController
 * @package app\api\controller
 */
class AccountController extends Api
{
    protected $noNeedLogin = ['login', 'mobileLogin', 'register', 'resetPwd', 'authApple', 'getOpenid', 'wechat','getJsSdk','getRedirect','wechatCode'];

    /**
     * @api {post} /api/account/login 登录
     * @apiName login
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 登录
     *
     * @apiParam {String} account    账号
     * @apiParam {String} password   密码
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     * @apiSuccessExample {Object}  成功数据
     * "data": {
     *      "user_info": {                              // 用户信息
     *          "id": "2",                              // 用户ID
     *          "nickname": "181****9682",              // 用户昵称
     *          "mobile": "13888888888",                // 用户手机号
     *          "avatar": "http://avatar.png",          // 用户头像
     *          "money": "0.00",                        // 余额
     *          "token": "eyJ0eXAiOiJKV1QiI2DmozQ0",    // 令牌
     *      },
     * }
     *
     */
    public function login()
    {
        $account  = $this->request->param('account');
        $password = $this->request->param('password');

        $validate = $this->validate(compact('account', 'password'), 'app\api\validate\Account.login');
        if($validate !== true){
            $this->error($validate);
        }

        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = [
                'user_info' => $this->auth->getUserInfo(),
            ];
            $this->success(__('Logged in successful'), $data);
        }

        $error = $this->auth->getError() ?: __('Login failed');
        $this->error($error);
    }

    /**
     * @api {post} /api/account/mobileLogin  手机验证码登录
     * @apiName mobileLogin
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 手机验证码登录
     *
     * @apiParam {String} mobile  手机号
     * @apiParam {String} captcha 验证码
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     * @apiSuccessExample {json}  成功数据
     * "data": {
     *      "user_info": {                              // 用户信息
     *          "id": "2",                              // 用户ID
     *          "nickname": "181****9682",              // 用户昵称
     *          "mobile": "13888888888",                // 用户手机号
     *          "avatar": "http://avatar.png",          // 用户头像
     *          "money": "0.00",                        // 余额
     *          "token": "eyJ0eXAiOiJKV1QiI2DmozQ0",    // 令牌
     *      },
     * }
     *
     */
    public function mobileLogin()
    {
        $mobile  = $this->request->param('mobile');
        $captcha = $this->request->param('captcha');

        if (!$mobile || !$captcha) {
            $this->error(__('参数错误'));
        }
        $sms = new SmsService();
        $check = $sms->checkCode($mobile, $captcha, 'login');
        if(!$check)
            $this->error($sms->getError());

        $user = UserService::getByMobile($mobile);
        if ($user) {
            if ($user->status != 1) {
                $this->error(__('账户已经被锁定'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register(substr_replace($mobile, '****', 3, 4), Random::alnum(6), $mobile, []);
        }

        if ($ret) {
            $sms->flush($mobile, 'login');
            $data = [
                'user_info' => $this->auth->getUserInfo(),
            ];
            $this->success(__('登录成功'), $data);
        }

        $this->error('登录失败');
    }

    /**
     * @api {post} /api/account/register  注册
     * @apiName register
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 注册
     *
     * @apiParam {String} mobile        手机号
     * @apiParam {String} captcha       验证码
     * @apiParam {String} password      密码
     * @apiParam {String} repassword    确认密码
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     * @apiSuccessExample {Object}  成功示例
     * "data": {
     *      "user_info": {                              // 用户信息
     *          "id": "2",                              // 用户ID
     *          "nickname": "181****9682",              // 用户昵称
     *          "mobile": "13888888888",                // 用户手机号
     *          "avatar": "http://avatar.png",          // 用户头像
     *          "money": "0.00",                        // 余额
     *          "token": "eyJ0eXAiOiJKV1QiI2DmozQ0",    // 令牌
     *      },
     * }
     *
     */
    public function register()
    {
        $mobile     = $this->request->param('mobile');
        $captcha    = $this->request->param('captcha');
        $password   = $this->request->param('password');
        $repassword = $this->request->param('repassword');

        $validate = $this->validate(compact('mobile', 'captcha', 'password', 'repassword'), 'app\api\validate\Account.register');
        if($validate !== true){
            $this->error($validate);
        }

        $sms = new SmsService();
        $check = $sms->checkCode($mobile, $captcha, 'register');
        if(!$check)
            $this->error($sms->getError());

        $ret = $this->auth->register(substr_replace($mobile, '****', 3, 4), $password, $mobile);
        if ($ret) {
            $sms->flush($mobile, 'register');
            $user = $this->auth->getUserInfo();

            $data = [
                'user_info' => $user,
            ];
            $this->success(__('Sign up successful'), $data);
        }
        $this->error(__('Sign up failed'));
    }

    /**
     * @api {post} /api/account/authApple 苹果登录
     * @apiName authApple
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 苹果登录
     *
     * @apiParam {String} identityToken  身份令牌
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *    "user_info": {                                // 用户信息
     *          "id": "2",                              // 用户ID
     *          "nickname": "181****9682",              // 用户昵称
     *          "mobile": "13888888888",                // 用户手机号
     *          "avatar": "http://avatar.png",          // 用户头像
     *          "money": "0.00",                        // 余额
     *          "token": "eyJ0eXAiOiJKV1QiI2DmozQ0",    // 令牌
     *     },
     *    "is_bind_mobile": true,                        //是否绑定手机 true已绑定
     * }
     *
     */
    public function authApple()
    {
        $identityToken = $this->request->param('identityToken');
        if(!$identityToken)
            $this->error('请先授权登录');

        $appleSignInPayload = ASDecoder::getAppleSignInPayload($identityToken);
        $apple_id = $appleSignInPayload->getUser();
        if(!$apple_id)
            $this->error('请先授权登录');

        $params = [];
        $params['apple_id'] = $apple_id;

        $login_ret = $this->auth->apple($params);
        if ($login_ret) {
            $user = $this->auth->getUser();
            if ($user['status'] != 1) {
                $this->error('账户已经被锁定');
            }

            $isBind = true; //是否绑定手机
            if(empty($user['mobile'])){
                $isBind = false;
            }
            $data = [
                'user_info'      => $this->auth->getUserinfo(),
                'is_bind_mobile' => $isBind,
            ];
            $this->success('登录成功', $data);
        }

        $this->error('登录失败');
    }

    /**
     * @api {post} /api/account/getOpenid 微信小程序登录第一步
     * @apiName getOpenid
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 微信登录第一步-获取openid和session_key
     *
     * @apiParam {String} code   登录时获取的 code
     *
     * @apiSuccess {Number} code  返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     * @apiSuccessExample {object} 成功示例
     * "data" : {
     *    "openid":"xxxx",                      //用户openid 新用户时返回
     *    "user_info": {                        //用户信息   老用户时返回
     *       "id": 9,                           //用户ID
     *       "nickname": "haha",                //昵称
     *       "mobile": "13888888888",           //手机号
     *       "avatar": "http://avatar.png",     //头像
     *       "token": "1924b7",                 //令牌
     *    },
     *    "is_bind_mobile": true,               //是否绑定手机 true已绑定
     * }
     */
    public function getOpenid(){
        $code = $this->request->param('code');
        if(empty($code))
            $this->error('参数错误');

        $wechat = new WechatService('miniProgram');
        $result = $wechat->sessionKey($code);
        if($result && isset($result['openid'])){
            $user = UserService::where('openid', $result['openid'])->find();
            $data = [
                'openid'    => $result['openid'],
                'user_info' => null,
                'is_bind_mobile' => false
            ];
            if ($user && $this->auth->direct($user['id'])) {
                if ($user['status'] != 1) {
                    $this->error('账户已经被锁定');
                }

                // 是否绑定手机
                if($user['mobile']){
                    $data['is_bind_mobile'] = true;
                }
                $data['user_info'] = $this->auth->getUserinfo();
            }
            $this->success('请求成功', $data);
        }
        $this->error('登录失败');
    }

    /**
     * @api {post} /api/account/miniProgram 微信小程序登录
     * @apiName miniProgram
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 微信小程序登录
     *
     * @apiParam {String} openid         openid
     * @apiParam {String} rawData        原始数据
     * @apiParam {String} signature      签名
     * @apiParam {String} encryptedData  加密数据
     * @apiParam {String} iv             向量
     *
     * @apiSuccess {Number} code   返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg    提示语.
     * @apiSuccess {object} [data] 返回数据
     * @apiSuccessExample {json}   成功示例
     * "data": {
     *    "user_info": {                              //用户信息
     *        "id": 9,                                //用户ID
     *        "nickname": "haha",                     //昵称
     *        "mobile": "18559588920",                //手机号
     *        "avatar": "http://piano.test/tar.png",  //头像
     *        "token": "1924b57e",                    //令牌
     *    },
     *    "is_bind_mobile": true,                     //是否绑定手机 true已绑定
     * }
     *
     */
    public function miniProgram()
    {
        $openid        = $this->request->param('openid');
        $rawData       = $this->request->param('rawData','','trim,strip_tags');
        $signature     = $this->request->param('signature');
        $encryptedData = $this->request->param('encryptedData');
        $iv            = $this->request->param('iv');
        $sessionKey    = cache($openid);
        if(empty($openid) || empty($encryptedData) || empty($iv) || empty($sessionKey)){
            $this->error('请先授权登录');
        }

        //验签
        if($signature != sha1($rawData . $sessionKey)){
            $this->error('请先授权登录');
        }

        $wechat = new WechatService('miniProgram');
        $result = $wechat->getMiniProgramUserInfo($sessionKey, $iv, $encryptedData);
        if ($result) {
            $login_ret = $this->auth->wechat($result);
            if ($login_ret) {
                $user = $this->auth->getUser();
                if ($user['status'] != 1) {
                    $this->error('账户已经被锁定');
                }

                $isBind = false; //是否绑定手机
                if ($user['mobile']) {
                    $isBind = true;
                }
                $data = [
                    'user_info'      => $this->auth->getUserinfo(),
                    'is_bind_mobile' => $isBind,
                ];
                $this->success('登录成功', $data);
            }
            trace('微信登录失败：' . var_export_short($this->auth->getError(), true), 'error');
        }
        trace('微信登录解析失败：'.var_export_short($wechat->getError(),true),'error');
        $this->error('登录失败');
    }

    /**
     * @api {post} /api/account/wechat 微信登录 h5/APP
     * @apiName wechat
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 微信登录 h5/APP
     *
     * @apiParam {String} code
     *
     * @apiSuccess {Number} code   返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg    提示语.
     * @apiSuccess {object} [data] 返回数据
     * @apiSuccessExample {json}   成功示例
     * "data": {
     *    "user_info": {                              //用户信息
     *        "id": 9,                                //用户ID
     *        "nickname": "haha",                     //昵称
     *        "mobile": "18559588920",                //手机号
     *        "avatar": "http://piano.test/tar.png",  //头像
     *        "token": "1924b57e",                    //令牌
     *    },
     *    "is_bind_mobile": true,                     //是否绑定手机 true已绑定
     * }
     *
     */
    public function wechat()
    {
        $code = $this->request->param('code','');
        if(!$code){
            $this->error('登录失败');
        }

        $userInfo = (new WechatService('officialAccount'))->getUserInfo($code);
        if (!$userInfo) {
            $this->error('登录失败');
        }

        $login_ret = $this->auth->wechat($userInfo);
        if ($login_ret) {
            $user = $this->auth->getUser();
            if ($user['status'] != 1) {
                $this->error('账户已经被锁定');
            }

            $isBind = true; //是否绑定手机
            if(empty($user['mobile'])){
                $isBind = false;
            }
            $data = [
                'user_info'      => $this->auth->getUserinfo(),
                'is_bind_mobile' => $isBind,
            ];
            $this->success('登录成功', $data);
        }
        trace('微信登录失败：'.var_export_short($this->auth->getError(),true),'error');
        $this->error('登录失败');
    }

    /**
     * @api {get} /api/account/getRedirect H5微信登录获取code
     * @apiName getRedirect
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription H5微信登录获取code
     *
     * @apiParam {String} redirect_uri  回调地址
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     *
     */
    public function getRedirect()
    {
        $redirect_uri = $this->request->param('redirect_uri');
        if (!$redirect_uri)
            $this->error('请求失败');

        // 成功回调地址
        $redirect = $this->request->domain().'/api/account/wechatCode?callback_url=' . base64_encode($redirect_uri);

        $wechat = new WechatService('officialAccount');
        $redirectUrl = $wechat->getRedirect($redirect);
        header("Location: " . $redirectUrl);
    }

    /**
     * 回调给前端 code
     */
    public function wechatCode()
    {
        $code           = $this->request->param("code");
        $callback_url   = $this->request->param('callback_url');    //前端回调地址
        if (!$callback_url)
            $this->error('回调地址不允许为空');

        $callback_url = base64_decode($callback_url);

        if(strpos($callback_url, '?')){
            $callback_url = $callback_url.'&code=' . $code;
        }else{
            $callback_url = $callback_url.'?code=' . $code;
        }

        header('Location:' . $callback_url);
    }

    /**
     * @api {get} /api/account/getJsSdk 获取微信JSSDK配置
     * @apiName getJsSdk
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 获取微信JSSDK配置
     *
     * @apiParam {String} url 分享地址
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *      "debug": false,
     *      "beta": false,
     *      "jsApiList": [
     *          "updateAppMessageShareData",
     *          "updateTimelineShareData"
     *      ],
     *      "openTagList": [],
     *      "appId": "wx847f3dfe20ad04f9",
     *      "nonceStr": "whNZARNoY0",
     *      "timestamp": 1606873498,
     *      "url": "http://xxx/getJsSdk",
     *      "signature": "5a1f00338ca90295d8e823ecbc83382c5209cc00"
     * }
     *
     */
    public function getJsSdk()
    {
        $url = $this->request->param('url','');
        $config = (new WechatService('officialAccount'))->jssdk($url);
        $this->success('请求成功', $config);
    }

    /**
     * @api {post} /api/account/logout  注销登录
     * @apiName logout
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 注销登录
     *
     * @apiParam {String} token   令牌
     *
     * @apiSuccess {Number} code  返回值 0提示错误  1正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     *
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * @api {post} /api/account/bindMobile 绑定手机号
     * @apiName bindMobile
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 绑定手机号
     *
     * @apiHeader {String} token   令牌
     *
     * @apiParam {Number} [mobile]  手机号   直接绑定手机号时必传
     * @apiParam {String} [captcha] 验证码   直接绑定手机号时必传
     * @apiParam {String} [openid]          openid  绑定微信手机号时必传
     * @apiParam {String} [encryptedData]   加密数据 绑定微信手机号时必传
     * @apiParam {String} [iv]              向量    绑定微信手机号时必传
     *
     * @apiSuccess {Number} code   返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg    提示语.
     * @apiSuccess {object} [data] 返回数据
     * @apiSuccessExample {object} 成功示例
     * "data": {
     *    "user_info": {                              // 用户信息
     *        "id": 9,                                // 用户ID
     *        "nickname": "haha",                     // 昵称
     *        "mobile": "18559588920",                // 手机号
     *        "avatar": "http://piano.test/tar.png",  // 头像
     *        "money": "10",                          // 余额
     *        "token": "1924b57e",                    // 令牌
     *    }
     * }
     *
     */
    public function bindMobile()
    {
        $user = $this->auth->getUser();
        $mobile  = $this->request->param('mobile');
        $captcha = $this->request->param('captcha');
        $openid        = $this->request->param('openid', '');
        $encryptedData = $this->request->param('encryptedData','');
        $iv            = $this->request->param('iv','');
        $sessionKey    = cache($openid);
        if (!$mobile && !$encryptedData)
            $this->error('请求失败');

        if($user['mobile']){
            $this->error('请勿重复绑定手机号');
        }

        if($mobile){
            if(!$captcha){
                $this->error('请输入验证码');
            }
            // 验证验证码
            $sms = new SmsService();
            $result = $sms->checkCode($mobile, $captcha, 'bind');
            if (!$result) {
                $error = $sms->getError() ?: '验证码错误';
                $this->error($error);
            }

            $sms->flush($mobile, 'bind');
        }else{
            if(!$openid || !$encryptedData || !$iv || !$sessionKey){
                $this->error('请先获取授权');
            }

            $res = (new WechatService('miniProgram'))->decryptData($sessionKey, $iv, $encryptedData);
            if(!$res){
                $this->error('请求失败');
            }
            $mobile = isset($res['purePhoneNumber']) ? $res['purePhoneNumber'] : '';
        }

        if (!Validate::mobile($mobile))
            $this->error('手机号格式不正确');

        if($user['mobile'] == $mobile){
            $this->error('新手机号码不能和旧手机号一致');
        }

        //判断绑定的手机号是否已存在用户
        $exists = UserService::where('mobile', $mobile)->where('id', '<>', $user->id)->find();
        if ($exists) {
            // 同时存在苹果登录和微信登录说明是老用户了，不能合并账号
            if($user['apple_id'] && $user['unionid']){
                $this->error('手机号已被注册');
            }

            //苹果授权登录
            if($user['apple_id']){
                //判断手机号用户是否也是苹果登录
                if($exists['apple_id']){
                    $this->error('手机号已被注册');
                }
                //更换绑定信息
                $exists->apple_id = $user['apple_id'];
            }

            // 微信登录来绑定手机号
            if($user['unionid']){
                // 已绑定微信
                if($exists['unionid']) {
                    $this->error('手机号已被注册');
                }

                $exists->openid  = $user['openid'];
                $exists->unionid = $user['unionid'];
            }

            $exists->save();
            //删除当前用户
            $user->delete();
            $user_id = $exists['id'];
        }else{
            $user->mobile = $mobile;
            $user->save();
            $user_id = $user['id'];
        }

        $ret = $this->auth->direct($user_id);
        if ($ret) {
            $sms->flush($mobile, 'bind');
            $data = [
                'user_info' => $this->auth->getUserInfo(),
            ];
            $this->success('绑定成功', $data);
        }else{
            $this->error('操作失败');
        }
    }

    /**
     * @api {post} /api/account/bindWechat 绑定微信号
     * @apiName bindWechat
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 绑定微信号
     *
     * @apiHeader {String} token   令牌
     *
     * @apiParam {String} nickname
     * @apiParam {String} openid
     * @apiParam {String} unionid
     *
     * @apiSuccess {Number} code   返回值 其他错误码提示错误  2000正常.
     * @apiSuccess {String} msg    提示语.
     * @apiSuccess {object} [data] 返回数据
     *
     */
    public function bindWechat()
    {
        $openid  = $this->request->param('openid');
        $unionid = $this->request->param('unionid');
        if(!$unionid || !$openid)
            $this->error('绑定失败');

        $user = $this->auth->getUser();
        $res = UserService::get(['unionid' => $unionid]);
        if ($res)
            $this->error('该微信号已被其人绑定');

        $user->openid  = $openid;
        $user->unionid = $unionid;
        $user->save();

        $this->success('绑定成功');
    }

    /**
     * @api {post} /api/account/resetPwd 重置密码
     * @apiName resetPwd
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 重置密码
     *
     * @apiParam {String} mobile        手机号
     * @apiParam {String} captcha       验证码
     * @apiParam {String} password      密码
     * @apiParam {String} repassword    确认密码
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     *
     */
    public function resetPwd()
    {
        $mobile     = $this->request->param("mobile");
        $password   = $this->request->param("password");
        $repassword = $this->request->param("repassword");
        $captcha    = $this->request->param("captcha");

        $validate = $this->validate(compact('mobile', 'captcha', 'password', 'repassword'), 'app\api\validate\Account.resetpwd');
        if($validate !== true){
            $this->error($validate);
        }

        $sms = new SmsService();
        $check = $sms->checkCode($mobile, $captcha, 'resetpwd');
        if(!$check)
            $this->error($sms->getError());

        $user = UserService::get(['mobile' => $mobile]);
        if (!$user) {
            $this->error(__('User not found'));
        }
        // 删除验证码
        $sms::flush($mobile, 'resetpwd');
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($password, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * @api {post} /api/account/changeMobile 修改手机号
     * @apiName changeMobile
     * @apiGroup 账号管理
     * @apiVersion 0.0.0
     * @apiDescription 修改手机号
     *
     * @apiHeader {String} token   令牌
     *
     * @apiParam {String} mobile        手机号
     * @apiParam {String} captcha       验证码
     * @apiParam {String} old_captcha   原手机号验证码
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *      "user_info": {                              // 用户信息
     *          "id": "2",                              // 用户ID
     *          "nickname": "181****9682",              // 用户昵称
     *          "mobile": "13888888888",                // 用户手机号
     *          "avatar": "http://avatar.png",          // 用户头像
     *          "money": "0.00",                        // 余额
     *          "token": "eyJ0eXAiOiJKV1QiI2DmozQ0",    // 令牌
     *      },
     * }
     *
     */
    public function changeMobile()
    {
        $mobile     = $this->request->param('mobile');
        $captcha    = $this->request->param('captcha');
        $old_captcha= $this->request->param('old_captcha');

        $validate = $this->validate(compact('mobile', 'captcha', 'old_captcha'), 'app\api\validate\Account.changemobile');
        if($validate !== true){
            $this->error($validate);
        }

        $user = $this->auth->getUser();
        $sms = new SmsService();
        // 验证旧手机号验证码
        $check_old = $sms->checkCode($user['mobile'], $old_captcha, 'check');
        if(!$check_old)
            $this->error($sms->getError());
        // 验证新手机号验证码
        $check = $sms->checkCode($mobile, $captcha, 'bind');
        if(!$check)
            $this->error($sms->getError());

        $is_exists = UserService::where(['mobile' => $mobile])->where('id', '<>', $user->id)->find();
        if ($is_exists) {
            $this->error(__('Mobile already exists'));
        }

        $sms::flush($user['mobile'], 'check');
        $sms::flush($mobile, 'bind');

        $user->save(['mobile' => $mobile]);

        $this->auth->direct($user->id);
        $data = [
            'user_info' => $this->auth->getUserInfo(),
        ];
        $this->success(__('Operation completed'), $data);
    }

}