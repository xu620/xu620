<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 会员等级
 *
 * @icon fa fa-circle-o
 */
class Level extends Backend
{
    
    /**
     * Level模型对象
     * @var \app\common\model\user\Level
     */
    protected $model = null;

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\user\Level;

    }


}
