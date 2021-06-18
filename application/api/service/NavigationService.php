<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: MrXu
 * +----------------------------------------------------------------------
 * | DateTime: 2020/12/16 15:59
 * +----------------------------------------------------------------------
 */

namespace app\api\service;

use app\common\model\Navigation;

class NavigationService extends Navigation
{
    protected $append = [];


    /**
     * 获取列表
     */
    public static function getList()
    {
        return self::cache(600)
                   ->where('status',1)
                   ->field('name,image,jump_type,url')
                   ->order('weigh desc')
                   ->select();
    }


    public function getImageAttr($value)
    {
        return get_domain_paths($value);
    }
}