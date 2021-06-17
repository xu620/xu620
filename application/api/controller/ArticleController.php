<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: Yida
 * +----------------------------------------------------------------------
 * | DateTime: 2021/01/26 11:01
 * +----------------------------------------------------------------------
 */

namespace app\api\controller;

use app\common\controller\Api;
use app\api\service\ArticleService;

/**
 * 文章接口
 * Class ArticleController
 * @package app\api\controller
 */
class ArticleController extends Api
{
    protected $noNeedLogin = ['*'];


    /**
     * @api {get} /api/article/userAgreement  用户协议H5
     * @apiName userAgreement
     * @apiGroup 内容管理
     */
    /**
     * @api {get} /api/article/privacy  隐私政策H5
     * @apiName privacy
     * @apiGroup 内容管理
     */
    /**
     * @api {get} /api/article/aboutUs  关于我们H5
     * @apiName aboutUs
     * @apiGroup 内容管理
     */
    /**
     * @api {get} /api/article/recharge  充值协议H5
     * @apiName recharge
     * @apiGroup 内容管理
     */
    /**
     * @api {get} /api/article/member  会员说明H5
     * @apiName member
     * @apiGroup 内容管理
     */
    /**
     * @api {get} /api/article/detail 新手指南详情H5
     * @apiName detail
     * @apiGroup 内容管理
     * @apiVersion 0.0.0
     * @apiDescription 新手指南详情
     *
     * @apiParam {Number} id 文章ID
     *
     */
    public function detail($id, $show_title = false)
    {
        //$arr = ['1' => '用户协议','2'=>'隐私政策','3'=>'关于我们','4' => '充值协议', '5' => '会员说明'];
        $detail = ArticleService::detail($id);

        $title   = isset($detail['title']) ? $detail['title'] : '';
        $content = isset($detail['content']) ? $detail['content'] : '';

        return view('/article',['title' => $title, 'content' => $content, 'show_title' => $show_title]);
    }

    /**
     * @api {get} /api/article/noviceGuide 新手指南列表
     * @apiName noviceGuide
     * @apiGroup 内容管理
     * @apiVersion 0.0.0
     * @apiDescription 新手指南列表
     *
     * @apiParam {Number} page = 1  页码
     * @apiParam {Number} rows = 10 每页数量
     *
     * @apiSuccess {Number} code  返回值 其他状态码提示错误  2000正常.
     * @apiSuccess {String} msg   提示语.
     * @apiSuccess {Array} [data] 返回数据
     * @apiSuccessExample {Array} 成功示例
     * "data": [
     *      {
     *          "id": "5",
     *          "title": "新手指南",
     *      },
     *      {
     *          "id": "6",
     *          "title": "老司机带带你",
     *      }
     * ]
     *
     */
    public function noviceGuide()
    {

        $list = ArticleService::getNoviceGuideList();
        $this->success('请求成功', $list);
    }

}