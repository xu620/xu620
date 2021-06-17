<?php

namespace app\api\service;

use app\common\model\article\Article;

class ArticleService extends Article
{

    // 追加属性
    protected $append = [

    ];

    /**
     * 获取新手引导
     */
    public static function getNoviceGuideList()
    {
        return self::cache(600)->where(['type' => 1, 'switch' => 1])->field('id,title')->order('weigh desc')->select();
    }

    /**
     * 获取详情
     */
    public static function detail($id)
    {
        //获取咨询列表
        return self::cache(600)->where('id', $id)->field('id,title,content')->find();
    }


    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }







}
