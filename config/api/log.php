<?php
/**
 * Created by PhpStorm.
 * User: MrXu
 * Date: 2021/1/18
 * Time: 01:23
 */

use think\facade\Env;

return [
    // 日志保存目录
    'path'        => Env::get('runtime_path') . 'log/api/'.date('Ym').'/',
    'max_files'   => 30,
];