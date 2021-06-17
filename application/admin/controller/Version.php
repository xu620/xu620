<?php

namespace app\admin\controller;

use app\common\controller\Backend;


/**
 * 版本管理
 *
 * @icon fa fa-circle-o
 */
class Version extends Backend
{

    protected $model = null;

    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\common\model\Version();
        $this->view->assign('platformList', $this->model->getPlatformList());
    }
    
}
