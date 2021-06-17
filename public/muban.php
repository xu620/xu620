<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 后台入口文件 ]
// 使用此文件可以达到隐藏admin模块的效果
// 建议将admin.php改成其它任意的文件名，同时修改config.php中的'deny_module_list',把admin模块也添加进去
// 定义应用目录
use think\Container;

define('DS', DIRECTORY_SEPARATOR);
define('APP_PATH', __DIR__ . '/../application/');
define('ROOT_PATH', __DIR__ . DS . '..' . DS);
// 判断是否安装FastAdmin
if (!is_file(APP_PATH . 'admin/command/Install/install.lock')) {
    header("location:./install.php");
    exit;
}

// 加载框架引导文件
require __DIR__ . '/../thinkphp/base.php';

// 绑定到admin模块
Container::get('app')->bind('admin')->run()->send();
