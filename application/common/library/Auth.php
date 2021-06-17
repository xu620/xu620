<?php

namespace app\common\library;

use app\api\service\user\UserService;
use fast\Random;
use think\facade\Config;
use think\Db;
use think\Exception;
use think\facade\Hook;
use think\facade\Request;

class Auth
{
    protected static $instance = null;
    protected $_error = '';
    protected $_logined = false;
    protected $_user = null;
    protected $_token = '';
    //Token默认有效时长
    protected $keeptime = 2592000;
    protected $requestUri = '';
    protected $rules = [];
    //默认配置
    protected $config = [];
    protected $options = [];
    protected $allowFields = ['id', 'nickname', 'mobile', 'avatar', 'money'];

    public function __construct($options = [])
    {
        if ($config = Config::get('user.')) {
            $this->config = array_merge($this->config, $config);
        }
        $this->options = array_merge($this->config, $options);
    }

    /**
     *
     * @param array $options 参数
     * @return Auth
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     * 获取User模型
     * @return UserService
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 兼容调用user模型的属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_user ? $this->_user->$name : null;
    }

    /**
     * 根据Token初始化
     *
     * @param string $token Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined) {
            return true;
        }
        if ($this->_error) {
            return false;
        }
        $data = Token::checkToken($token);
        if (!$data) {
            return false;
        }
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        if ($user_id > 0) {
            $user = UserService::get($user_id);
            if (!$user) {
                $this->setError(__('Account not exist'));
                return false;
            }
            if(!isset($data['access_token']) || $user['token'] != $data['access_token']){
                $this->setError(__('You are not logged in'));
                return false;
            }
            if ($user['status'] != 1) {
                $this->setError(__('Account is locked'));
                return false;
            }
            $this->_user = $user;
            $this->_logined = true;
            $this->_token = $token;

            //初始化成功的事件
            Hook::listen("user_init_successed", $this->_user);

            return true;
        } else {
            $this->setError(__('You are not logged in'));
            return false;
        }
    }

    /**
     * 注册用户
     *
     * @param string $nickname 昵称
     * @param string $password 密码
     * @param string $mobile   手机号
     * @param array  $extend   扩展参数
     * @return boolean
     */
    public function register($nickname, $password, $mobile, $extend = [])
    {
        // 检测手机号是否存在
        if (UserService::get(['mobile' => $mobile])) {
            $this->setError(__('Account already exist'));
            return false;
        }

        $ip = request()->ip();
        $time = time();

        $params = [
            'nickname'   => $nickname,
            'salt'       => Random::alnum(),
            'mobile'     => $mobile,
            'join_ip'    => $ip,
            'login_time' => $time,
            'login_ip'   => $ip,
            'prev_time'  => $time,
            'status'     => 1,
            'token'      => Random::alnum(32),
        ];
        $params['password'] = $this->getEncryptPassword($password, $params['salt']);
        $params = array_merge($params, $extend);

        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try {
            $user = UserService::create($params, true);

            $this->_user = UserService::get($user->id);
            //设置Token
            $this->_token = Token::createToken($user->id, $user->token, $this->keeptime);

            //设置登录状态
            $this->_logined = true;

            Db::commit();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            Db::rollback();
            return false;
        }
        return true;
    }

    /**
     * 微信登录
     *
     * @param array $params
     *
     * @return bool
     */
    public function wechat($params = [])
    {
        $this->keeptime(0);
        $user = UserService::get(['unionid' => $params['unionid']]);
        if (!$user) {
            // 默认注册一个会员
            $result = $this->register($params['nickname'], Random::alnum(6), '', $params);
            if (!$result) {
                return false;
            }
            $user = $this->getUser();
        }
        return $this->direct($user->id);
    }

    /**
     * 苹果登录
     *
     * @param array $params
     *
     * @return bool
     */
    public function apple($params = [])
    {
        $this->keeptime(0);
        $user = UserService::get(['apple_id' => $params['apple_id']]);
        if (!$user) {
            // 默认注册一个会员
            $result = $this->register(Random::alnum(), Random::alnum(6), '', $params);
            if (!$result) {
                return false;
            }
            $user = $this->getUser();
        }
        return $this->direct($user->id);
    }

    /**
     * 用户登录
     *
     * @param string $mobile  手机号
     * @param string $password 密码
     * @return boolean
     */
    public function login($mobile, $password)
    {
        $user = UserService::get(['mobile' => $mobile]);
        if (!$user || $user['password'] != $this->getEncryptPassword($password, $user['salt'])) {
            $this->setError(__('Account or password is incorrect'));
            return false;
        }

        if ($user['status'] != 1) {
            $this->setError(__('Account is locked'));
            return false;
        }

        //直接登录会员
        $this->direct($user->id);

        return true;
    }

    /**
     * 退出
     *
     * @return boolean
     */
    public function logout()
    {
        if (!$this->_logined) {
            $this->setError(__('You are not logged in'));
            return false;
        }
        //设置登录标识
        $this->_logined = false;
        //删除Token
        $this->_user->token = Random::alnum(32);
        $this->_user->save();
        //退出成功的事件
        Hook::listen("user_logout_successed", $this->_user);
        return true;
    }

