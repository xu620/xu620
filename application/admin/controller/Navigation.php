<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 导航管理
 *
 * @icon fa fa-circle-o
 */
class Navigation extends Backend
{
    
    /**
     * Navigation模型对象
     * @var \app\common\model\Navigation
     */
    protected $model = null;

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\Navigation;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("jumpTypeList", $this->model->getJumpTypeList());
    }
    

}
