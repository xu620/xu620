<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: MrXu
 * +----------------------------------------------------------------------
 * | DateTime: 2021/1/26 9:46
 * +----------------------------------------------------------------------
 */

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 会员接口
 */
class UserController extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    /**
     * @api {get} /api/user/index 会员中心
     * @apiName index
     * @apiGroup 会员管理
     * @apiVersion 0.0.0
     * @apiDescription 会员中心
     *
     * @apiHeader {String} token   令牌
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Object} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *      "avatar": "http://avatar.png",      // 头像
     *      "nickname": "181****9666",          // 昵称
     *      "money": "0.00",                    // 余额
     *
     * }
     *
     */
    public function index()
    {
        $user = $this->auth->getUser();
        $data = [
            'avatar'            => $user['avatar'],
            'nickname'          => $user['nickname'],
            'money'             => $user['money'],
        ];

        $this->success(__('Request successful'), $data);
    }


}
