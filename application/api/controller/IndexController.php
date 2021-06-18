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

use app\api\service\BannerService;
use app\api\service\NavigationService;
use app\common\controller\Api;

/**
 * 首页接口
 */
class IndexController extends Api
{
    protected $noNeedLogin = ['index','test1','test'];
    protected $noNeedRight = ['*'];

    /**
     * @api {get} /api/index/index 首页
     * @apiName index
     * @apiGroup 首页管理
     * @apiVersion 0.0.0
     * @apiDescription 首页
     *
     * @apiHeader {String} [token]   令牌
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     * @apiSuccessExample {Object} 成功示例
     * "data": {
     *      "banner": [              // 轮播图
     *          {
     *              "title": "啦啦啦",
     *              "image": "http://e7d34.jpg",
     *              "jump_type": "0",    //跳转类型 0不跳转 1跳转内链 2跳转外链
     *              "url": ""
     *          }
     *      ],
     *      "navigation": [         // 导航
     *          {
     *              "name": "理财投资",
     *              "image": "http://9bdb0.jpg",
     *              "jump_type": "1",    // 跳转类型 1内链 2外链
     *              "url": "/pages/invest/index"
     *          },
     *      ],
     * }
     *
     */
    public function index()
    {
        // 轮播图
        $banner = BannerService::lists(1);
        // 导航
        $navigation = NavigationService::getList();

        $data = [
            'banner'        => $banner,
            'navigation'    => $navigation,
        ];

        $this->success(__('Operation completed'), $data);
    }

    public function test() {
        sleep(10);
        return 123;
    }

    public function test1() {
        return 123456;
    }
}
