<?php
/**
 * +----------------------------------------------------------------------
 * | [ WE CAN DO IT MORE SIMPLE]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016-2020 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: MrXu
 * +----------------------------------------------------------------------
 * | DateTime: 2021/1/26 9:46
 * +----------------------------------------------------------------------
 */
namespace app\api\controller;

use app\common\controller\Api;

class RedisController extends Api {

    protected $redis = '';

    public function __construct(){
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1',6379);
    }

    public function redisList() {
       $redis =  $this->redis;
       echo $redis->ping();
    }
}