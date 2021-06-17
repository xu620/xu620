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
// [ 安装文件 ]
use think\Container;

define('DS', DIRECTORY_SEPARATOR);

// 加载框架引导文件
require __DIR__ . '/../thinkphp/base.php';

// 绑定到安装控制器
// 执行应用
Container::get('app')->bind('\app\admin\command\Install')->run()->send();