    /**
     * 修改密码
     * @param string $newpassword       新密码
     * @param string $oldpassword       旧密码
     * @param bool   $ignoreoldpassword 忽略旧密码
     * @return boolean
     */
    public function changepwd($newpassword, $oldpassword = '', $ignoreoldpassword = false)
    {
        if (!$this->_logined) {
            $this->setError(__('You are not logged in'));
            return false;
        }
        //判断旧密码是否正确
        if ($this->_user->password == $this->getEncryptPassword($oldpassword, $this->_user->salt) || $ignoreoldpassword) {
            Db::startTrans();
            try {
                $salt = Random::alnum();
                $newpassword = $this->getEncryptPassword($newpassword, $salt);
                $this->_user->save(['login_failure' => 0, 'password' => $newpassword, 'salt' => $salt]);

                //修改密码成功的事件
                Hook::listen("user_changepwd_successed", $this->_user);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->setError(__('Reset password failure'));
                return false;
            }
            return true;
        } else {
            $this->setError(__('Password is incorrect'));
            return false;
        }
    }

    /**
     * 直接登录账号
     * @param int $user_id
     * @return boolean
     */
    public function direct($user_id)
    {
        $user = UserService::get($user_id);
        if ($user) {
            Db::startTrans();
            try {
                $ip = request()->ip();
                $time = time();

                //判断连续登录和最大连续登录
                if ($user->login_time < \fast\Date::unixtime('day')) {
                    $user->successions = $user->login_time < \fast\Date::unixtime('day', -1) ? 1 : $user->successions + 1;
                    $user->max_successions = max($user->successions, $user->max_successions);
                }

                $user->prev_time = $user->login_time;
                //记录本次登录的IP和时间
                $user->login_ip   = $ip;
                $user->login_time = $time;
                //重置登录失败次数
                $user->login_failure = 0;
                //更新Token
                $user->token = Random::alnum(32);
                $user->save();

                $this->_user = $user;

                $this->_token = Token::createToken($user->id, $user->token, $this->keeptime);

                $this->_logined = true;

                //登录成功的事件
                Hook::listen("user_login_successed", $this->_user);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                // $e->getMessage()
                $this->setError(__('Login failed'));
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测是否是否有对应权限
     * @param string $path   控制器/方法
     * @param string $module 模块 默认为当前模块
     * @return boolean
     */
    public function check($path = null, $module = null)
    {
        if (!$this->_logined) {
            return false;
        }

        return true;
    }

    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取会员基本信息
     */
    public function getUserinfo()
    {
        $data = $this->_user->toArray();
        $allowFields = $this->getAllowFields();
        $userinfo = array_intersect_key($data, array_flip($allowFields));
        $userinfo = array_merge($userinfo, ['token' => $this->_token]);
        return $userinfo;
    }

    /**
     * 获取当前请求的URI
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * 设置当前请求的URI
     * @param string $uri
     */
    public function setRequestUri($uri)
    {
        $this->requestUri = $uri;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }

    /**
     * 删除一个指定会员
     * @param int $user_id 会员ID
     * @return boolean
     */
    public function delete($user_id)
    {
        $user = UserService::get($user_id);
        if (!$user) {
            return false;
        }
        Db::startTrans();
        try {
            // 删除会员
            UserService::destroy($user_id);

            Hook::listen("user_delete_successed", $user);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->setError($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password 密码
     * @param string $salt     密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组
     *
     * @param array $arr 需要验证权限的数组
     * @return boolean
     */
    public function match($arr = [])
    {
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (!$arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }

        // 没找到匹配
        return false;
    }

    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function keeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    /**
     * 渲染用户数据
     * @param array  $datalist  二维数组
     * @param mixed  $fields    加载的字段列表
     * @param string $fieldkey  渲染的字段
     * @param string $renderkey 结果字段
     * @return array
     */
    public function render(&$datalist, $fields = [], $fieldkey = 'user_id', $renderkey = 'userinfo')
    {
        $fields = !$fields ? ['id', 'nickname', 'level', 'avatar'] : (is_array($fields) ? $fields : explode(',', $fields));
        $ids = [];
        foreach ($datalist as $k => $v) {
            if (!isset($v[$fieldkey])) {
                continue;
            }
            $ids[] = $v[$fieldkey];
        }
        $list = [];
        if ($ids) {
            if (!in_array('id', $fields)) {
                $fields[] = 'id';
            }
            $ids = array_unique($ids);
            $selectlist = UserService::where('id', 'in', $ids)->column($fields);
            foreach ($selectlist as $k => $v) {
                $list[$v['id']] = $v;
            }
        }
        foreach ($datalist as $k => &$v) {
            $v[$renderkey] = isset($list[$v[$fieldkey]]) ? $list[$v[$fieldkey]] : null;
        }
        unset($v);
        return $datalist;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }
}
