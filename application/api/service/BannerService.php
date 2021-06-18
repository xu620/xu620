<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: MrXu
 * +----------------------------------------------------------------------
 * | DateTime: 2022/01/28 1:29
 * +----------------------------------------------------------------------
 */

namespace app\api\service;

use app\common\model\Banner;


class BannerService extends Banner
{
    protected $visible = [
        'title','image','url','jump_type'
    ];

    /**
     * 获取列表
     *
     * int type 1首页 2投资理财
     */
    public static function lists($type = 1)
    {
        return self::cache(600)->where('type',$type)->order('weigh desc, id desc')->select();
    }

    protected function base($query)
    {
        $query->where('status',1);
    }

    public function getImageAttr($value)
    {
        return get_domain_paths($value);
    }

}
